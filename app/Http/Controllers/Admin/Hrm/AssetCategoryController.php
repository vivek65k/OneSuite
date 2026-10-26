<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAsset;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class AssetCategoryController extends Controller
{
    protected const TABS = ['assigned', 'returned', 'problem'];

    /** Returned in a state that needs following up. */
    protected const PROBLEM = ['damaged', 'lost'];

    /** Employees who have left: nothing new is issued to them, and what they still hold needs collecting. */
    protected const INACTIVE = ['terminated', 'inactive'];

    public function laptop(): View
    {
        return $this->render('laptop');
    }

    public function mobile(): View
    {
        return $this->render('mobile');
    }

    public function idCard(): View
    {
        return $this->render('id_card');
    }

    public function simCard(): View
    {
        return $this->render('sim_card');
    }

    public function uniform(): View
    {
        return $this->render('uniform');
    }

    /** The register of one kind of asset: everything of that kind that is out or has come back. */
    protected function render(string $category): View
    {
        $this->authorize('viewAny', EmployeeAsset::class);

        $request = request();
        // Anything that isn't a plain value (e.g. ?search[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $id = fn (string $key) => ctype_digit($text($key)) ? (int) $text($key) : null;
        $state = [
            'tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : '',
            'employee_id' => $id('employee_id'),
            'department_id' => $id('department_id'),
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $scoped = fn () => EmployeeAsset::query()
            ->where('employee_assets.company_id', Company::current()->id)
            ->where('category', $category)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
        $filtered = fn () => $scoped()
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['department_id'], fn ($q, $departmentId) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $departmentId)))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->where(fn ($w) => $w->where('asset_name', 'like', $like)->orWhere('asset_tag', 'like', $like)->orWhere('identifier', 'like', $like)->orWhere('brand_model', 'like', $like)
                    ->orWhereHas('employee', fn ($e) => $e->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('employee_code', 'like', $like))));
            });
        $tabbed = fn (Builder $q, string $tab) => match ($tab) {
            'assigned' => $q->where('status', 'assigned'),
            'returned' => $q->where('status', 'returned'),
            'problem' => $q->where('status', 'returned')->whereIn('condition_at_return', self::PROBLEM),
            default => $q,
        };

        $assets = $tabbed($filtered(), $state['tab'])
            ->with('employee.department:id,name')
            // What is still out comes first, newest handover on top.
            ->orderByRaw("status = 'assigned' desc")->orderByDesc('assigned_date')->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $all = $scoped()->get(['id', 'employee_id', 'asset_name', 'asset_tag', 'identifier', 'status', 'condition_at_return', 'assigned_date']);
        $out = $all->where('status', 'assigned');
        $back = $all->where('status', 'returned');
        $people = Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->with('department:id,name')->orderBy('name')
            ->get(['id', 'name', 'employee_code', 'avatar', 'department_id', 'status', 'joining_date']);
        $staff = $people->whereNotIn('status', self::INACTIVE)->values();
        $leavers = $people->whereIn('status', self::INACTIVE)->pluck('id');
        $holders = $out->pluck('employee_id')->unique();

        return view('admin.hrm.assets.category', [
            'category' => $category,
            'categoryLabel' => EmployeeAsset::CATEGORIES[$category],
            'assets' => $assets,
            'state' => $state,
            'employees' => $people->pluck('name', 'id'),
            'formPeople' => $staff,
            'leaverIds' => $leavers->flip(),
            'holderIds' => $holders->flip(),
            // Current staff who don't hold one of these, for the "who still needs one" list.
            'without' => $staff->reject(fn (Employee $e) => $holders->contains($e->id))->values(),
            'departments' => Department::query()->where('company_id', Company::current()->id)
                ->whereIn('id', $people->pluck('department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            'names' => $all->pluck('asset_name')->map(fn ($n) => trim($n))->unique(fn ($n) => mb_strtolower($n))->sort()->values(),
            'counts' => ['all' => $filtered()->count()] + collect(self::TABS)->mapWithKeys(fn ($tab) => [$tab => $tabbed($filtered(), $tab)->count()])->all(),
            'kpis' => [
                'out' => $out->count(),
                'holders' => $holders->intersect($staff->pluck('id'))->count(),
                'staff' => $staff->count(),
                'returned' => $back->count(),
                'damaged' => $back->where('condition_at_return', 'damaged')->count(),
                'lost' => $back->where('condition_at_return', 'lost')->count(),
                'leavers' => $out->whereIn('employee_id', $leavers)->count(),
                'untagged' => $out->filter(fn (EmployeeAsset $a) => blank($a->asset_tag) && blank($a->identifier))->count(),
            ],
            'canCreate' => $request->user()->can('create', EmployeeAsset::class),
        ]);
    }
}
