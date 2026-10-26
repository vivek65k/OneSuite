<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class EmployeeDocumentCategoryController extends Controller
{
    protected const TABS = ['soon', 'expired'];

    /** A document that runs out within this many days counts as "expiring soon". */
    public const SOON_DAYS = 60;

    /** Papers every employee is expected to have exactly one of; for these the page shows who is still missing one. */
    protected const EXPECTED = ['offer_letter', 'appointment_letter', 'contract', 'pan', 'aadhar'];

    /** Employees who have left aren't chased for missing papers. */
    protected const INACTIVE = ['terminated', 'inactive'];

    public function offerLetter(): View
    {
        return $this->render('offer_letter');
    }

    public function appointmentLetter(): View
    {
        return $this->render('appointment_letter');
    }

    public function contract(): View
    {
        return $this->render('contract');
    }

    public function pan(): View
    {
        return $this->render('pan');
    }

    public function aadhar(): View
    {
        return $this->render('aadhar');
    }

    public function passport(): View
    {
        return $this->render('passport');
    }

    public function visa(): View
    {
        return $this->render('visa');
    }

    public function certificate(): View
    {
        return $this->render('certificate');
    }

    public function experienceLetter(): View
    {
        return $this->render('experience_letter');
    }

    public function salarySlip(): View
    {
        return $this->render('salary_slip');
    }

    /** Everything on file of one kind of document. */
    protected function render(string $type): View
    {
        $this->authorize('viewAny', EmployeeDocument::class);

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

        $scoped = fn () => EmployeeDocument::query()
            ->where('employee_documents.company_id', Company::current()->id)
            ->where('category', $type)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
        $filtered = fn () => $scoped()
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['department_id'], fn ($q, $departmentId) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $departmentId)))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)
                    ->orWhereHas('employee', fn ($e) => $e->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('employee_code', 'like', $like))));
            });
        $soon = today()->addDays(self::SOON_DAYS);
        $tabbed = fn (Builder $q, string $tab) => match ($tab) {
            'soon' => $q->whereNotNull('expiry_date')->whereDate('expiry_date', '>=', today())->whereDate('expiry_date', '<=', $soon),
            'expired' => $q->whereNotNull('expiry_date')->whereDate('expiry_date', '<', today()),
            default => $q,
        };

        $documents = $tabbed($filtered(), $state['tab'])
            ->with('employee.department:id,name')
            // The ones running out first when looking at expiry; otherwise the newest upload first.
            ->when($state['tab'], fn ($q) => $q->orderBy('expiry_date'), fn ($q) => $q->latest())
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $all = $scoped()->get(['id', 'employee_id', 'expiry_date', 'created_at']);
        $people = Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->with('department:id,name')->orderBy('name')
            ->get(['id', 'name', 'employee_code', 'avatar', 'department_id', 'status']);
        $staff = $people->whereNotIn('status', self::INACTIVE)->values();
        $holders = $all->pluck('employee_id')->unique();
        $expected = in_array($type, self::EXPECTED, true);

        return view('admin.hrm.documents.category', [
            'type' => $type,
            'typeLabel' => EmployeeDocument::TYPES[$type],
            'documents' => $documents,
            'state' => $state,
            'employees' => $people->pluck('name', 'id'),
            'formPeople' => $staff,
            'holderIds' => $holders->flip(),
            'expected' => $expected,
            // Current staff with nothing of this kind on file, for the "still missing" list.
            'missing' => $expected ? $staff->reject(fn (Employee $e) => $holders->contains($e->id))->values() : collect(),
            'departments' => Department::query()->where('company_id', Company::current()->id)
                ->whereIn('id', $people->pluck('department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            'counts' => ['all' => $filtered()->count(), 'soon' => $tabbed($filtered(), 'soon')->count(), 'expired' => $tabbed($filtered(), 'expired')->count()],
            'kpis' => [
                'total' => $all->count(),
                'holders' => $holders->intersect($staff->pluck('id'))->count(),
                'staff' => $staff->count(),
                'month' => $all->filter(fn (EmployeeDocument $d) => $d->created_at && $d->created_at->isSameMonth(today()))->count(),
                'soon' => $all->filter(fn (EmployeeDocument $d) => $d->expiry_date && $d->expiry_date->gte(today()) && $d->expiry_date->lte($soon))->count(),
                'next' => $all->filter(fn (EmployeeDocument $d) => $d->expiry_date && $d->expiry_date->gte(today()))->min('expiry_date'),
                'expired' => $all->filter(fn (EmployeeDocument $d) => $d->expiry_date && $d->expiry_date->lt(today()))->count(),
                'dated' => $all->whereNotNull('expiry_date')->count(),
            ],
            'canCreate' => $request->user()->can('create', EmployeeDocument::class),
            'soonDays' => self::SOON_DAYS,
        ]);
    }
}
