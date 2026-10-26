<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Lead;
use App\Models\Employee;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadAssignmentController extends Controller
{
    protected const SORTABLE = ['name', 'created_at'];

    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));
        $filter = (string) $request->query('filter', '');
        $sort = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'created_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $base = Lead::query()->where('company_id', $companyId)->whereNotIn('status', ['converted']);

        if ($filter === 'assigned') {
            $base->whereNotNull('assigned_to_employee_id');
        } elseif ($filter === 'unassigned') {
            $base->whereNull('assigned_to_employee_id');
        }

        if ($search !== '') {
            $base->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('company_name', 'like', "%{$search}%"));
        }

        $leads = (clone $base)->with('assignedTo')->orderBy($sort, $direction)->paginate(25)->withQueryString();

        $employees = Employee::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->orderBy('name')
            ->pluck('name', 'id');

        $countsBase = Lead::query()->where('company_id', $companyId)->whereNotIn('status', ['converted']);

        return view('admin.crm.assignment.index', [
            'leads' => $leads,
            'employees' => $employees,
            'search' => $search,
            'filter' => $filter,
            'sort' => $sort,
            'direction' => $direction,
            'total' => (clone $countsBase)->count(),
            'assignedCount' => (clone $countsBase)->whereNotNull('assigned_to_employee_id')->count(),
            'unassignedCount' => (clone $countsBase)->whereNull('assigned_to_employee_id')->count(),
        ]);
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        abort_unless($lead->company_id === Company::current()->id, 404);

        $data = $request->validate(['assigned_to_employee_id' => ['nullable', 'exists:employees,id']]);

        $lead->update($data);

        return back()->with('status', $data['assigned_to_employee_id'] ? 'Lead assigned.' : 'Lead unassigned.');
    }
}
