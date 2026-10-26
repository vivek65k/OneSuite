<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Quota;
use App\Models\Crm\Team;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrmQuotaController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;
        $quotas = Quota::where('company_id', $companyId)->with(['employee', 'team'])->orderByDesc('period_start')->get();

        $rows = $quotas->map(function ($quota) {
            $achieved = $quota->achievedAmount();

            return [
                'quota' => $quota,
                'achieved' => $achieved,
                'pct' => $quota->target_amount > 0 ? round($achieved / $quota->target_amount * 100, 1) : 0,
            ];
        });

        return view('admin.crm.quotas.index', [
            'rows' => $rows,
            'employees' => Employee::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'teams' => Team::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['nullable', 'exists:employees,id'],
            'team_id' => ['nullable', 'exists:crm_teams,id'],
            'period' => ['required', 'in:'.implode(',', array_keys(Quota::PERIODS))],
            'period_start' => ['required', 'date'],
            'target_amount' => ['required', 'numeric', 'min:0'],
        ]);

        Quota::create($data + ['company_id' => Company::current()->id]);

        return back()->with('status', 'Quota set.');
    }

    public function destroy(Quota $quota): RedirectResponse
    {
        abort_unless($quota->company_id === Company::current()->id, 404);
        $quota->delete();

        return back()->with('status', 'Quota removed.');
    }
}
