<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\AssessmentResult;
use App\Models\Certification;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\TrainingEnrollment;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LearningProgressController extends Controller
{
    protected const TABS = ['active', 'behind', 'done', 'none'];

    protected const SORTS = ['name', 'rate', 'completed', 'hours', 'certs', 'passed'];

    /** Employees who have left are not chased for training. */
    protected const INACTIVE = ['terminated', 'inactive'];

    protected const PER_PAGE = 25;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', TrainingEnrollment::class);

        $state = $this->state($request);
        $all = $this->rows();
        $filtered = $this->filtered($all, $state);
        $listed = $this->sorted($state['tab'] ? $filtered->where('state', $state['tab']) : $filtered, $state);
        $page = max(1, (int) (is_scalar($request->query('page')) ? $request->query('page') : 1));
        $withTraining = $all->where('assigned', '>', 0);

        return view('admin.hrm.learning.progress', [
            'rows' => (new LengthAwarePaginator($listed->forPage($page, self::PER_PAGE)->values(), $listed->count(), self::PER_PAGE, $page, ['path' => $request->url()]))->withQueryString(),
            'state' => $state,
            'employees' => $all->mapWithKeys(fn ($row) => [$row['employee']->id => $row['employee']->name]),
            'departments' => Department::query()->where('company_id', Company::current()->id)
                ->whereIn('id', $all->pluck('employee.department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            'counts' => ['all' => $filtered->count()] + collect(self::TABS)->mapWithKeys(fn ($tab) => [$tab => $filtered->where('state', $tab)->count()])->all(),
            'kpis' => [
                'people' => $all->count(),
                'learning' => $all->where('open', '>', 0)->count(),
                'open' => $all->sum('open'),
                'assigned' => $all->sum('assigned'),
                'completed' => $all->sum('completed'),
                // Everyone's trainings together, so one person with a single course doesn't count as much as one with ten.
                'rate' => $all->sum('assigned') > 0 ? (int) round($all->sum('completed') / $all->sum('assigned') * 100) : null,
                'hours' => (float) $all->sum('hours'),
                'none' => $all->where('state', 'none')->count(),
                'behind' => $all->where('state', 'behind')->count(),
                'overdue' => $all->sum('overdue'),
                'trained' => $withTraining->count(),
            ],
            'detail' => $state['employee_id'] ? $all->firstWhere('employee.id', $state['employee_id']) : null,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', TrainingEnrollment::class);

        $state = $this->state($request);
        $filtered = $this->filtered($this->rows(), $state);
        $rows = $this->sorted($state['tab'] ? $filtered->where('state', $state['tab']) : $filtered, $state);
        // A cell starting with = + - @ would run as a formula when the file is opened in a spreadsheet.
        $safe = fn ($value) => is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
        $labels = ['active' => 'In training', 'behind' => 'Behind', 'done' => 'All completed', 'none' => 'Nothing assigned'];

        return response()->streamDownload(function () use ($rows, $safe, $labels) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Employee', 'Employee code', 'Department', 'Status', 'Trainings assigned', 'Completed', 'In progress', 'Not started', 'Overdue', 'Dropped', 'Completion %', 'Training hours', 'Valid certifications', 'Expiring soon', 'Assessments taken', 'Assessments passed', 'Average score %', 'Last completed']);

            foreach ($rows as $row) {
                fputcsv($out, array_map($safe, [
                    $row['employee']->name, $row['employee']->employee_code, $row['employee']->department?->name, $labels[$row['state']],
                    $row['assigned'], $row['completed'], $row['in_progress'], $row['open'] - $row['in_progress'], $row['overdue'], $row['dropped'],
                    $row['rate'] ?? '', $row['hours'], $row['certs'], $row['certs_soon'], $row['taken'], $row['passed'], $row['average'] ?? '', $row['last']?->toDateString() ?? '',
                ]));
            }

            fclose($out);
        }, 'learning-progress-'.today()->toDateString().'.csv', ['Content-Type' => 'text/csv']);
    }

    protected function state(Request $request): array
    {
        // Anything that isn't a plain value (e.g. ?employee_id[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $id = fn (string $key) => ctype_digit($text($key)) ? (int) $text($key) : null;

        return [
            'tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : '',
            'employee_id' => $id('employee_id'),
            'department_id' => $id('department_id'),
            'search' => trim(mb_substr($text('search'), 0, 80)),
            'sort' => in_array($text('sort'), self::SORTS, true) ? $text('sort') : 'name',
            'dir' => $text('dir') === 'asc' || ($text('dir') !== 'desc' && ! in_array($text('sort'), array_diff(self::SORTS, ['name']), true)) ? 'asc' : 'desc',
        ];
    }

    protected function filtered(Collection $rows, array $state): Collection
    {
        $needle = mb_strtolower($state['search']);

        return $rows
            ->when($state['employee_id'], fn ($c, $employeeId) => $c->where('employee.id', $employeeId))
            ->when($state['department_id'], fn ($c, $departmentId) => $c->where('employee.department_id', $departmentId))
            ->when($needle !== '', fn ($c) => $c->filter(fn ($row) => str_contains(mb_strtolower($row['employee']->name.' '.$row['employee']->employee_code), $needle)))
            ->values();
    }

    protected function sorted(Collection $rows, array $state): Collection
    {
        if ($state['sort'] === 'name') {
            return $rows->sortBy(fn ($row) => mb_strtolower($row['employee']->name), SORT_REGULAR, $state['dir'] === 'desc')->values();
        }

        // People with nothing to measure always go last, whichever way the column is sorted.
        return $rows->sort(function ($a, $b) use ($state) {
            [$x, $y] = [$a[$state['sort']], $b[$state['sort']]];

            if ($x === null || $y === null) {
                return ($x === null) <=> ($y === null);
            }

            return ($state['dir'] === 'asc' ? $x <=> $y : $y <=> $x) ?: strcasecmp($a['employee']->name, $b['employee']->name);
        })->values();
    }

    /** One line per current employee: training, certifications and assessments rolled up. */
    protected function rows(): Collection
    {
        $companyId = Company::current()->id;
        $employees = Employee::query()
            ->where('company_id', $companyId)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->whereNotIn('status', self::INACTIVE)
            ->with('department:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'employee_code', 'avatar', 'department_id', 'designation', 'status']);
        $ids = $employees->pluck('id');

        $enrollments = TrainingEnrollment::query()->where('company_id', $companyId)->whereIn('employee_id', $ids)
            ->with(['trainingSession:id,course_id,start_date,end_date,status', 'trainingSession.course:id,title,duration_hours'])
            ->orderByDesc('assigned_date')->get()->groupBy('employee_id');
        $certifications = Certification::query()->where('company_id', $companyId)->whereIn('employee_id', $ids)->orderByDesc('issue_date')->get()->groupBy('employee_id');
        $results = AssessmentResult::query()->where('company_id', $companyId)->whereIn('employee_id', $ids)
            ->with('assessment:id,title,max_score,passing_score')->orderByDesc('attempted_date')->get()->groupBy('employee_id');
        $soon = today()->addDays(CertificationController::SOON_DAYS);

        return $employees->map(function (Employee $employee) use ($enrollments, $certifications, $results, $soon) {
            $mine = $enrollments->get($employee->id, collect());
            $certs = $certifications->get($employee->id, collect());
            $scores = $results->get($employee->id, collect());

            // Dropped trainings don't count against anyone: they are not expected to finish them.
            $counted = $mine->where('status', '!=', 'dropped');
            $completed = $counted->where('status', 'completed');
            $open = $counted->whereIn('status', ['assigned', 'in_progress']);
            // Still open although the session is over, closed or cancelled.
            $overdue = $open->filter(fn (TrainingEnrollment $e) => $e->trainingSession && (in_array($e->trainingSession->status, ['completed', 'cancelled'], true) || $e->trainingSession->end_date->lt(today())));
            // A certificate counts by its date, even if nobody has opened the Certifications page to mark it expired.
            $valid = $certs->filter(fn (Certification $c) => $c->status === 'active' && (! $c->expiry_date || $c->expiry_date->gte(today())));
            $percents = $scores->map(fn (AssessmentResult $r) => (float) ($r->assessment?->max_score ?? 0) > 0 ? (float) $r->score / (float) $r->assessment->max_score * 100 : null)->filter(fn ($p) => $p !== null);

            return [
                'employee' => $employee,
                'state' => match (true) {
                    $counted->isEmpty() => 'none',
                    $overdue->isNotEmpty() => 'behind',
                    $open->isNotEmpty() => 'active',
                    default => 'done',
                },
                'assigned' => $counted->count(),
                'completed' => $completed->count(),
                'in_progress' => $counted->where('status', 'in_progress')->count(),
                'open' => $open->count(),
                'overdue' => $overdue->count(),
                'dropped' => $mine->count() - $counted->count(),
                'rate' => $counted->isNotEmpty() ? (int) round($completed->count() / $counted->count() * 100) : null,
                'hours' => (float) $completed->sum(fn (TrainingEnrollment $e) => (float) ($e->trainingSession?->course?->duration_hours ?? 0)),
                'certs' => $valid->count(),
                'certs_soon' => $valid->filter(fn (Certification $c) => $c->expiry_date && $c->expiry_date->lte($soon))->count(),
                'certs_expired' => $certs->count() - $valid->count(),
                'taken' => $scores->count(),
                'passed' => $scores->where('status', 'pass')->count(),
                'average' => $percents->isNotEmpty() ? (int) round($percents->avg()) : null,
                'last' => $completed->max('completed_date'),
                'enrollments' => $mine,
                'certifications' => $certs,
                'results' => $scores,
            ];
        });
    }
}
