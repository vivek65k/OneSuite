<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\TravelBooking;
use App\Models\TravelExpense;
use App\Models\TravelRequest;
use App\Models\TravelSettlement;
use App\Support\ApprovalFlow;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TravelRequestController extends Controller
{
    protected const TABS = ['pending', 'approved', 'rejected'];

    /** Employees who have left don't travel on company business. */
    protected const INACTIVE = ['terminated', 'inactive'];

    /** A trip can be entered this many days after it started (late paperwork) and this many months ahead. */
    protected const DAYS_BACK = 90;

    protected const MONTHS_AHEAD = 12;

    /** The longest single trip, in days. */
    protected const MAX_DAYS = 90;

    /** The cost column is decimal(12,2). */
    protected const MAX_COST = 9999999999.99;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', TravelRequest::class);

        // Anything that isn't a plain value (e.g. ?status[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $id = fn (string $key) => ctype_digit($text($key)) ? (int) $text($key) : null;
        // The old page filtered with ?status=; those links keep working.
        $tab = $text('tab') !== '' ? $text('tab') : $text('status');
        $state = [
            'tab' => in_array($tab, self::TABS, true) ? $tab : '',
            'mode' => array_key_exists($text('mode'), TravelRequest::MODES) ? $text('mode') : '',
            'employee_id' => $id('employee_id'),
            'department_id' => $id('department_id'),
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $filtered = fn () => $this->scoped()
            ->when($state['mode'], fn ($q, $mode) => $q->where('mode_of_travel', $mode))
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['department_id'], fn ($q, $departmentId) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $departmentId)))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->where(fn ($w) => $w->where('purpose', 'like', $like)->orWhere('from_location', 'like', $like)->orWhere('to_location', 'like', $like)
                    ->orWhereHas('employee', fn ($e) => $e->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('employee_code', 'like', $like))));
            });

        $requests = $filtered()
            ->when($state['tab'], fn ($q, $status) => $q->where('status', $status))
            ->with(['employee.department:id,name', 'reviewedBy:id,name', 'settlement'])
            ->withCount(['bookings', 'expenses'])
            ->orderByDesc('start_date')->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $all = $this->scoped()->get(['id', 'employee_id', 'start_date', 'end_date', 'estimated_cost', 'status']);
        $pending = $all->where('status', 'pending');
        $approved = $all->where('status', 'approved');
        $upcoming = $approved->filter(fn (TravelRequest $r) => $r->start_date->gt(today()));
        $people = $this->employeeQuery()->orderBy('name')->get(['id', 'name', 'employee_code', 'department_id', 'status', 'user_id']);
        $user = $request->user();
        $canReview = $user->can('update', new TravelRequest);

        return view('admin.hrm.travel.requests.index', [
            'requests' => $requests,
            'state' => $state,
            'employees' => $people->pluck('name', 'id'),
            'formPeople' => $people->whereNotIn('status', self::INACTIVE)->values(),
            'departments' => Department::query()->where('company_id', Company::current()->id)
                ->whereIn('id', $people->pluck('department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            // Each person's open trips, so the form can warn about an overlap before saving.
            'trips' => $all->whereIn('status', ['pending', 'approved'])->groupBy('employee_id')
                ->map(fn ($group) => $group->map(fn (TravelRequest $r) => ['id' => $r->id, 'from' => $r->start_date->toDateString(), 'to' => $r->end_date->toDateString()])->values()),
            // Where each pending request on this page stands in the approval chain, and whether this login can act on it.
            'stages' => $requests->getCollection()->where('status', 'pending')->mapWithKeys(function (TravelRequest $r) use ($user, $canReview) {
                $stage = ApprovalFlow::state($r);
                $own = ! $user->is_super_admin && $r->employee && (int) $r->employee->user_id === (int) $user->id;

                return [$r->id => ['required' => $stage['required'], 'done' => $stage['done'], 'waiting' => $stage['waiting']?->name, 'own' => $own, 'can_act' => $canReview && ! $own && ApprovalFlow::awaits($user, $r, $stage)]];
            }),
            'counts' => ['all' => $filtered()->count()] + collect(self::TABS)->mapWithKeys(fn ($tab) => [$tab => $filtered()->where('status', $tab)->count()])->all(),
            'kpis' => [
                'pending' => $pending->count(),
                'pending_cost' => (float) $pending->sum(fn (TravelRequest $r) => (float) $r->estimated_cost),
                'soon' => $pending->filter(fn (TravelRequest $r) => $r->start_date->lte(today()->addDays(7)))->count(),
                'travelling' => $approved->filter(fn (TravelRequest $r) => $r->start_date->lte(today()) && $r->end_date->gte(today()))->count(),
                'upcoming' => $upcoming->count(),
                'next' => $upcoming->min('start_date'),
                'year_cost' => (float) $approved->filter(fn (TravelRequest $r) => $r->start_date->year === today()->year)->sum(fn (TravelRequest $r) => (float) $r->estimated_cost),
                'year_trips' => $approved->filter(fn (TravelRequest $r) => $r->start_date->year === today()->year)->count(),
            ],
            'canCreate' => $user->can('create', TravelRequest::class),
            'limits' => ['min' => today()->subDays(self::DAYS_BACK)->toDateString(), 'max' => today()->addMonths(self::MONTHS_AHEAD)->toDateString(), 'days' => self::MAX_DAYS],
        ]);
    }

    public function approval(Request $request): View
    {
        $this->authorize('viewAny', TravelRequest::class);

        // Anything that isn't a plain value (e.g. ?employee_id[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $id = fn (string $key) => ctype_digit($text($key)) ? (int) $text($key) : null;
        $state = [
            'window' => in_array($text('window'), ['week', 'month'], true) ? $text('window') : '',
            'sort' => $text('sort') === 'oldest' ? 'oldest' : '',
            'mode' => array_key_exists($text('mode'), TravelRequest::MODES) ? $text('mode') : '',
            'employee_id' => $id('employee_id'),
            'department_id' => $id('department_id'),
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $filtered = fn () => $this->scoped()->where('status', 'pending')
            ->when($state['mode'], fn ($q, $mode) => $q->where('mode_of_travel', $mode))
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['department_id'], fn ($q, $departmentId) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $departmentId)))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->where(fn ($w) => $w->where('purpose', 'like', $like)->orWhere('from_location', 'like', $like)->orWhere('to_location', 'like', $like)
                    ->orWhereHas('employee', fn ($e) => $e->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('employee_code', 'like', $like))));
            });
        // "Within a week" also covers trips that have already started: those are the most urgent of all.
        $windowed = fn (Builder $q, string $window) => match ($window) {
            'week' => $q->whereDate('start_date', '<=', today()->addDays(7)),
            'month' => $q->whereDate('start_date', '<=', today()->addDays(30)),
            default => $q,
        };

        $requests = $windowed($filtered(), $state['window'])
            ->with('employee.department:id,name')
            ->when($state['sort'] === 'oldest', fn ($q) => $q->orderBy('created_at')->orderBy('id'), fn ($q) => $q->orderBy('start_date')->orderBy('id'))
            ->paginate(20)
            ->withQueryString();

        $shown = $requests->getCollection();
        $employeeIds = $shown->pluck('employee_id')->unique();
        $user = $request->user();
        $canReview = $user->can('update', new TravelRequest);
        $waiting = $this->scoped()->where('status', 'pending')->get(['id', 'start_date', 'estimated_cost', 'created_at']);
        // What each person has already had approved this year, as context for the decision.
        $history = $this->scoped()->where('status', 'approved')->whereIn('employee_id', $employeeIds)->whereYear('start_date', today()->year)
            ->get(['id', 'employee_id', 'estimated_cost'])->groupBy('employee_id')
            ->map(fn ($group) => ['trips' => $group->count(), 'cost' => (float) $group->sum(fn (TravelRequest $r) => (float) $r->estimated_cost)]);
        // Leave that falls inside the trip: the person can't be travelling for work and on leave at once.
        $leave = $shown->isEmpty() ? collect() : LeaveRequest::query()->where('company_id', Company::current()->id)->whereIn('employee_id', $employeeIds)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $shown->max('end_date'))->whereDate('end_date', '>=', $shown->min('start_date'))
            ->get(['id', 'employee_id', 'start_date', 'end_date', 'status'])->groupBy('employee_id');
        $people = $this->employeeQuery()->orderBy('name')->get(['id', 'name', 'department_id']);

        return view('admin.hrm.travel.approval', [
            'requests' => $requests,
            'state' => $state,
            'employees' => $people->pluck('name', 'id'),
            'departments' => Department::query()->where('company_id', Company::current()->id)
                ->whereIn('id', $people->pluck('department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            'history' => $history,
            'leaveClashes' => $shown->mapWithKeys(fn (TravelRequest $r) => [$r->id => $leave->get($r->employee_id, collect())
                ->filter(fn (LeaveRequest $l) => $l->start_date->lte($r->end_date) && $l->end_date->gte($r->start_date))->values()]),
            // Where each request stands in the approval chain, and whether this login can act on it.
            'stages' => $shown->mapWithKeys(function (TravelRequest $r) use ($user, $canReview) {
                $stage = ApprovalFlow::state($r);
                $own = ! $user->is_super_admin && $r->employee && (int) $r->employee->user_id === (int) $user->id;

                return [$r->id => ['required' => $stage['required'], 'done' => $stage['done'], 'waiting' => $stage['waiting']?->name, 'own' => $own, 'can_act' => $canReview && ! $own && ApprovalFlow::awaits($user, $r, $stage)]];
            }),
            'counts' => ['all' => $filtered()->count(), 'week' => $windowed($filtered(), 'week')->count(), 'month' => $windowed($filtered(), 'month')->count()],
            'kpis' => [
                'waiting' => $waiting->count(),
                'week' => $waiting->filter(fn (TravelRequest $r) => $r->start_date->lte(today()->addDays(7)))->count(),
                'started' => $waiting->filter(fn (TravelRequest $r) => $r->start_date->lte(today()))->count(),
                'cost' => (float) $waiting->sum(fn (TravelRequest $r) => (float) $r->estimated_cost),
                'oldest' => $waiting->min('created_at'),
            ],
            'canReview' => $canReview,
        ]);
    }

    /** Approve several requests at once. Each one is checked exactly like a single approval; the rest are skipped. */
    public function bulkApprove(Request $request): RedirectResponse
    {
        $this->authorize('update', new TravelRequest);

        $ids = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:200'], 'ids.*' => ['integer']], ['ids.required' => 'Select at least one request.'])['ids'];
        $user = $request->user();
        $approved = 0;
        $forwarded = 0;

        foreach ($this->scoped()->whereKey($ids)->where('status', 'pending')->with('employee')->get() as $travelRequest) {
            $reviewable = $user->can('update', $travelRequest)
                // Never your own request (same rule as denyOwnRequest() on the single approve).
                && ($user->is_super_admin || (int) $travelRequest->employee?->user_id !== (int) $user->id)
                // Already approved at this manager's level: nothing for them to add.
                && ApprovalFlow::awaits($user, $travelRequest);

            if (! $reviewable) {
                continue;
            }

            try {
                $this->settle($travelRequest) === null ? $approved++ : $forwarded++;
            } catch (ValidationException) {
                // Someone else dealt with it in the meantime.
            }
        }

        $skipped = count(array_unique($ids)) - $approved - $forwarded;

        return back()->with('status', 'Approved '.$approved.' travel '.($approved === 1 ? 'request' : 'requests').'.'
            .($forwarded ? ' '.$forwarded.' moved to the next approval level.' : '')
            .($skipped ? ' Skipped '.$skipped.' that '.($skipped === 1 ? 'was' : 'were').' not yours to approve or no longer pending.' : ''));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', TravelRequest::class);

        $employeeId = $request->input('employee_id');
        $employee = is_scalar($employeeId) && ctype_digit((string) $employeeId)
            ? $this->employeeQuery()->whereNotIn('status', self::INACTIVE)->whereKey((int) $employeeId)->first(['id', 'name'])
            : null;

        if (! $employee) {
            throw ValidationException::withMessages(['employee_id' => 'Choose an employee from the list.']);
        }

        $data = $this->validated($request, $employee->id);

        TravelRequest::create($data + ['company_id' => Company::current()->id, 'employee_id' => $employee->id, 'status' => 'pending']);

        return redirect()->route('admin.travel-requests.index')->with('status', 'Travel request for '.$employee->name.' to '.$data['to_location'].' submitted. It is waiting for approval.');
    }

    public function update(Request $request, TravelRequest $travelRequest): RedirectResponse
    {
        $this->ensureOwn($travelRequest);
        $this->authorize('update', $travelRequest);

        if ($travelRequest->status !== 'pending') {
            return back()->withErrors(['request' => 'This request has already been '.$travelRequest->status.', so it can\'t be changed.']);
        }

        $travelRequest->update($this->validated($request, $travelRequest->employee_id, $travelRequest));

        return back()->with('status', 'Travel request updated.');
    }

    public function approve(TravelRequest $travelRequest): RedirectResponse
    {
        $this->ensureOwn($travelRequest);
        $this->authorize('update', $travelRequest);
        $this->denyOwnRequest($travelRequest->employee);

        $waiting = $this->settle($travelRequest);

        return back()->with('status', $waiting ?? 'Travel request for '.($travelRequest->employee?->name ?? 'the employee').' approved.');
    }

    /**
     * Record this login's approval. Returns null when the request is now
     * approved, or the "moved up a level" message when someone further up
     * still has to approve it.
     */
    protected function settle(TravelRequest $travelRequest): ?string
    {
        return DB::transaction(function () use ($travelRequest) {
            $row = TravelRequest::withoutGlobalScopes()->whereKey($travelRequest->id)->lockForUpdate()->first();

            // Approved or rejected a moment ago by someone else, or the button was pressed twice.
            if ($row->status !== 'pending') {
                throw ValidationException::withMessages(['request' => 'This request has already been '.$row->status.'.']);
            }

            // Multi-level approval: stop here while a manager further up still has to approve.
            if ($waiting = ApprovalFlow::forward($travelRequest)) {
                return $waiting;
            }

            $travelRequest->update(['status' => 'approved', 'reviewed_by_user_id' => Auth::id(), 'reviewed_at' => now()]);

            return null;
        });
    }

    public function reject(Request $request, TravelRequest $travelRequest): RedirectResponse
    {
        $this->ensureOwn($travelRequest);
        $this->authorize('update', $travelRequest);
        $this->denyOwnRequest($travelRequest->employee);

        $remarks = is_scalar($request->input('remarks')) && trim((string) $request->input('remarks')) !== '' ? mb_substr(trim((string) $request->input('remarks')), 0, 1000) : null;

        DB::transaction(function () use ($travelRequest, $remarks) {
            $row = TravelRequest::withoutGlobalScopes()->whereKey($travelRequest->id)->lockForUpdate()->first();

            if ($row->status !== 'pending') {
                throw ValidationException::withMessages(['request' => 'This request has already been '.$row->status.'.']);
            }

            ApprovalFlow::rejected($travelRequest, $remarks);
            $travelRequest->update(['status' => 'rejected', 'reviewed_by_user_id' => Auth::id(), 'reviewed_at' => now(), 'remarks' => $remarks]);
        });

        return back()->with('status', 'Travel request for '.($travelRequest->employee?->name ?? 'the employee').' rejected.');
    }

    public function destroy(TravelRequest $travelRequest): RedirectResponse
    {
        $this->ensureOwn($travelRequest);
        $this->authorize('delete', $travelRequest);

        // Deleting a trip would also delete its tickets, expense claims and settlement.
        $bookings = TravelBooking::withoutGlobalScopes()->where('travel_request_id', $travelRequest->id)->count();
        $expenses = TravelExpense::withoutGlobalScopes()->where('travel_request_id', $travelRequest->id)->count();
        $settled = TravelSettlement::withoutGlobalScopes()->where('travel_request_id', $travelRequest->id)->exists();

        if ($bookings || $expenses || $settled) {
            $what = collect([$bookings ? $bookings.' '.($bookings === 1 ? 'booking' : 'bookings') : null, $expenses ? $expenses.' '.($expenses === 1 ? 'expense' : 'expenses') : null, $settled ? 'a settlement' : null])->filter()->implode(', ');

            return back()->withErrors(['request' => 'This trip has '.$what.' recorded against it, so it can\'t be deleted.']);
        }

        $travelRequest->delete();

        return redirect()->route('admin.travel-requests.index')->with('status', 'Travel request removed.');
    }

    protected function validated(Request $request, int $employeeId, ?TravelRequest $existing = null): array
    {
        $first = today()->subDays(self::DAYS_BACK)->toDateString();
        $last = today()->addMonths(self::MONTHS_AHEAD)->toDateString();

        $data = $request->validate([
            'purpose' => ['required', 'string', 'max:255'],
            'from_location' => ['required', 'string', 'max:255'],
            'to_location' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date', 'after_or_equal:'.$first, 'before_or_equal:'.$last],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'mode_of_travel' => ['required', Rule::in(array_keys(TravelRequest::MODES))],
            'estimated_cost' => ['nullable', 'numeric', 'min:0', 'max:'.self::MAX_COST],
        ], [
            'start_date.after_or_equal' => 'A trip can be entered at most '.self::DAYS_BACK.' days after it started.',
            'start_date.before_or_equal' => 'A trip can be requested at most '.self::MONTHS_AHEAD.' months ahead.',
            'end_date.after_or_equal' => 'The return date can\'t be before the departure date.',
            'estimated_cost.max' => 'The estimated cost is too large.',
        ]);

        $from = trim($data['from_location']);
        $to = trim($data['to_location']);
        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end = Carbon::parse($data['end_date'])->startOfDay();

        if (mb_strtolower($from) === mb_strtolower($to)) {
            throw ValidationException::withMessages(['to_location' => 'The destination is the same as the starting point.']);
        }
        if ($start->diffInDays($end) + 1 > self::MAX_DAYS) {
            throw ValidationException::withMessages(['end_date' => 'A single trip can last at most '.self::MAX_DAYS.' days. Split a longer stay into several requests.']);
        }

        // One person can't be on two trips on the same day.
        $clash = TravelRequest::withoutGlobalScopes()->where('company_id', Company::current()->id)->where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'approved'])
            ->when($existing, fn ($q) => $q->whereKeyNot($existing->id))
            ->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start)
            ->first();

        if ($clash) {
            throw ValidationException::withMessages(['start_date' => 'This overlaps another '.$clash->status.' trip of the same person: '.$clash->from_location.' to '.$clash->to_location.', '.$clash->start_date->format('M j').' – '.$clash->end_date->format('M j, Y').'.']);
        }

        return [
            'purpose' => trim($data['purpose']),
            'from_location' => $from,
            'to_location' => $to,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'mode_of_travel' => $data['mode_of_travel'],
            'estimated_cost' => filled($data['estimated_cost'] ?? null) ? round((float) $data['estimated_cost'], 2) : null,
        ];
    }

    /** 404 (not 403) for another company's or branch's request, so the URL doesn't confirm it exists. */
    protected function ensureOwn(TravelRequest $travelRequest): void
    {
        abort_unless($this->scoped()->whereKey($travelRequest->id)->exists(), 404);
    }

    /** Requests this login may see: its own company and, when tied to a branch, that branch's employees. */
    protected function scoped(): Builder
    {
        return TravelRequest::query()
            ->where('travel_requests.company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }

    protected function employeeQuery(): Builder
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }
}
