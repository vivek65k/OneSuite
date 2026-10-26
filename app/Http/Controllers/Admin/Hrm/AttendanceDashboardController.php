<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Support\Attendance;
use Illuminate\View\View;

class AttendanceDashboardController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', AttendanceRecord::class);

        $today = now()->toDateString();

        $todayCounts = Attendance::records()
            ->whereDate('date', $today)
            ->toBase()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count) => (int) $count);

        $headcount = Attendance::employees()->whereNotIn('status', ['terminated', 'inactive'])->count();
        $marked = (int) $todayCounts->sum();
        $attending = $todayCounts->get('present', 0) + $todayCounts->get('half_day', 0) + $todayCounts->get('wfh', 0);

        // Last 7 days, per day and status, for the trend chart. DATE() because
        // stored values carry a time component (see AttendanceRecord::upsertForDate).
        $from = now()->subDays(6)->startOfDay();
        $trendRows = Attendance::records()
            ->whereDate('date', '>=', $from->toDateString())
            ->toBase()
            ->selectRaw('DATE(date) as day, status, COUNT(*) as aggregate')
            ->groupByRaw('DATE(date), status')
            ->get();

        $trend = collect(range(0, 6))->map(function ($offset) use ($from, $trendRows) {
            $day = $from->copy()->addDays($offset);
            $rows = $trendRows->filter(fn ($row) => substr((string) $row->day, 0, 10) === $day->toDateString());

            return [
                'date' => $day,
                'counts' => $rows->mapWithKeys(fn ($row) => [$row->status => (int) $row->aggregate])->all(),
                'total' => (int) $rows->sum('aggregate'),
            ];
        });

        return view('admin.hrm.attendance.dashboard', [
            'headcount' => $headcount,
            'todayCounts' => $todayCounts,
            'marked' => $marked,
            'notMarked' => max(0, $headcount - $marked),
            'attendanceRate' => $marked ? (int) round($attending / $marked * 100) : null,
            'trend' => $trend,
            'trendMax' => max(1, (int) $trend->max('total')),
            'navCounts' => Attendance::navCounts(),
            'recent' => Attendance::records()
                ->with('employee.department')
                ->whereDate('date', $today)
                ->latest('updated_at')
                ->limit(8)
                ->get(),
            'absentToday' => Attendance::records()
                ->with('employee')
                ->whereDate('date', $today)
                ->whereIn('status', ['absent', 'on_leave'])
                ->limit(6)
                ->get(),
        ]);
    }
}
