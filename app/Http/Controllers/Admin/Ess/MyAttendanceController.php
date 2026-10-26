<?php

namespace App\Http\Controllers\Admin\Ess;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Support\AttendanceMonth;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class MyAttendanceController extends Controller
{
    use ResolvesCurrentEmployee;

    protected const TABS = ['attention', 'worked', 'off'];

    public function index(Request $request): View
    {
        $employee = $this->currentEmployee();

        // Anything that isn't a plain value (e.g. ?month[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $thisMonth = now()->format('Y-m');
        $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $text('month')) && $text('month') >= '2000-01' ? $text('month') : $thisMonth;
        // There is no attendance in the future.
        $month = min($month, $thisMonth);
        $state = [
            'month' => $month === $thisMonth ? '' : $month,
            'tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : '',
        ];
        $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfDay();
        $end = $start->copy()->endOfMonth()->startOfDay();

        // The same day-by-day reading the manager sees on Team Attendance.
        $days = AttendanceMonth::days($employee, $start, $end);
        $past = $days->filter(fn ($d) => $d['date']->lte(today()));
        $tabbed = fn (Collection $rows, string $tab) => match ($tab) {
            'attention' => $rows->where('attention', '!=', null),
            'worked' => $rows->whereIn('kind', AttendanceMonth::WORKED),
            'off' => $rows->whereIn('kind', ['on_leave', 'holiday', 'weekend']),
            default => $rows,
        };
        // The list shows days that have happened, newest first.
        $list = $past->sortByDesc(fn ($d) => $d['date']->timestamp)->values();

        return view('admin.ess.attendance', [
            'employee' => $employee,
            'state' => $state,
            'month' => $month,
            'start' => $start,
            'isCurrent' => $month === $thisMonth,
            'days' => $days,
            'rows' => $tabbed($list, $state['tab'])->values(),
            'counts' => ['all' => $list->count()] + collect(self::TABS)->mapWithKeys(fn ($tab) => [$tab => $tabbed($list, $tab)->count()])->all(),
            'kpis' => AttendanceMonth::summary($days) + [
                'upcoming_leave' => $days->filter(fn ($d) => $d['date']->gt(today()) && $d['leave'] && $d['leave']->status === 'approved')->count(),
            ],
        ]);
    }
}
