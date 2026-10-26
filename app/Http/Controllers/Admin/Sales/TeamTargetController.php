<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Quota;
use App\Models\Crm\Team;
use App\Models\Crm\Territory;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Teams, Territories and Quotas already live in crm_teams/crm_territories/
 * crm_quotas with full CRUD in CRM - this is a single Sales-native screen
 * over the same records (team rosters, territory assignment, quota
 * progress and incentive payout) rather than three separate duplicate
 * screens. Quota achievement here is real booked Sales Order revenue
 * (Quota::achievedSalesAmount()), not CRM's won-Opportunity figure.
 */
class TeamTargetController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        $quotas = Quota::where('company_id', $companyId)->with(['employee', 'team'])->orderByDesc('period_start')->get()
            ->map(function (Quota $quota) {
                $achieved = $quota->achievedSalesAmount();

                return [
                    'quota' => $quota,
                    'achieved' => $achieved,
                    'pct' => $quota->target_amount > 0 ? min(100, round($achieved / $quota->target_amount * 100, 1)) : 0,
                    'commission' => $quota->commissionAmount(),
                ];
            });

        return view('admin.sales.team-targets.index', [
            'teams' => Team::where('company_id', $companyId)->with(['manager', 'members', 'territories'])->orderBy('name')->get(),
            'territories' => Territory::where('company_id', $companyId)->with('team')->orderBy('name')->get(),
            'quotaRows' => $quotas,
            'totalTarget' => $quotas->sum(fn ($r) => (float) $r['quota']->target_amount),
            'totalAchieved' => $quotas->sum('achieved'),
            'totalCommission' => $quotas->sum('commission'),
            'employees' => Employee::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeTeam(Request $request): RedirectResponse
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

    public function destroyTeam(Team $team): RedirectResponse
    {
        abort_unless($team->company_id === Company::current()->id, 404);
        $team->delete();

        return back()->with('status', 'Team removed.');
    }

    public function storeTerritory(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'team_id' => ['nullable', 'exists:crm_teams,id'],
            'criteria' => ['nullable', 'string', 'max:255'],
        ]);

        Territory::create($data + ['company_id' => Company::current()->id]);

        return back()->with('status', 'Territory created.');
    }

    public function destroyTerritory(Territory $territory): RedirectResponse
    {
        abort_unless($territory->company_id === Company::current()->id, 404);
        $territory->delete();

        return back()->with('status', 'Territory removed.');
    }

    public function storeQuota(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['nullable', 'exists:employees,id'],
            'team_id' => ['nullable', 'exists:crm_teams,id'],
            'period' => ['required', 'in:'.implode(',', array_keys(Quota::PERIODS))],
            'period_start' => ['required', 'date'],
            'target_amount' => ['required', 'numeric', 'min:0'],
            'incentive_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        Quota::create($data + ['company_id' => Company::current()->id, 'incentive_percent' => $data['incentive_percent'] ?? 0]);

        return back()->with('status', 'Quota set.');
    }

    public function destroyQuota(Quota $quota): RedirectResponse
    {
        abort_unless($quota->company_id === Company::current()->id, 404);
        $quota->delete();

        return back()->with('status', 'Quota removed.');
    }
}
