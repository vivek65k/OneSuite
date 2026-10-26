<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Crm\CrmSetting;
use App\Models\Crm\SlaPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrmSettingsController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $branchId = $request->query('branch_id') ?: null;

        $settings = CrmSetting::firstOrCreate(['company_id' => $companyId, 'branch_id' => $branchId]);
        $companyDefault = $branchId ? CrmSetting::firstOrCreate(['company_id' => $companyId, 'branch_id' => null]) : null;

        return view('admin.crm.settings.index', [
            'settings' => $settings,
            'branchId' => $branchId,
            'branches' => Branch::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'companyDefault' => $companyDefault,
            'slaPolicies' => SlaPolicy::where('company_id', $companyId)->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $branchId = $request->input('branch_id') ?: null;

        $data = $request->validate([
            'default_currency' => ['required', 'string', 'max:10'],
            'fiscal_year_start_month' => ['required', 'integer', 'min:1', 'max:12'],
            'business_hours_start' => ['required', 'string', 'max:5'],
            'business_hours_end' => ['required', 'string', 'max:5'],
            'default_sla_policy_id' => ['nullable', 'exists:crm_sla_policies,id'],
            'auto_assign_leads' => ['nullable', 'boolean'],
            'pipeline_mode' => ['required', 'in:'.implode(',', array_keys(CrmSetting::PIPELINE_MODES))],
        ]) + ['auto_assign_leads' => $request->boolean('auto_assign_leads')];

        CrmSetting::updateOrCreate(['company_id' => $companyId, 'branch_id' => $branchId], $data);

        return redirect()->route('admin.crm.settings.index', $branchId ? ['branch_id' => $branchId] : [])->with('status', 'Settings saved.');
    }
}
