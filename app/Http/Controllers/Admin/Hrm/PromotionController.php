<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Appraisal;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeePromotion;
use App\Models\PromotionRequest;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PromotionController extends Controller
{
    /** People who have left can't be promoted. */
    protected const INACTIVE = ['terminated', 'inactive'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Employee::class);

        // Anything that isn't a plain value (e.g. ?search[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $id = fn (string $key) => ctype_digit($text($key)) ? (int) $text($key) : null;
        $state = [
            'employee_id' => $id('employee_id'),
            'department_id' => $id('department_id'),
            'year' => $id('year'),
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $promotions = $this->scoped()
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['department_id'], fn ($q, $departmentId) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $departmentId)))
            ->when($state['year'], fn ($q, $year) => $q->whereYear('promotion_date', $year))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->where(fn ($w) => $w->where('to_designation', 'like', $like)->orWhere('from_designation', 'like', $like)
                    ->orWhereHas('employee', fn ($e) => $e->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('employee_code', 'like', $like))));
            })
            ->with('employee.department:id,name')
            ->orderByDesc('promotion_date')->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $all = $this->scoped()->orderBy('promotion_date')->orderBy('id')->get(['id', 'employee_id', 'from_designation', 'to_designation', 'promotion_date']);
        $people = $this->employeeQuery()->with('department:id,name')->orderBy('name')
            ->get(['id', 'name', 'employee_code', 'avatar', 'department_id', 'designation', 'joining_date', 'status']);
        $active = $people->whereNotIn('status', self::INACTIVE)->values();
        $thisYear = $all->filter(fn (EmployeePromotion $p) => $p->promotion_date->year === today()->year);
        $lastYear = $all->filter(fn (EmployeePromotion $p) => $p->promotion_date->gte(today()->subYear()));
        $latestByEmployee = $all->groupBy('employee_id')->map->last();
        $canPromote = $request->user()->can('update_employee');

        return view('admin.hrm.performance.promotions.index', [
            'promotions' => $promotions,
            'state' => $state,
            'employees' => $people->pluck('name', 'id'),
            'departments' => Department::query()->where('company_id', Company::current()->id)
                ->whereIn('id', $people->pluck('department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            'years' => $all->map(fn (EmployeePromotion $p) => $p->promotion_date->year)->unique()->sortDesc()->values(),
            'total' => $all->count(),
            // Only a person's most recent promotion can be undone; earlier ones have later roles built on them.
            'latestIds' => $latestByEmployee->pluck('id')->flip(),
            'kpis' => [
                'this_year' => $thisYear->count(),
                'people_this_year' => $thisYear->pluck('employee_id')->unique()->count(),
                'rate' => $active->isNotEmpty() ? (int) round($lastYear->pluck('employee_id')->unique()->intersect($active->pluck('id'))->count() / $active->count() * 100) : null,
                'never' => $active->whereNotIn('id', $all->pluck('employee_id')->unique())->count(),
                'active' => $active->count(),
                'requests' => $request->user()->can('viewAny', PromotionRequest::class)
                    ? PromotionRequest::query()->where('company_id', Company::current()->id)->where('status', 'pending')->whereIn('employee_id', $people->pluck('id'))->count()
                    : 0,
            ],
            'canPromote' => $canPromote,
            'formData' => $canPromote ? $this->formData($active, $latestByEmployee) : [],
            // Titles already in use, offered as suggestions so "Sr. Engineer" and "Senior Engineer" don't both appear.
            'designations' => $people->pluck('designation')->merge($all->pluck('to_designation'))->filter()->map(fn ($d) => trim($d))->unique()->sort()->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;

        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $companyId)->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))],
            'from_designation' => ['nullable', 'string', 'max:255'],
            'to_designation' => ['required', 'string', 'max:255'],
            'promotion_date' => ['required', 'date', 'after_or_equal:2000-01-01', 'before_or_equal:'.today()->toDateString()],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'promotion_date.before_or_equal' => 'A promotion can\'t be dated in the future. Record it on the day it takes effect, because the designation changes straight away.',
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        $this->authorize('update', $employee);

        if (in_array($employee->status, self::INACTIVE, true)) {
            throw ValidationException::withMessages(['employee_id' => $employee->name.' is no longer with the company.']);
        }

        $date = Carbon::parse($data['promotion_date'])->startOfDay();
        $to = trim($data['to_designation']);

        if ($employee->joining_date && $date->lt($employee->joining_date->copy()->startOfDay())) {
            throw ValidationException::withMessages(['promotion_date' => $employee->name.' joined on '.$employee->joining_date->format('M j, Y').'. A promotion can\'t be dated before that.']);
        }

        $history = EmployeePromotion::query()->where('employee_id', $employee->id)->orderBy('promotion_date')->orderBy('id')->get();

        if ($history->contains(fn (EmployeePromotion $p) => $p->promotion_date->isSameDay($date))) {
            throw ValidationException::withMessages(['promotion_date' => $employee->name.' already has a promotion dated '.$date->format('M j, Y').'.']);
        }

        // The newest promotion moves the person on from the title they hold now. An older, back-dated
        // one is only a history entry: it must not overwrite today's designation.
        $latest = $history->last();
        $isLatest = ! $latest || $date->gt($latest->promotion_date);
        $from = $isLatest ? trim((string) $employee->designation) : trim((string) ($data['from_designation'] ?? ''));

        if (! $isLatest && $from === '') {
            throw ValidationException::withMessages(['from_designation' => 'This is dated before '.$employee->name.'\'s latest promotion ('.$latest->promotion_date->format('M j, Y').'), so it is a history entry. Enter the designation they held before it.']);
        }

        if (mb_strtolower($from) === mb_strtolower($to)) {
            throw ValidationException::withMessages(['to_designation' => $employee->name.' is already "'.$to.'". Enter the new designation.']);
        }

        DB::transaction(function () use ($employee, $from, $to, $date, $data, $isLatest) {
            $employee->promotions()->create([
                'from_designation' => $from !== '' ? $from : null,
                'to_designation' => $to,
                'promotion_date' => $date->toDateString(),
                'notes' => filled($data['notes'] ?? null) ? trim($data['notes']) : null,
            ]);

            if ($isLatest) {
                $employee->update(['designation' => $to]);
            }
        });

        return redirect()->route('admin.promotions.index')->with('status', $isLatest
            ? $employee->name.' promoted'.($from !== '' ? ' from '.$from : '').' to '.$to.'. Their designation is updated.'
            : 'Earlier promotion added to '.$employee->name.'\'s history. Their current designation is unchanged.');
    }

    /** Undo a promotion recorded by mistake. Only the person's latest one, and the title goes back with it. */
    public function destroy(EmployeePromotion $promotion): RedirectResponse
    {
        $employee = $this->employeeQuery()->whereKey($promotion->employee_id)->first();
        abort_unless($employee, 404);
        $this->authorize('update', $employee);

        $later = EmployeePromotion::query()->where('employee_id', $employee->id)
            ->where(fn ($q) => $q->whereDate('promotion_date', '>', $promotion->promotion_date)
                ->orWhere(fn ($w) => $w->whereDate('promotion_date', $promotion->promotion_date)->where('id', '>', $promotion->id)))
            ->exists();

        if ($later) {
            return back()->withErrors(['promotion' => 'A later promotion builds on this one, so it can\'t be undone. Undo the later one first.']);
        }

        // Put the old title back only if the person still holds the one this promotion gave them.
        $revert = filled($promotion->from_designation) && mb_strtolower(trim((string) $employee->designation)) === mb_strtolower(trim($promotion->to_designation));

        DB::transaction(function () use ($promotion, $employee, $revert) {
            $promotion->delete();

            if ($revert) {
                $employee->update(['designation' => $promotion->from_designation]);
            }
        });

        return back()->with('status', 'Promotion undone.'.($revert ? ' '.$employee->name.' is '.$promotion->from_designation.' again.' : ' '.$employee->name.'\'s designation was left as "'.$employee->designation.'" because it had changed since.'));
    }

    /** Promotions this login may see: its own company and, when tied to a branch, that branch's employees. */
    protected function scoped(): Builder
    {
        return EmployeePromotion::query()->whereIn('employee_id', $this->employeeQuery()->select('id'));
    }

    protected function employeeQuery(): Builder
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }

    /** What the "Promote someone" form shows live for the picked employee. */
    protected function formData(Collection $people, Collection $latestByEmployee): array
    {
        $ratings = Appraisal::query()->whereIn('employee_id', $people->pluck('id'))->where('status', 'completed')->whereNotNull('overall_rating')
            ->with('appraisalCycle:id,name')->orderBy('completed_at')->get()->groupBy('employee_id')->map->last();

        return $people->mapWithKeys(function (Employee $employee) use ($latestByEmployee, $ratings) {
            $last = $latestByEmployee->get($employee->id);
            // How long they have held the current title: since the last promotion, else since joining.
            $since = $last?->promotion_date ?? $employee->joining_date;
            $rating = $ratings->get($employee->id);

            return [$employee->id => [
                'name' => $employee->name,
                'designation' => trim((string) $employee->designation),
                'department' => $employee->department?->name,
                'since' => $since?->format('M j, Y'),
                'months' => $since ? (int) $since->diffInMonths(today()) : null,
                'sinceKind' => $last ? 'last promoted' : 'joined',
                'lastDate' => $last?->promotion_date->toDateString(),
                'joined' => $employee->joining_date?->toDateString(),
                'rating' => $rating ? rtrim(rtrim(number_format((float) $rating->overall_rating, 2), '0'), '.').' of 5 ('.($rating->appraisalCycle?->name ?? 'last appraisal').')' : null,
            ]];
        })->all();
    }
}
