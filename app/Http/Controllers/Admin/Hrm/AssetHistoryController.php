<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAsset;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetHistoryController extends Controller
{
    use ExportsCsv;

    protected const TABS = ['assigned', 'returned', 'problem'];

    /** Returned in a state that needed following up. */
    protected const PROBLEM = ['damaged', 'lost'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', EmployeeAsset::class);

        $state = $this->state($request);
        $assets = $this->tabbed($this->filtered($state), $state['tab'])
            ->with('employee.department:id,name')
            // Following one item: oldest holder first, so it reads as a story. Otherwise newest first.
            ->when($state['trace'] !== '', fn ($q) => $q->orderBy('assigned_date')->orderBy('id'), fn ($q) => $q->orderByDesc('assigned_date')->orderByDesc('id'))
            ->paginate(25)
            ->withQueryString();

        $all = $this->owned()->get(['id', 'employee_id', 'asset_tag', 'identifier', 'status', 'condition_at_return', 'assigned_date', 'returned_date']);
        $back = $all->where('status', 'returned');
        $kept = $back->filter(fn (EmployeeAsset $a) => $a->assigned_date && $a->returned_date)->map(fn (EmployeeAsset $a) => (int) $a->assigned_date->diffInDays($a->returned_date));
        $people = Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->orderBy('name')->get(['id', 'name', 'department_id']);

        return view('admin.hrm.assets.history', [
            'assets' => $assets,
            'state' => $state,
            'employees' => $people->whereIn('id', $all->pluck('employee_id')->unique())->pluck('name', 'id'),
            'departments' => Department::query()->where('company_id', Company::current()->id)
                ->whereIn('id', $people->pluck('department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            'years' => $all->flatMap(fn (EmployeeAsset $a) => [$a->assigned_date?->year, $a->returned_date?->year])->filter()->unique()->sortDesc()->values(),
            'counts' => ['all' => $this->filtered($state)->count()] + collect(self::TABS)->mapWithKeys(fn ($tab) => [$tab => $this->tabbed($this->filtered($state), $tab)->count()])->all(),
            'kpis' => [
                'records' => $all->count(),
                'people' => $all->pluck('employee_id')->unique()->count(),
                'out' => $all->where('status', 'assigned')->count(),
                'returned' => $back->count(),
                'average_days' => $kept->isNotEmpty() ? (int) round($kept->avg()) : null,
                'damaged' => $back->where('condition_at_return', 'damaged')->count(),
                'lost' => $back->where('condition_at_return', 'lost')->count(),
            ],
            'trace' => $state['trace'] !== '' ? $this->trace($state['trace']) : null,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', EmployeeAsset::class);

        $state = $this->state($request);
        $assets = $this->tabbed($this->filtered($state), $state['tab'])->with('employee')->orderByDesc('assigned_date')->orderByDesc('id')->get();
        // A cell starting with = + - @ would run as a formula when the file is opened in a spreadsheet.
        $safe = fn ($value) => is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;

        return $this->streamCsv(
            'asset-history-'.today()->toDateString().'.csv',
            // The first ten columns are where they always were; new ones are added at the end.
            ['Employee', 'Category', 'Asset', 'Tag', 'Identifier', 'Brand/Model', 'Assigned Date', 'Returned Date', 'Status', 'Condition at Return', 'Days Held', 'Remarks', 'Employee Code'],
            (function () use ($assets, $safe) {
                foreach ($assets as $asset) {
                    yield array_map($safe, [
                        $asset->employee?->name ?? 'Removed employee', EmployeeAsset::CATEGORIES[$asset->category] ?? $asset->category, $asset->asset_name,
                        $asset->asset_tag, $asset->identifier, $asset->brand_model,
                        $asset->assigned_date?->format('Y-m-d'), $asset->returned_date?->format('Y-m-d'),
                        EmployeeAsset::STATUSES[$asset->status] ?? $asset->status, $asset->condition_at_return,
                        $asset->assigned_date ? (int) $asset->assigned_date->diffInDays($asset->returned_date ?? today()) : null,
                        $asset->remarks, $asset->employee?->employee_code,
                    ]);
                }
            })()
        );
    }

    protected function state(Request $request): array
    {
        // Anything that isn't a plain value (e.g. ?category[]=x) is ignored rather than crashing the page or the export.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $id = fn (string $key) => ctype_digit($text($key)) ? (int) $text($key) : null;
        // The old page filtered with ?status=assigned|returned; those links keep working.
        $tab = $text('tab') !== '' ? $text('tab') : $text('status');

        return [
            'tab' => in_array($tab, self::TABS, true) ? $tab : '',
            'category' => array_key_exists($text('category'), EmployeeAsset::CATEGORIES) ? $text('category') : '',
            'employee_id' => $id('employee_id'),
            'department_id' => $id('department_id'),
            'year' => $id('year') && $id('year') >= 2000 && $id('year') <= 2100 ? $id('year') : null,
            'search' => trim(mb_substr($text('search'), 0, 80)),
            'trace' => trim(mb_substr($text('trace'), 0, 100)),
        ];
    }

    protected function filtered(array $state): Builder
    {
        return $this->owned()
            ->when($state['category'], fn ($q, $category) => $q->where('category', $category))
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['department_id'], fn ($q, $departmentId) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $departmentId)))
            // Anything handed over or taken back in that year.
            ->when($state['year'], fn ($q, $year) => $q->where(fn ($w) => $w->whereYear('assigned_date', $year)->orWhereYear('returned_date', $year)))
            ->when($state['trace'] !== '', fn ($q) => $this->matching($q, $state['trace']))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->where(fn ($w) => $w->where('asset_name', 'like', $like)->orWhere('asset_tag', 'like', $like)->orWhere('identifier', 'like', $like)->orWhere('brand_model', 'like', $like)->orWhere('remarks', 'like', $like)
                    ->orWhereHas('employee', fn ($e) => $e->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('employee_code', 'like', $like))));
            });
    }

    protected function tabbed(Builder $query, string $tab): Builder
    {
        return match ($tab) {
            'assigned' => $query->where('status', 'assigned'),
            'returned' => $query->where('status', 'returned'),
            'problem' => $query->where('status', 'returned')->whereIn('condition_at_return', self::PROBLEM),
            default => $query,
        };
    }

    /** Every record of one physical item: the same tag or the same serial / IMEI / card number. */
    protected function matching(Builder $query, string $code): Builder
    {
        $code = mb_strtolower($code);

        return $query->where(fn ($w) => $w->whereRaw('LOWER(asset_tag) = ?', [$code])
            // A uniform's "identifier" is its size, which doesn't single out one item.
            ->orWhere(fn ($x) => $x->where('category', '!=', 'uniform')->whereRaw('LOWER(identifier) = ?', [$code])));
    }

    /** Summary of one item's journey, for the header above its records. */
    protected function trace(string $code): array
    {
        $records = $this->matching($this->owned(), $code)->with('employee:id,name')->orderBy('assigned_date')->orderBy('id')->get();
        $current = $records->firstWhere('status', 'assigned');
        $last = $records->last();

        return [
            'code' => $code,
            'name' => $last?->asset_name,
            'category' => $last?->category,
            'records' => $records->count(),
            'holders' => $records->pluck('employee_id')->unique()->count(),
            'first' => $records->first()?->assigned_date,
            'with' => $current?->employee?->name,
            'last_condition' => $current ? null : $last?->condition_at_return,
        ];
    }

    /** Records this login may see: its own company and, when tied to a branch, that branch's employees. */
    protected function owned(): Builder
    {
        return EmployeeAsset::query()
            ->where('employee_assets.company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }
}
