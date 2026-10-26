<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\CaseModel;
use App\Models\Crm\Campaign;
use App\Models\Crm\Lead;
use App\Models\Crm\ScheduledJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduledJobController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.crm.scheduled-jobs.index', [
            'jobs' => ScheduledJob::where('company_id', $companyId)->orderBy('next_run_at')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'job_type' => ['required', 'in:'.implode(',', array_keys(ScheduledJob::JOB_TYPES))],
            'frequency' => ['required', 'in:'.implode(',', array_keys(ScheduledJob::FREQUENCIES))],
        ]);

        $job = new ScheduledJob($data + ['company_id' => Company::current()->id, 'is_active' => true]);
        $job->next_run_at = $job->computeNextRun();
        $job->save();

        return back()->with('status', 'Scheduled job created.');
    }

    public function toggle(ScheduledJob $scheduledJob): RedirectResponse
    {
        abort_unless($scheduledJob->company_id === Company::current()->id, 404);
        $scheduledJob->update(['is_active' => ! $scheduledJob->is_active]);

        return back()->with('status', $scheduledJob->is_active ? 'Job activated.' : 'Job paused.');
    }

    public function runNow(ScheduledJob $scheduledJob): RedirectResponse
    {
        abort_unless($scheduledJob->company_id === Company::current()->id, 404);
        $companyId = $scheduledJob->company_id;
        $result = 'No matching records.';

        if ($scheduledJob->job_type === 'recalculate_campaign_counts') {
            $campaigns = Campaign::where('company_id', $companyId)->get();
            $campaigns->each(fn ($c) => $c->recalculateCounts());
            $result = "Recalculated counts for {$campaigns->count()} campaign(s).";
        } elseif ($scheduledJob->job_type === 'close_stale_leads') {
            $count = Lead::where('company_id', $companyId)->where('status', 'new')
                ->whereNull('assigned_to_employee_id')->where('created_at', '<=', now()->subDays(60))
                ->update(['status' => 'unqualified']);
            $result = "Closed {$count} stale unassigned lead(s).";
        } elseif ($scheduledJob->job_type === 'sync_case_sla') {
            $count = CaseModel::where('company_id', $companyId)->whereNotIn('status', ['resolved', 'closed'])
                ->whereNotNull('resolution_due_at')->where('resolution_due_at', '<', now())->count();
            $result = "{$count} case(s) currently breaching SLA.";
        }

        $scheduledJob->update(['last_run_at' => now(), 'next_run_at' => $scheduledJob->computeNextRun(), 'last_result' => $result]);

        return back()->with('status', $result);
    }

    public function destroy(ScheduledJob $scheduledJob): RedirectResponse
    {
        abort_unless($scheduledJob->company_id === Company::current()->id, 404);
        $scheduledJob->delete();

        return back()->with('status', 'Scheduled job removed.');
    }
}
