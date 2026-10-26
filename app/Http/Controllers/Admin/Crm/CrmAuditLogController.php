<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\AutomationRule;
use App\Models\Crm\CaseModel;
use App\Models\Crm\Lead;
use App\Models\Crm\ScheduledJob;
use Illuminate\View\View;

class CrmAuditLogController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;
        $events = collect();

        foreach (AutomationRule::where('company_id', $companyId)->whereNotNull('last_run_at')->get() as $rule) {
            $events->push(['when' => $rule->last_run_at, 'icon' => 'ti-bolt', 'description' => "Automation rule \"{$rule->name}\" ran ({$rule->run_count} total matches)."]);
        }

        foreach (ScheduledJob::where('company_id', $companyId)->whereNotNull('last_run_at')->get() as $job) {
            $events->push(['when' => $job->last_run_at, 'icon' => 'ti-calendar-time', 'description' => "Scheduled job \"{$job->name}\" ran — {$job->last_result}"]);
        }

        foreach (CaseModel::where('company_id', $companyId)->where('is_escalated', true)->get() as $case) {
            $events->push(['when' => $case->escalated_at, 'icon' => 'ti-arrow-up-circle', 'description' => "Case {$case->case_number} escalated to {$case->escalatedTo?->name}."]);
        }

        foreach (Lead::where('company_id', $companyId)->where('status', 'converted')->whereNotNull('converted_at')->get() as $lead) {
            $events->push(['when' => $lead->converted_at, 'icon' => 'ti-circle-check', 'description' => "Lead \"{$lead->name}\" converted to a contact."]);
        }

        return view('admin.crm.audit-logs.index', [
            'events' => $events->filter(fn ($e) => $e['when'])->sortByDesc('when')->values()->take(100),
        ]);
    }
}
