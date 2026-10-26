<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\CustomReport;
use App\Models\Crm\ScheduledReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduledReportController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.crm.scheduled-reports.index', [
            'schedules' => ScheduledReport::where('company_id', $companyId)->with('customReport')->orderBy('next_run_at')->get(),
            'reports' => CustomReport::where('company_id', $companyId)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'custom_report_id' => ['nullable', 'exists:crm_custom_reports,id'],
            'frequency' => ['required', 'in:'.implode(',', array_keys(ScheduledReport::FREQUENCIES))],
            'recipients' => ['nullable', 'string', 'max:500'],
        ]);

        $schedule = new ScheduledReport($data + ['company_id' => Company::current()->id, 'is_active' => true]);
        $schedule->next_run_at = $schedule->computeNextRun();
        $schedule->save();

        return back()->with('status', 'Schedule created.');
    }

    public function toggle(ScheduledReport $scheduledReport): RedirectResponse
    {
        abort_unless($scheduledReport->company_id === Company::current()->id, 404);
        $scheduledReport->update(['is_active' => ! $scheduledReport->is_active]);

        return back()->with('status', $scheduledReport->is_active ? 'Schedule activated.' : 'Schedule paused.');
    }

    public function runNow(ScheduledReport $scheduledReport): RedirectResponse
    {
        abort_unless($scheduledReport->company_id === Company::current()->id, 404);
        $scheduledReport->update(['last_run_at' => now(), 'next_run_at' => $scheduledReport->computeNextRun()]);

        return back()->with('status', 'Report run triggered to: '.($scheduledReport->recipients ?: 'no recipients set'));
    }

    public function destroy(ScheduledReport $scheduledReport): RedirectResponse
    {
        abort_unless($scheduledReport->company_id === Company::current()->id, 404);
        $scheduledReport->delete();

        return back()->with('status', 'Schedule removed.');
    }
}
