<?php

namespace App\Http\Controllers\Admin\Ess;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeAsset;
use App\Models\Resignation;
use App\Support\Notifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MyAssetController extends Controller
{
    use ResolvesCurrentEmployee;

    protected const TABS = ['held', 'returned'];

    /** What an employee can tell the asset team about something they hold. */
    public const ISSUES = [
        'not_working' => 'Not working properly',
        'damaged' => 'Damaged',
        'lost' => 'Lost or stolen',
        'not_mine' => 'I don\'t have this',
        'details_wrong' => 'Details are wrong',
    ];

    /** The line a report leaves on the asset starts with this, so HR's pages and this one can both spot it. */
    protected const REPORT_PREFIX = 'Reported by ';

    public function index(Request $request): View
    {
        $employee = $this->currentEmployee();

        // Anything that isn't a plain value (e.g. ?tab[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $state = ['tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : ''];

        $all = $this->mine($employee)->orderByRaw("status = 'assigned' desc")->orderByDesc('assigned_date')->orderByDesc('id')->get()
            ->map(function (EmployeeAsset $a) {
                // The newest thing the person reported about it, if anything.
                $report = collect(preg_split('/\R/', (string) $a->remarks))->filter(fn ($line) => str_starts_with($line, self::REPORT_PREFIX))->last();
                $a->setAttribute('report', $report ? trim(substr($report, strlen(self::REPORT_PREFIX))) : null);
                $a->setAttribute('days_held', $a->assigned_date ? (int) $a->assigned_date->diffInDays($a->returned_date ?? today()) : null);

                return $a;
            });
        $tabbed = fn (Collection $rows, string $tab) => match ($tab) {
            'held' => $rows->where('status', 'assigned'),
            'returned' => $rows->where('status', '!=', 'assigned'),
            default => $rows,
        };
        $held = $tabbed($all, 'held');
        $returned = $tabbed($all, 'returned');
        // Someone serving notice has to hand everything back by their last working day.
        $leaving = Resignation::withoutGlobalScopes()->where('employee_id', $employee->id)->where('status', 'accepted')->orderByDesc('id')->first();

        return view('admin.ess.assets', [
            'state' => $state,
            'held' => in_array($state['tab'], ['', 'held'], true) ? $held->values() : collect(),
            'returned' => in_array($state['tab'], ['', 'returned'], true) ? $returned->values() : collect(),
            'counts' => ['all' => $all->count(), 'held' => $held->count(), 'returned' => $returned->count()],
            'kpis' => [
                'held' => $held->count(),
                'kinds' => $held->pluck('category')->unique()->map(fn ($c) => EmployeeAsset::CATEGORIES[$c] ?? ucfirst((string) $c))->values(),
                'oldest' => $held->whereNotNull('assigned_date')->sortBy('assigned_date')->first(),
                'returned' => $returned->count(),
                'problems' => $returned->whereIn('condition_at_return', ['damaged', 'lost'])->count(),
                'reported' => $held->whereNotNull('report')->count(),
            ],
            'leaving' => $leaving && $held->isNotEmpty() ? $leaving : null,
        ]);
    }

    /** Tell the asset team something is wrong with an item you hold. It is recorded on the asset and they are notified. */
    public function report(Request $request, int $asset): RedirectResponse
    {
        $employee = $this->currentEmployee();
        // 404 (not 403) for someone else's asset, so the id doesn't confirm it exists.
        $record = $this->mine($employee)->whereKey($asset)->firstOrFail();

        $data = $request->validate([
            'issue' => ['required', Rule::in(array_keys(self::ISSUES))],
            'note' => ['nullable', 'string', 'max:300'],
        ], ['issue.*' => 'Choose what is wrong.']);
        $note = filled($data['note'] ?? null) ? trim(preg_replace('/\s+/', ' ', strip_tags($data['note']))) : null;
        $issue = self::ISSUES[$data['issue']];

        DB::transaction(function () use ($record, $employee, $issue, $note) {
            $row = EmployeeAsset::withoutGlobalScopes()->whereKey($record->id)->lockForUpdate()->first();

            if ($row->status !== 'assigned') {
                throw ValidationException::withMessages(['asset' => $row->asset_name.' has already been returned, so there is nothing to report on it.']);
            }
            $stamp = self::REPORT_PREFIX.$employee->name.' on '.today()->format('M j, Y').': ';
            // One report a day per item is enough for the asset team to act on.
            if (str_contains((string) $row->remarks, $stamp)) {
                throw ValidationException::withMessages(['asset' => 'You already reported a problem with '.$row->asset_name.' today. The asset team has been told.']);
            }
            $row->forceFill(['remarks' => trim(($row->remarks ? $row->remarks."\n" : '').$stamp.$issue.($note ? ' - '.$note : ''))])->save();
        });

        Notifier::notifyUsersWithPermission(
            'update_employee_asset',
            'Asset problem reported',
            $employee->name.' reported "'.$issue.'" for '.$record->asset_name.($record->asset_tag ? ' ('.$record->asset_tag.')' : '').($note ? ': '.$note : '.'),
            route('admin.asset-history.index'),
            'ti ti-alert-triangle',
            $employee->branch_id,
            employeeId: $employee->id,
        );

        return back()->with('status', 'Thanks. The asset team has been told about '.$record->asset_name.'.');
    }

    /** This employee's own assets, whatever their role may browse elsewhere. */
    protected function mine(Employee $employee): Builder
    {
        return EmployeeAsset::withoutGlobalScopes()->where('employee_id', $employee->id)->where('company_id', $employee->company_id);
    }
}
