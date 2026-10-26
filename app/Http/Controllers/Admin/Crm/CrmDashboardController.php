<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Activity;
use App\Models\Crm\Lead;
use App\Models\Crm\Opportunity;
use App\Models\Crm\Quota;
use App\Models\Employee;
use Illuminate\View\View;

class CrmDashboardController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        $leads = Lead::where('company_id', $companyId)->get(['id', 'status', 'assigned_to_employee_id', 'score', 'created_at']);
        $openOpportunities = Opportunity::where('company_id', $companyId)->where('status', 'open')->get(['id', 'amount']);
        $wonThisMonth = Opportunity::where('company_id', $companyId)->where('status', 'won')
            ->whereYear('closed_at', now()->year)->whereMonth('closed_at', now()->month)->sum('amount');

        $followUpsDueToday = Activity::where('company_id', $companyId)->whereIn('type', ['task', 'call', 'meeting'])
            ->where('status', 'pending')->whereDate('due_at', now()->toDateString())->count();

        $overdueActivities = Activity::where('company_id', $companyId)->whereIn('type', ['task', 'call', 'meeting'])
            ->where('status', 'pending')->whereNotNull('due_at')->where('due_at', '<', now())->count();

        $recentActivities = Activity::where('company_id', $companyId)->with('owner')->orderByDesc('created_at')->limit(8)->get();

        $monthQuotas = Quota::where('company_id', $companyId)->where('period', 'month')
            ->whereYear('period_start', now()->year)->whereMonth('period_start', now()->month)
            ->with(['employee', 'team'])->get();

        $targetVsAchievement = $monthQuotas->map(fn ($quota) => [
            'label' => $quota->employee?->name ?? $quota->team?->name ?? 'Unknown',
            'target' => (float) $quota->target_amount,
            'achieved' => $quota->achievedAmount(),
        ]);

        $employees = Employee::where('company_id', $companyId)->get();
        $wonOpps = Opportunity::where('company_id', $companyId)->where('status', 'won')
            ->whereYear('closed_at', now()->year)->whereMonth('closed_at', now()->month)->get();

        $teamPerformance = $employees->map(function ($employee) use ($wonOpps) {
            $owned = $wonOpps->where('owner_employee_id', $employee->id);

            return ['employee' => $employee, 'count' => $owned->count(), 'amount' => (float) $owned->sum('amount')];
        })->filter(fn ($row) => $row['count'] > 0)->sortByDesc('amount')->values()->take(5);

        return view('admin.crm.dashboard', [
            'total' => $leads->count(),
            'newLeads' => $leads->where('status', 'new')->count(),
            'qualified' => $leads->where('status', 'qualified')->count(),
            'converted' => $leads->where('status', 'converted')->count(),
            'conversionRate' => $leads->count() > 0 ? round($leads->where('status', 'converted')->count() / $leads->count() * 100, 1) : 0,
            'openOpportunitiesCount' => $openOpportunities->count(),
            'pipelineValue' => (float) $openOpportunities->sum('amount'),
            'wonThisMonth' => (float) $wonThisMonth,
            'followUpsDueToday' => $followUpsDueToday,
            'overdueActivities' => $overdueActivities,
            'recentActivities' => $recentActivities,
            'targetVsAchievement' => $targetVsAchievement,
            'teamPerformance' => $teamPerformance,
            'byStatus' => $leads->countBy('status'),
        ]);
    }
}
