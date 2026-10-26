<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAsset;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssetController extends Controller
{
    /** Employees who have left: nothing new is issued to them, and what they still hold needs collecting. */
    protected const INACTIVE = ['terminated', 'inactive'];

    /** An assignment can be dated at most this many years back. */
    protected const YEARS_BACK = 20;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', EmployeeAsset::class);

        // Anything that isn't a plain value (e.g. ?category[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $id = fn (string $key) => ctype_digit($text($key)) ? (int) $text($key) : null;
        // The old page filtered with ?category=; those links keep working.
        $tab = $text('tab') !== '' ? $text('tab') : $text('category');
        $state = [
            'tab' => array_key_exists($tab, EmployeeAsset::CATEGORIES) || $tab === 'leavers' ? $tab : '',
            'employee_id' => $id('employee_id'),
            'department_id' => $id('department_id'),
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $filtered = fn () => $this->scoped()
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['department_id'], fn ($q, $departmentId) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $departmentId)))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->where(fn ($w) => $w->where('asset_name', 'like', $like)->orWhere('asset_tag', 'like', $like)->orWhere('identifier', 'like', $like)->orWhere('brand_model', 'like', $like)
                    ->orWhereHas('employee', fn ($e) => $e->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('employee_code', 'like', $like))));
            });
        $tabbed = fn (Builder $q, string $tab) => match ($tab) {
            '' => $q,
            'leavers' => $q->whereHas('employee', fn ($e) => $e->whereIn('status', self::INACTIVE)),
            default => $q->where('category', $tab),
        };

        $assets = $tabbed($filtered(), $state['tab'])
            ->with('employee.department:id,name')
            ->orderByDesc('assigned_date')->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $all = $this->scoped()->get(['id', 'employee_id', 'category', 'asset_name', 'asset_tag', 'identifier', 'assigned_date']);
        $people = $this->employeeQuery()->with('department:id,name')->orderBy('name')->get(['id', 'name', 'employee_code', 'department_id', 'status', 'joining_date']);
        $leavers = $people->whereIn('status', self::INACTIVE)->pluck('id');
        $withLeavers = $all->whereIn('employee_id', $leavers);
        $top = $all->countBy('category')->sortDesc();

        return view('admin.hrm.assets.assign', [
            'assets' => $assets,
            'state' => $state,
            'employees' => $people->pluck('name', 'id'),
            'formPeople' => $people->whereNotIn('status', self::INACTIVE)->values(),
            'leaverIds' => $leavers->flip(),
            'departments' => Department::query()->where('company_id', Company::current()->id)
                ->whereIn('id', $people->pluck('department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            // What each person already holds, so the form can show it before something else is handed over.
            'holdings' => $all->groupBy('employee_id')->map(fn ($group) => $group->map(fn (EmployeeAsset $a) => ['category' => $a->category, 'name' => $a->asset_name, 'tag' => $a->asset_tag])->values()),
            'names' => $all->pluck('asset_name')->map(fn ($n) => trim($n))->unique(fn ($n) => mb_strtolower($n))->sort()->values(),
            'counts' => ['all' => $filtered()->count(), 'leavers' => $tabbed($filtered(), 'leavers')->count()]
                + collect(EmployeeAsset::CATEGORIES)->map(fn ($label, $key) => $tabbed($filtered(), $key)->count())->all(),
            'kpis' => [
                'out' => $all->count(),
                'holders' => $all->pluck('employee_id')->unique()->count(),
                'staff' => $people->whereNotIn('status', self::INACTIVE)->count(),
                'top' => $top->keys()->first(),
                'top_count' => (int) $top->first(),
                'month' => $all->filter(fn (EmployeeAsset $a) => $a->assigned_date && $a->assigned_date->isSameMonth(today()))->count(),
                'leavers' => $withLeavers->count(),
                'leaver_people' => $withLeavers->pluck('employee_id')->unique()->count(),
                'untagged' => $all->filter(fn (EmployeeAsset $a) => blank($a->asset_tag) && blank($a->identifier))->count(),
            ],
            'canCreate' => $request->user()->can('create', EmployeeAsset::class),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', EmployeeAsset::class);

        $data = $this->validated($request);
        $employee = $this->employee($request->input('employee_id'));

        $this->guardDate($employee, $data['assigned_date']);
        $this->guardDoubleIssue($data);

        EmployeeAsset::create($data + ['company_id' => Company::current()->id, 'employee_id' => $employee->id, 'status' => 'assigned']);

        // The Laptops, Mobiles, ... pages post here too, so go back to wherever the form was.
        return back()->with('status', $data['asset_name'].' assigned to '.$employee->name.'.');
    }

    public function update(Request $request, EmployeeAsset $asset): RedirectResponse
    {
        $this->ensureOwn($asset);
        $this->authorize('update', $asset);

        $data = $this->validated($request);

        if ($asset->employee) {
            $this->guardDate($asset->employee, $data['assigned_date']);
        }
        $this->guardDoubleIssue($data, $asset);

        $asset->update($data);

        return back()->with('status', 'Asset details updated.');
    }

    /** Hand an asset from one person to another: the first holder's record is closed and a new one is opened. */
    public function transfer(Request $request, EmployeeAsset $asset): RedirectResponse
    {
        $this->ensureOwn($asset);
        $this->authorize('update', $asset);
        $this->authorize('create', EmployeeAsset::class);

        $data = $request->validate([
            'employee_id' => ['required'],
            'transfer_date' => ['required', 'date', 'before_or_equal:today'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ], ['transfer_date.before_or_equal' => 'The handover date can\'t be in the future.']);

        $to = $this->employee($data['employee_id']);
        $date = Carbon::parse($data['transfer_date'])->startOfDay();

        if ($to->id === $asset->employee_id) {
            throw ValidationException::withMessages(['employee_id' => $to->name.' already holds this asset.']);
        }
        if ($asset->assigned_date && $date->lt($asset->assigned_date)) {
            throw ValidationException::withMessages(['transfer_date' => 'The handover can\'t be before '.$asset->assigned_date->format('M j, Y').', when '.($asset->employee?->name ?? 'the current holder').' received it.']);
        }
        $this->guardDate($to, $date->toDateString());

        $from = $asset->employee?->name ?? 'the previous holder';
        $note = filled($data['remarks'] ?? null) ? ' '.trim($data['remarks']) : '';

        DB::transaction(function () use ($asset, $to, $date, $from, $note) {
            $row = EmployeeAsset::withoutGlobalScopes()->whereKey($asset->id)->lockForUpdate()->first();

            // Someone else returned or transferred it a moment ago.
            if ($row->status !== 'assigned') {
                throw ValidationException::withMessages(['employee_id' => 'This asset is no longer assigned, so it can\'t be transferred. Reload the page.']);
            }

            EmployeeAsset::create([
                'company_id' => $row->company_id, 'employee_id' => $to->id, 'category' => $row->category, 'asset_name' => $row->asset_name,
                'asset_tag' => $row->asset_tag, 'identifier' => $row->identifier, 'brand_model' => $row->brand_model,
                'assigned_date' => $date->toDateString(), 'status' => 'assigned', 'remarks' => trim('Transferred from '.$from.'.'.$note),
            ]);
            $asset->update(['status' => 'returned', 'returned_date' => $date->toDateString(), 'condition_at_return' => 'good', 'remarks' => trim('Transferred to '.$to->name.'.'.$note)]);
        });

        return back()->with('status', $asset->asset_name.' transferred from '.$from.' to '.$to->name.'. Both records are kept in Asset History.');
    }

    public function destroy(EmployeeAsset $asset): RedirectResponse
    {
        // The Laptops, Mobiles, ... pages also delete returned records here, so this one isn't limited to assets still out.
        abort_unless($this->owned()->whereKey($asset->id)->exists(), 404);
        $this->authorize('delete', $asset);

        $asset->delete();

        return back()->with('status', 'Asset record removed.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(EmployeeAsset::CATEGORIES))],
            'asset_name' => ['required', 'string', 'max:255'],
            'asset_tag' => ['nullable', 'string', 'max:100'],
            'identifier' => ['nullable', 'string', 'max:100'],
            'brand_model' => ['nullable', 'string', 'max:255'],
            'assigned_date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.today()->subYears(self::YEARS_BACK)->toDateString()],
        ], [
            'assigned_date.before_or_equal' => 'The assigned date can\'t be in the future.',
            'assigned_date.after_or_equal' => 'The assigned date can be at most '.self::YEARS_BACK.' years back.',
        ]);

        $clean = fn (string $key) => filled($data[$key] ?? null) ? trim($data[$key]) : null;

        return [
            'category' => $data['category'],
            'asset_name' => trim($data['asset_name']),
            'asset_tag' => $clean('asset_tag'),
            'identifier' => $clean('identifier'),
            'brand_model' => $clean('brand_model'),
            'assigned_date' => Carbon::parse($data['assigned_date'])->toDateString(),
        ];
    }

    /** A current employee this login may issue things to. */
    protected function employee(mixed $id): Employee
    {
        $employee = is_scalar($id) && ctype_digit((string) $id)
            ? $this->employeeQuery()->whereNotIn('status', self::INACTIVE)->whereKey((int) $id)->first(['id', 'name', 'joining_date'])
            : null;

        if (! $employee) {
            throw ValidationException::withMessages(['employee_id' => 'Choose an employee from the list.']);
        }

        return $employee;
    }

    protected function guardDate(Employee $employee, string $date): void
    {
        if ($employee->joining_date && Carbon::parse($date)->lt($employee->joining_date)) {
            throw ValidationException::withMessages(['assigned_date' => $employee->name.' joined on '.$employee->joining_date->format('M j, Y').', so nothing can be assigned to them before that.']);
        }
    }

    /** One physical item can't be with two people: the same tag, or the same serial/IMEI/card number, already out. */
    protected function guardDoubleIssue(array $data, ?EmployeeAsset $except = null): void
    {
        $out = fn () => EmployeeAsset::withoutGlobalScopes()->where('company_id', Company::current()->id)->where('status', 'assigned')
            ->when($except, fn ($q) => $q->whereKeyNot($except->id))->with('employee:id,name');

        if ($data['asset_tag'] !== null && ($clash = $out()->whereRaw('LOWER(asset_tag) = ?', [mb_strtolower($data['asset_tag'])])->first())) {
            throw ValidationException::withMessages(['asset_tag' => 'Asset tag "'.$data['asset_tag'].'" is already assigned to '.($clash->employee?->name ?? 'someone').' ('.$clash->asset_name.'). Take it back or transfer it first.']);
        }

        // For a uniform the "identifier" is its size, which many people share.
        if ($data['category'] !== 'uniform' && $data['identifier'] !== null && ($clash = $out()->where('category', $data['category'])->whereRaw('LOWER(identifier) = ?', [mb_strtolower($data['identifier'])])->first())) {
            throw ValidationException::withMessages(['identifier' => '"'.$data['identifier'].'" is already assigned to '.($clash->employee?->name ?? 'someone').' ('.$clash->asset_name.'). Take it back or transfer it first.']);
        }
    }

    /** 404 (not 403) for another company's or branch's asset, or one already returned, so the URL doesn't confirm it exists. */
    protected function ensureOwn(EmployeeAsset $asset): void
    {
        abort_unless($this->scoped()->whereKey($asset->id)->exists(), 404);
    }

    /** Assets that are out right now, with this login's own people. */
    protected function scoped(): Builder
    {
        return $this->owned()->where('employee_assets.status', 'assigned');
    }

    protected function owned(): Builder
    {
        return EmployeeAsset::query()
            ->where('employee_assets.company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }

    protected function employeeQuery(): Builder
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }
}
