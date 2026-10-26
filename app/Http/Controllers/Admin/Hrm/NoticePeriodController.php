<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\FinalSettlement;
use App\Models\Resignation;
use App\Support\BranchContext;
use App\Support\LoanRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NoticePeriodController extends Controller
{
    protected const TABS = ['week', 'overdue', 'short'];

    /** The longest anyone stays after resigning, in days. */
    protected const MAX_DAYS = 365;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Resignation::class);

        // Anything that isn't a plain value (e.g. ?tab[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $state = [
            'tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : '',
            'department_id' => ctype_digit($text('department_id')) ? (int) $text('department_id') : null,
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $all = $this->rows();
        $needle = mb_strtolower($state['search']);
        $filtered = $all
            ->when($state['department_id'], fn (Collection $c, $departmentId) => $c->where('department_id', $departmentId))
            ->when($needle !== '', fn (Collection $c) => $c->filter(fn ($r) => str_contains(mb_strtolower($r['name'].' '.$r['resignation']->employee?->employee_code), $needle)))
            ->values();
        $tabbed = fn (Collection $rows, string $tab) => match ($tab) {
            'week' => $rows->filter(fn ($r) => $r['days_left'] >= 0 && $r['days_left'] <= 7),
            'overdue' => $rows->where('days_left', '<', 0),
            'short' => $rows->where('shortfall', '>', 0),
            default => $rows,
        };
        $user = $request->user();

        return view('admin.hrm.separation.notice-period', [
            'rows' => $tabbed($filtered, $state['tab'])->values(),
            'state' => $state,
            'departments' => Department::query()->where('company_id', Company::current()->id)
                ->whereIn('id', $all->pluck('department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            'counts' => ['all' => $filtered->count()] + collect(self::TABS)->mapWithKeys(fn ($tab) => [$tab => $tabbed($filtered, $tab)->count()])->all(),
            'kpis' => [
                'serving' => $all->count(),
                'next' => $all->where('days_left', '>=', 0)->sortBy('days_left')->first(),
                'week' => $tabbed($all, 'week')->count(),
                'overdue' => $tabbed($all, 'overdue')->count(),
                'short' => $tabbed($all, 'short')->count(),
                'short_days' => (int) $all->sum('shortfall'),
                'recovery' => (float) $all->sum(fn ($r) => (float) $r['recovery']),
            ],
            'canUpdate' => $user->can('update', new Resignation),
            // This login can't change their own last day (super admin excepted), same as denyOwnRequest().
            'ownEmployeeId' => $user->is_super_admin ? null : $all->pluck('resignation')->first(fn (Resignation $r) => (int) $r->employee?->user_id === (int) $user->id)?->employee_id,
            'maxDays' => self::MAX_DAYS,
        ]);
    }

    public function updateLastWorkingDay(Request $request, Resignation $resignation): RedirectResponse
    {
        abort_unless($this->scoped()->whereKey($resignation->id)->exists(), 404);
        $this->authorize('update', $resignation);
        $this->denyOwnRequest($resignation->employee);

        $data = $request->validate([
            'last_working_day' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:300'],
        ]);

        $newDay = Carbon::parse($data['last_working_day'])->startOfDay();
        $note = filled($data['note'] ?? null) ? trim($data['note']) : null;

        $from = DB::transaction(function () use ($resignation, $newDay, $note) {
            $row = Resignation::withoutGlobalScopes()->whereKey($resignation->id)->lockForUpdate()->first();

            // Withdrawn, completed or still pending: nobody is serving notice on it.
            if ($row->status !== 'accepted') {
                throw ValidationException::withMessages(['last_working_day' => 'This resignation is '.$row->status.', so nobody is serving notice on it.']);
            }
            if ($newDay->lt($row->resignation_date)) {
                throw ValidationException::withMessages(['last_working_day' => 'The last working day can\'t be before the resignation date ('.$row->resignation_date->format('M j, Y').').']);
            }
            if ($row->resignation_date->diffInDays($newDay) > self::MAX_DAYS) {
                throw ValidationException::withMessages(['last_working_day' => 'That is more than a year after the resignation. Check the date.']);
            }
            // The final dues were worked out to the old date; moving it would make them wrong.
            if ($settlement = FinalSettlement::withoutGlobalScopes()->where('resignation_id', $row->id)->whereIn('status', ['processed', 'paid'])->first()) {
                throw ValidationException::withMessages(['last_working_day' => 'The final settlement has already been '.$settlement->status.' for this last working day, so it can\'t be moved.']);
            }
            if ($newDay->isSameDay($row->last_working_day)) {
                return null;
            }

            $old = $row->last_working_day->copy();
            // Kept on the record: who moved the date, from what to what, and why.
            $line = 'Last working day moved from '.$old->format('M j, Y').' to '.$newDay->format('M j, Y').' by '.(Auth::user()?->name ?? 'HR').' on '.today()->format('M j, Y').($note ? ': '.$note : '.');
            $resignation->update(['last_working_day' => $newDay->toDateString(), 'remarks' => trim(($row->remarks ? $row->remarks."\n" : '').$line)]);

            return $old;
        });

        if ($from === null) {
            return back()->with('status', 'The last working day was already '.$newDay->format('M j, Y').'; nothing changed.');
        }

        $noticeEnds = $resignation->resignation_date->copy()->addDays((int) $resignation->notice_period_days);
        $short = $newDay->lt($noticeEnds) ? (int) $newDay->diffInDays($noticeEnds) : 0;

        return back()->with('status', 'Last working day of '.($resignation->employee?->name ?? 'the employee').' moved from '.$from->format('M j, Y').' to '.$newDay->format('M j, Y').'.'
            .($short ? ' That is '.$short.' '.($short === 1 ? 'day' : 'days').' short of the notice period.' : ''));
    }

    /** Everyone serving notice, the one leaving soonest first, with how their last day compares to their notice. */
    protected function rows(): Collection
    {
        $resignations = $this->scoped()->where('status', 'accepted')
            ->with(['employee.department:id,name', 'clearances:id,resignation_id,is_cleared', 'finalSettlement:id,resignation_id,status'])
            ->orderBy('last_working_day')->orderBy('id')->get();
        $salaries = LoanRules::salaries($resignations->pluck('employee_id'));

        return $resignations->map(function (Resignation $r) use ($salaries) {
            $noticeEnds = $r->resignation_date->copy()->addDays((int) $r->notice_period_days);
            $shortfall = $r->last_working_day->lt($noticeEnds) ? (int) $r->last_working_day->diffInDays($noticeEnds) : 0;
            $monthly = $salaries[$r->employee_id] ?? null;

            return [
                'resignation' => $r,
                'name' => $r->employee?->name ?? 'Removed employee',
                'department_id' => $r->employee?->department_id,
                'notice_ends' => $noticeEnds,
                'days_left' => (int) today()->diffInDays($r->last_working_day, false),
                // Days of notice not being served (an early release), or served beyond it.
                'shortfall' => $shortfall,
                'extra' => $r->last_working_day->gt($noticeEnds) ? (int) $noticeEnds->diffInDays($r->last_working_day) : 0,
                'monthly' => $monthly,
                // What the unserved notice is worth at the person's pay: a guide for the final settlement, not a charge.
                'recovery' => $shortfall && $monthly ? round($monthly / 30 * $shortfall, 2) : null,
                'locked' => in_array($r->finalSettlement?->status, ['processed', 'paid'], true),
            ];
        });
    }

    /** Resignations this login may see: its own company and, when tied to a branch, that branch's employees. */
    protected function scoped(): Builder
    {
        return Resignation::query()
            ->where('resignations.company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }
}
