<?php

namespace App\Http\Controllers\Admin\Mss;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Support\AttendanceMonth;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeamAttendanceController extends Controller
{
    use ResolvesCurrentEmployee;

    protected const TABS = ['attention', 'worked', 'off'];

    /** People who have left drop off the board (their past months still show when picked by name). */
    protected const INACTIVE = ['terminated', 'inactive'];

    public function index(Request $request): View
    {
        [$state, $month, $start, $end, $people, $focus, $everyone] = $this->resolve($request);
        $thisMonth = now()->format('Y-m');

        // One reading per person; the board, the totals and "today" all come from it.
        $board = $people->map(function (Employee $e) use ($start, $end) {
            $days = AttendanceMonth::days($e, $start, $end);

            return ['employee' => $e, 'days' => $days->keyBy(fn ($d) => $d['date']->toDateString()), 'summary' => AttendanceMonth::summary($days)];
        })->values();

        $today = today()->toDateString();
        // Where everyone is right now, only meaningful on the current month.
        $now = $month === $thisMonth ? $board->map(fn ($row) => ['employee' => $row['employee'], 'day' => $row['days'][$today] ?? null])->filter(fn ($r) => $r['day'])->values() : collect();
        $nowKind = fn (array $kinds) => $now->filter(fn ($r) => in_array($r['day']['kind'], $kinds, true));
        $working = (int) $board->sum(fn ($row) => $row['summary']['working']);
        $worked = (float) $board->sum(fn ($row) => $row['summary']['worked']);

        // Picking one person shows their days as a list underneath.
        $focusRow = $focus ? $board->first(fn ($row) => $row['employee']->id === $focus->id) : null;
        $tabbed = fn (Collection $rows, string $tab) => match ($tab) {
            'attention' => $rows->where('attention', '!=', null),
            'worked' => $rows->whereIn('kind', AttendanceMonth::WORKED),
            'off' => $rows->whereIn('kind', ['on_leave', 'holiday', 'weekend']),
            default => $rows,
        };
        $list = $focusRow ? $focusRow['days']->values()->filter(fn ($d) => $d['date']->lte(today()))->sortByDesc(fn ($d) => $d['date']->timestamp)->values() : collect();

        return view('admin.mss.attendance', [
            'state' => $state,
            'month' => $month,
            'start' => $start,
            'isCurrent' => $month === $thisMonth,
            'board' => $board,
            'everyone' => $everyone,
            'hasIndirect' => $everyone->contains(fn (Employee $e) => ! $e->getAttribute('is_direct')),
            'focus' => $focus,
            'rows' => $tabbed($list, $state['tab'])->values(),
            'counts' => ['all' => $list->count()] + collect(self::TABS)->mapWithKeys(fn ($tab) => [$tab => $tabbed($list, $tab)->count()])->all(),
            'now' => $now,
            'kpis' => [
                'people' => $board->count(),
                'in' => $nowKind(['present', 'half_day'])->count(),
                'wfh' => $nowKind(['wfh'])->count(),
                'on_leave' => $nowKind(['on_leave'])->count(),
                // A working day today with nothing marked yet.
                'not_in' => $now->filter(fn ($r) => in_array($r['day']['kind'], ['upcoming', 'absent'], true) && ($r['day']['working'] || $r['day']['kind'] === 'absent'))->count(),
                'rate' => $working ? (int) round($worked / $working * 100) : null,
                'worked' => $worked,
                'working' => $working,
                'late' => (int) $board->sum(fn ($row) => $row['summary']['late']),
                'late_people' => $board->filter(fn ($row) => $row['summary']['late'] > 0)->count(),
                'attention' => (int) $board->sum(fn ($row) => $row['summary']['attention']),
                'unaccounted' => (int) $board->sum(fn ($row) => $row['summary']['absent'] + $row['summary']['missing']),
            ],
        ]);
    }

    /** The month on screen as a spreadsheet: one line per person. */
    public function export(Request $request): StreamedResponse
    {
        [, $month, $start, $end, $people] = $this->resolve($request);

        // Stops a spreadsheet from running a cell as a formula.
        $safe = fn ($value) => is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;

        return response()->streamDownload(function () use ($people, $start, $end, $safe) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Employee', 'Code', 'Designation', 'Working days so far', 'Days worked', 'Work from home', 'Half days', 'On leave', 'Absent', 'Not marked', 'Late arrivals', 'Late minutes', 'Hours worked']);
            foreach ($people as $e) {
                $s = AttendanceMonth::summary(AttendanceMonth::days($e, $start, $end));
                fputcsv($out, array_map($safe, [$e->name, $e->employee_code, $e->designation, $s['working'], $s['worked'], $s['wfh'], $s['half'], $s['leave'], $s['absent'], $s['missing'], $s['late'], $s['late_minutes'], $s['hours']]));
            }
            fclose($out);
        }, 'team-attendance-'.$month.'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * The filters from the URL and the people they select. Only ever people below the
     * signed-in manager in the reporting line.
     *
     * @return array{0: array<string, mixed>, 1: string, 2: Carbon, 3: Carbon, 4: Collection, 5: ?Employee, 6: Collection}
     */
    protected function resolve(Request $request): array
    {
        $manager = $this->currentEmployee();
        $teamIds = $this->teamEmployeeIds();

        // Anything that isn't a plain value (e.g. ?month[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $thisMonth = now()->format('Y-m');
        $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $text('month')) && $text('month') >= '2000-01' ? $text('month') : $thisMonth;
        // There is no attendance in the future.
        $month = min($month, $thisMonth);
        $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfDay();
        $end = $start->copy()->endOfMonth()->startOfDay();

        // The team is the manager's own reporting line, read by id: it doesn't depend on what their role may browse.
        $everyone = Employee::withoutGlobalScopes()->where('company_id', $manager->company_id)->whereIn('id', $teamIds)
            ->with('department:id,name')->orderBy('name')->get()
            ->each(fn (Employee $e) => $e->setAttribute('is_direct', (int) $e->manager_id === (int) $manager->id));
        // A filter for someone outside the team is simply ignored.
        $focus = ctype_digit($text('employee_id')) ? $everyone->firstWhere('id', (int) $text('employee_id')) : null;
        $scope = $text('scope') === 'direct' ? 'direct' : '';

        $people = $everyone
            ->when($focus, fn (Collection $c) => $c->where('id', $focus->id))
            ->when(! $focus, fn (Collection $c) => $c->reject(fn (Employee $e) => in_array($e->status, self::INACTIVE, true)))
            ->when(! $focus && $scope === 'direct', fn (Collection $c) => $c->where('is_direct', true))
            ->values();

        $state = [
            'month' => $month === $thisMonth ? '' : $month,
            'employee_id' => $focus?->id,
            'scope' => $focus ? '' : $scope,
            'tab' => $focus && in_array($text('tab'), self::TABS, true) ? $text('tab') : '',
        ];

        return [$state, $month, $start, $end, $people, $focus, $everyone];
    }
}
