<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Team;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrmTeamController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.crm.teams.index', [
            'teams' => Team::where('company_id', $companyId)->with(['manager', 'members'])->orderBy('name')->get(),
            'employees' => Employee::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'manager_employee_id' => ['nullable', 'exists:employees,id'],
        ]);

        Team::create($data + ['company_id' => Company::current()->id]);

        return back()->with('status', 'Team created.');
    }

    public function addMember(Request $request, Team $team): RedirectResponse
    {
        abort_unless($team->company_id === Company::current()->id, 404);
        $data = $request->validate(['employee_id' => ['required', 'exists:employees,id']]);
        $team->members()->syncWithoutDetaching([$data['employee_id']]);

        return back()->with('status', 'Member added to team.');
    }

    public function removeMember(Team $team, Employee $employee): RedirectResponse
    {
        abort_unless($team->company_id === Company::current()->id, 404);
        $team->members()->detach($employee->id);

        return back()->with('status', 'Member removed from team.');
    }

    public function destroy(Team $team): RedirectResponse
    {
        abort_unless($team->company_id === Company::current()->id, 404);
        $team->delete();

        return back()->with('status', 'Team removed.');
    }
}
