<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\CrmRole;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrmRoleController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.crm.permissions.index', [
            'roles' => CrmRole::where('company_id', $companyId)->with('employees')->orderBy('name')->get(),
            'employees' => Employee::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'permissions' => CrmRole::PERMISSIONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
        ]);

        CrmRole::create($data + ['company_id' => Company::current()->id]);

        return back()->with('status', 'Role created.');
    }

    public function assign(Request $request, CrmRole $crmRole): RedirectResponse
    {
        abort_unless($crmRole->company_id === Company::current()->id, 404);
        $data = $request->validate(['employee_id' => ['required', 'exists:employees,id']]);
        $crmRole->employees()->syncWithoutDetaching([$data['employee_id']]);

        return back()->with('status', 'Role assigned.');
    }

    public function unassign(CrmRole $crmRole, Employee $employee): RedirectResponse
    {
        abort_unless($crmRole->company_id === Company::current()->id, 404);
        $crmRole->employees()->detach($employee->id);

        return back()->with('status', 'Role unassigned.');
    }

    public function destroy(CrmRole $crmRole): RedirectResponse
    {
        abort_unless($crmRole->company_id === Company::current()->id, 404);
        $crmRole->delete();

        return back()->with('status', 'Role removed.');
    }
}
