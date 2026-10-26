<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Activity;
use App\Models\Crm\CaseModel;
use App\Models\Crm\Lead;
use App\Models\Crm\Opportunity;
use Illuminate\View\View;

class AiNextBestActionController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;
        $recommendations = collect();

        $leads = Lead::where('company_id', $companyId)->whereNotIn('status', ['converted', 'unqualified'])->get();
        foreach ($leads as $lead) {
            if (! $lead->assigned_to_employee_id) {
                $recommendations->push(['subject' => $lead, 'type' => 'Lead', 'action' => 'Assign owner', 'reason' => 'Unassigned lead', 'priority' => 3]);
            } elseif ($lead->score >= 70 && $lead->status === 'new') {
                $recommendations->push(['subject' => $lead, 'type' => 'Lead', 'action' => 'Call now', 'reason' => 'High score, not yet contacted', 'priority' => 1]);
            } elseif ($lead->created_at->diffInDays(now()) >= 7 && $lead->status === 'new') {
                $recommendations->push(['subject' => $lead, 'type' => 'Lead', 'action' => 'Follow up', 'reason' => 'No contact in 7+ days', 'priority' => 2]);
            }
        }

        $opportunities = Opportunity::where('company_id', $companyId)->where('status', 'open')->with('stage')->get();
        foreach ($opportunities as $opportunity) {
            $recentActivity = Activity::where('company_id', $companyId)->where('related_type', Opportunity::class)
                ->where('related_id', $opportunity->id)->where('created_at', '>=', now()->subDays(14))->exists();
            if (! $recentActivity) {
                $recommendations->push(['subject' => $opportunity, 'type' => 'Opportunity', 'action' => 'Re-engage', 'reason' => 'No activity in 14+ days', 'priority' => 1]);
            } elseif ($opportunity->expected_close_date && $opportunity->expected_close_date->isPast()) {
                $recommendations->push(['subject' => $opportunity, 'type' => 'Opportunity', 'action' => 'Update close date', 'reason' => 'Close date has passed', 'priority' => 2]);
            }
        }

        $cases = CaseModel::where('company_id', $companyId)->whereNotIn('status', ['resolved', 'closed'])->get();
        foreach ($cases as $case) {
            if ($case->is_overdue) {
                $recommendations->push(['subject' => $case, 'type' => 'Case', 'action' => 'Resolve or escalate', 'reason' => 'SLA breached', 'priority' => 1]);
            }
        }

        return view('admin.crm.ai-next-best-action.index', [
            'recommendations' => $recommendations->sortBy('priority')->values(),
        ]);
    }
}
