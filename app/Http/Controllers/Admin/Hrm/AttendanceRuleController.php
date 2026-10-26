<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRuleSetting;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Shift;
use App\Models\ShiftRoster;
use App\Support\Attendance;
use App\Support\BranchContext;
use App\Support\Overtime;
use App\Support\ShiftTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceRuleController extends Controller
{
    /** Column defaults from the migration, used before anything has been saved. */
    protected const DEFAULTS = [
        'late_grace_minutes' => 0,
        'half_day_threshold_hours' => 4,
        'full_day_threshold_hours' => 8,
        'auto_mark_absent_if_no_checkin' => false,
        'weekend_overtime_multiplier' => 1,
    ];

    /** How many days of attendance the impact preview looks back over. */
    protected const SAMPLE_DAYS = 30;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AttendanceRuleSetting::class);

        $companyId = Company::current()->id;
        $branchId = BranchContext::currentBranchId();

        // Viewing doesn't create a row; the first save does.
        $setting = AttendanceRuleSetting::firstOrNew(['company_id' => $companyId, 'branch_id' => $branchId]);
        if (! $setting->exists) {
            $setting->fill(self::DEFAULTS);
        }

        return view('admin.hrm.settings.attendance-rules', [
            'setting' => $setting,
            'canEdit' => $request->user()->can('update', $setting),
            'branchName' => $branchId ? Branch::whereKey($branchId)->value('name') : null,
            'sample' => $this->sample(),
            'sampleDays' => self::SAMPLE_DAYS,
            'shifts' => Shift::query()
                ->where('company_id', $companyId)
                ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
                ->where('is_active', true)
                ->orderBy('start_time')
                ->get(),
            'overtimeRules' => Overtime::activeRules()->count(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $setting = AttendanceRuleSetting::firstOrNew(['company_id' => Company::current()->id, 'branch_id' => BranchContext::currentBranchId()]);

        $this->authorize('update', $setting);

        $data = $request->validate([
            'late_grace_minutes' => ['required', 'integer', 'min:0', 'max:180'],
            'half_day_threshold_hours' => ['required', 'numeric', 'min:0.5', 'max:23.5'],
            'full_day_threshold_hours' => ['required', 'numeric', 'gt:half_day_threshold_hours', 'max:24'],
            'weekend_overtime_multiplier' => ['required', 'numeric', 'min:1', 'max:10'],
        ], [
            'full_day_threshold_hours.gt' => 'A full day must need more hours than a half day.',
            'half_day_threshold_hours.min' => 'A half day needs at least 30 minutes.',
            'weekend_overtime_multiplier.min' => 'Weekend overtime can\'t be paid below the normal rate (1×).',
        ]);

        $data['auto_mark_absent_if_no_checkin'] = $request->boolean('auto_mark_absent_if_no_checkin');

        $setting->fill($data)->save();

        return redirect()->route('admin.attendance-rules.index')->with('status', 'Attendance rules saved.');
    }

    /**
     * Recent attendance as [minutes after shift start, hours worked, weekend?]
     * so the page can preview how the thresholds would classify real days.
     *
     * @return array<int, array{l: ?int, w: ?float, we: bool}>
     */
    protected function sample(): array
    {
        $records = Attendance::records()
            ->whereDate('date', '>=', today()->subDays(self::SAMPLE_DAYS))
            ->whereDate('date', '<', today())
            ->whereNotNull('check_in_time')
            ->latest('date')
            ->limit(3000)
            ->get(['employee_id', 'date', 'check_in_time', 'check_out_time', 'work_hours']);

        if ($records->isEmpty()) {
            return [];
        }

        $starts = ShiftRoster::query()
            ->whereIn('employee_id', $records->pluck('employee_id')->unique())
            ->whereIn(DB::raw('DATE(date)'), $records->map(fn ($r) => $r->date->toDateString())->unique())
            ->with('shift:id,start_time')
            ->get()
            ->mapWithKeys(fn ($r) => [$r->employee_id.'|'.$r->date->toDateString() => $r->shift?->start_time]);

        return $records->map(function ($record) use ($starts) {
            $start = $starts->get($record->employee_id.'|'.$record->date->toDateString());
            $late = null;
            if ($start) {
                $late = ShiftTime::toMinutes($record->check_in_time) - ShiftTime::toMinutes($start);
                $late = (($late + 720) % 1440 + 1440) % 1440 - 720; // shortest way round the clock
            }

            return ['l' => $late, 'w' => Overtime::worked($record), 'we' => $record->date->isWeekend()];
        })->values()->all();
    }
}
