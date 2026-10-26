<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\LeavePolicySetting;
use App\Models\WorkingHour;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WorkingHourController extends Controller
{
    public function edit(): View
    {
        abort_unless(auth()->user()->can('page_WorkingHours'), 403);

        $company = Company::current();
        $branchId = BranchContext::currentBranchId();
        WorkingHour::ensureDefaultsFor($company, $branchId);

        $days = $company->workingHours()
            ->where('branch_id', $branchId)
            ->orderByRaw('CASE WHEN day_of_week = 0 THEN 7 ELSE day_of_week END')
            ->get();

        return view('admin.settings.organization.working-hours.edit', [
            'days' => $days,
            'branchName' => $branchId ? Branch::whereKey($branchId)->value('name') : null,
            'leavePolicyActive' => LeavePolicySetting::where('company_id', $company->id)
                ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'))
                ->where('exclude_weekends', true)
                ->exists(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->can('page_WorkingHours'), 403);

        $company = Company::current();
        $branchId = BranchContext::currentBranchId();

        $data = $request->validate([
            'days' => ['required', 'array'],
            'days.*.is_working_day' => ['nullable'],
            'days.*.start_time' => ['nullable', 'date_format:H:i'],
            'days.*.end_time' => ['nullable', 'date_format:H:i', 'after:days.*.start_time'],
        ], [
            'days.*.end_time.after' => 'The end time must be after the start time.',
        ]);

        $days = collect($data['days'])->only(array_keys(WorkingHour::DAYS));
        $errors = [];
        $working = 0;
        foreach ($days as $dayOfWeek => $row) {
            if (! $request->boolean("days.{$dayOfWeek}.is_working_day")) {
                continue;
            }
            $working++;
            if (empty($row['start_time']) || empty($row['end_time'])) {
                $errors["days.{$dayOfWeek}.start_time"] = WorkingHour::DAYS[$dayOfWeek].' is a working day, so it needs a start and end time.';
            }
        }
        if (! $working) {
            $errors['days'] = 'Keep at least one working day.';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        WorkingHour::ensureDefaultsFor($company, $branchId);
        foreach ($days as $dayOfWeek => $row) {
            WorkingHour::query()
                ->where('company_id', $company->id)
                ->where('branch_id', $branchId)
                ->where('day_of_week', $dayOfWeek)
                ->update([
                    'is_working_day' => $request->boolean("days.{$dayOfWeek}.is_working_day"),
                    'start_time' => $row['start_time'] ?? null,
                    'end_time' => $row['end_time'] ?? null,
                ]);
        }

        return redirect()->route('admin.working-hours.edit')->with('status', "Working hours saved - {$working} working ".($working === 1 ? 'day' : 'days').' a week.');
    }
}
