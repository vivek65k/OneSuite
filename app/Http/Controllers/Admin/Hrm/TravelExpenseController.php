<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\TravelExpense;
use App\Models\TravelRequest;
use App\Models\TravelSettlement;
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

class TravelExpenseController extends Controller
{
    protected const TABS = ['pending', 'approved', 'rejected'];

    /** The amount column is decimal(12,2). */
    protected const MAX_AMOUNT = 9999999999.99;

    /** An expense may fall this many days outside the trip (a cab the evening before, a meal on the way home). */
    protected const SLACK_DAYS = 1;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', TravelExpense::class);

        // Anything that isn't a plain value (e.g. ?status[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $id = fn (string $key) => ctype_digit($text($key)) ? (int) $text($key) : null;
        // The dashboard and the old page link here with ?status=pending; those links keep working.
        $tab = $text('tab') !== '' ? $text('tab') : $text('status');
        $state = [
            'tab' => in_array($tab, self::TABS, true) ? $tab : '',
            'category' => array_key_exists($text('category'), TravelExpense::CATEGORIES) ? $text('category') : '',
            'employee_id' => $id('employee_id'),
            'trip_id' => $id('trip_id'),
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $filtered = fn () => $this->scoped()
            ->when($state['category'], fn ($q, $category) => $q->where('category', $category))
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['trip_id'], fn ($q, $tripId) => $q->where('travel_request_id', $tripId))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->where(fn ($w) => $w
                    ->whereHas('employee', fn ($e) => $e->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('employee_code', 'like', $like)))
                    ->orWhereHas('travelRequest', fn ($t) => $t->where(fn ($x) => $x->where('to_location', 'like', $like)->orWhere('from_location', 'like', $like)->orWhere('purpose', 'like', $like))));
            });

        $expenses = $filtered()
            ->when($state['tab'], fn ($q, $status) => $q->where('status', $status))
            ->with(['employee.department:id,name', 'travelRequest', 'reviewedBy:id,name'])
            ->orderByDesc('expense_date')->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $all = $this->scoped()->get(['id', 'travel_request_id', 'employee_id', 'amount', 'status', 'expense_date']);
        $settledIds = TravelSettlement::query()->where('company_id', Company::current()->id)->where('status', 'settled')->pluck('travel_request_id')->flip();
        $pending = $all->where('status', 'pending');
        // Approved but the trip isn't settled yet: money still owed to the employee.
        $owed = $all->where('status', 'approved')->reject(fn (TravelExpense $e) => $settledIds->has($e->travel_request_id));
        $month = $all->where('status', '!=', 'rejected')->filter(fn (TravelExpense $e) => $e->expense_date->isSameMonth(today()));
        $user = $request->user();
        $canCreate = $user->can('create', TravelExpense::class);
        $focus = $state['trip_id'] ? $this->trips()->whereKey($state['trip_id'])->with('employee:id,name')->first() : null;

        return view('admin.hrm.travel.expenses.index', [
            'expenses' => $expenses,
            'state' => $state,
            'settledIds' => $settledIds,
            'employees' => Employee::query()->where('company_id', Company::current()->id)
                ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
                ->whereIn('id', $all->pluck('employee_id')->unique())->orderBy('name')->pluck('name', 'id'),
            'counts' => ['all' => $filtered()->count()] + collect(self::TABS)->mapWithKeys(fn ($t) => [$t => $filtered()->where('status', $t)->count()])->all(),
            'kpis' => [
                'pending' => $pending->count(),
                'pending_amount' => (float) $pending->sum(fn (TravelExpense $e) => (float) $e->amount),
                'pending_people' => $pending->pluck('employee_id')->unique()->count(),
                'owed' => (float) $owed->sum(fn (TravelExpense $e) => (float) $e->amount),
                'owed_trips' => $owed->pluck('travel_request_id')->unique()->count(),
                'month' => (float) $month->sum(fn (TravelExpense $e) => (float) $e->amount),
                'month_count' => $month->count(),
                'rejected' => $all->where('status', 'rejected')->count(),
                'rejected_amount' => (float) $all->where('status', 'rejected')->sum(fn (TravelExpense $e) => (float) $e->amount),
            ],
            'focus' => $focus,
            // This login's own request can't be reviewed by them (super admin excepted), same as denyOwnRequest().
            'ownEmployeeId' => $user->is_super_admin ? null : Employee::query()->where('user_id', $user->id)->value('id'),
            'canCreate' => $canCreate,
            'canReview' => $user->can('update', new TravelExpense),
            'trips' => $canCreate ? $this->tripOptions($settledIds) : collect(),
            'slack' => self::SLACK_DAYS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', TravelExpense::class);

        $tripId = $request->input('travel_request_id');
        // Only an approved trip of this login's own company and branch can have expenses claimed against it.
        $trip = is_scalar($tripId) && ctype_digit((string) $tripId) ? $this->trips()->whereKey((int) $tripId)->with('employee:id,name')->first() : null;

        if (! $trip) {
            throw ValidationException::withMessages(['travel_request_id' => 'Choose an approved trip from the list.']);
        }

        $this->guardSettled($trip->id);

        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(TravelExpense::CATEGORIES))],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.self::MAX_AMOUNT],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
        ], [
            'amount.min' => 'The amount must be more than zero.',
            'amount.max' => 'The amount is too large.',
            'expense_date.before_or_equal' => 'An expense can\'t be dated in the future.',
        ]);

        $date = Carbon::parse($data['expense_date'])->startOfDay();
        $first = $trip->start_date->copy()->subDays(self::SLACK_DAYS);
        $last = $trip->end_date->copy()->addDays(self::SLACK_DAYS);

        if ($date->lt($first) || $date->gt($last)) {
            throw ValidationException::withMessages(['expense_date' => 'The trip ran '.$trip->start_date->format('M j').' – '.$trip->end_date->format('M j, Y').', so the expense must be dated between '.$first->format('M j').' and '.$last->format('M j, Y').'.']);
        }

        $amount = round((float) $data['amount'], 2);

        // The same claim entered twice would be paid twice.
        if (TravelExpense::withoutGlobalScopes()->where('travel_request_id', $trip->id)->where('category', $data['category'])
            ->whereDate('expense_date', $date)->where('amount', $amount)->where('status', '!=', 'rejected')->exists()) {
            throw ValidationException::withMessages(['amount' => 'A '.strtolower(TravelExpense::CATEGORIES[$data['category']]).' expense of '.number_format($amount, 2).' on '.$date->format('M j').' is already recorded for this trip.']);
        }

        TravelExpense::create([
            'company_id' => $trip->company_id, 'travel_request_id' => $trip->id, 'employee_id' => $trip->employee_id,
            'category' => $data['category'], 'amount' => $amount, 'expense_date' => $date->toDateString(), 'status' => 'pending',
        ]);

        return back()->with('status', TravelExpense::CATEGORIES[$data['category']].' expense of '.number_format($amount, 2).' added for '.($trip->employee?->name ?? 'the employee').'. It is waiting for approval.');
    }

    public function approve(TravelExpense $expense): RedirectResponse
    {
        $this->ensureOwn($expense);
        $this->authorize('update', $expense);
        $this->denyOwnRequest($expense->employee);

        $this->decide($expense, 'approved');

        return back()->with('status', 'Expense of '.number_format((float) $expense->amount, 2).' for '.($expense->employee?->name ?? 'the employee').' approved.');
    }

    /** Approve several expenses at once. Each one is checked like a single approval; the rest are skipped. */
    public function bulkApprove(Request $request): RedirectResponse
    {
        $this->authorize('update', new TravelExpense);

        $ids = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:200'], 'ids.*' => ['integer']], ['ids.required' => 'Select at least one expense.'])['ids'];
        $user = $request->user();
        $approved = 0;
        $total = 0.0;

        foreach ($this->scoped()->whereKey($ids)->where('status', 'pending')->with('employee')->get() as $expense) {
            // Never your own expense (same rule as denyOwnRequest() on the single approve).
            if (! $user->can('update', $expense) || (! $user->is_super_admin && (int) $expense->employee?->user_id === (int) $user->id)) {
                continue;
            }

            try {
                $this->decide($expense, 'approved');
                $approved++;
                $total += (float) $expense->amount;
            } catch (ValidationException) {
                // Settled trip, or someone else dealt with it in the meantime.
            }
        }

        $skipped = count(array_unique($ids)) - $approved;

        return back()->with('status', 'Approved '.$approved.' '.($approved === 1 ? 'expense' : 'expenses').($approved ? ' ('.number_format($total, 2).')' : '').'.'
            .($skipped ? ' Skipped '.$skipped.' that '.($skipped === 1 ? 'was' : 'were').' not yours to approve, already decided or on a settled trip.' : ''));
    }

    public function reject(Request $request, TravelExpense $expense): RedirectResponse
    {
        $this->ensureOwn($expense);
        $this->authorize('update', $expense);
        $this->denyOwnRequest($expense->employee);

        $remarks = is_scalar($request->input('remarks')) && trim((string) $request->input('remarks')) !== '' ? mb_substr(trim((string) $request->input('remarks')), 0, 1000) : null;

        $this->decide($expense, 'rejected', $remarks);

        return back()->with('status', 'Expense of '.number_format((float) $expense->amount, 2).' for '.($expense->employee?->name ?? 'the employee').' rejected.');
    }

    public function destroy(TravelExpense $expense): RedirectResponse
    {
        $this->ensureOwn($expense);
        $this->authorize('delete', $expense);

        $this->guardSettled($expense->travel_request_id);
        $expense->delete();

        return back()->with('status', 'Expense removed.');
    }

    /** Approve or reject a pending expense, refusing one that was already decided or whose trip is settled. */
    protected function decide(TravelExpense $expense, string $status, ?string $remarks = null): void
    {
        DB::transaction(function () use ($expense, $status, $remarks) {
            $row = TravelExpense::withoutGlobalScopes()->whereKey($expense->id)->lockForUpdate()->first();

            // Decided a moment ago by someone else, or the button was pressed twice.
            if ($row->status !== 'pending') {
                throw ValidationException::withMessages(['expense' => 'This expense has already been '.$row->status.'.']);
            }

            $this->guardSettled($row->travel_request_id);

            $expense->update(['status' => $status, 'reviewed_by_user_id' => Auth::id(), 'reviewed_at' => now(), 'remarks' => $remarks]);
        });
    }

    /** A settled trip's costs are final: its reimbursement was worked out from these expenses. */
    protected function guardSettled(int $tripId): void
    {
        if (TravelSettlement::withoutGlobalScopes()->where('travel_request_id', $tripId)->where('status', 'settled')->exists()) {
            throw ValidationException::withMessages(['expense' => 'This trip has already been settled, so its expenses can no longer be changed.']);
        }
    }

    /** Approved, unsettled trips for the form, with what has been spent on each so far. */
    protected function tripOptions($settledIds)
    {
        return $this->trips()->whereNotIn('id', $settledIds->keys())
            ->whereDate('start_date', '<=', today()->addDays(self::SLACK_DAYS))
            ->with('employee:id,name')->withSum('bookings as booked', 'cost')
            ->withSum(['expenses as claimed' => fn ($q) => $q->where('status', '!=', 'rejected')], 'amount')
            ->orderByDesc('start_date')->limit(200)->get()
            ->map(fn (TravelRequest $t) => [
                'id' => $t->id,
                'label' => ($t->employee?->name ?? 'Removed employee').' · '.$t->from_location.' to '.$t->to_location.' ('.$t->start_date->format('M j').($t->start_date->isSameDay($t->end_date) ? '' : ' – '.$t->end_date->format('M j')).')',
                'who' => $t->employee?->name,
                'start' => $t->start_date->toDateString(),
                'end' => $t->end_date->toDateString(),
                'estimate' => $t->estimated_cost !== null ? (float) $t->estimated_cost : null,
                'booked' => (float) $t->booked,
                'claimed' => (float) $t->claimed,
            ]);
    }

    /** 404 (not 403) for another company's or branch's expense, so the URL doesn't confirm it exists. */
    protected function ensureOwn(TravelExpense $expense): void
    {
        abort_unless($this->scoped()->whereKey($expense->id)->exists(), 404);
    }

    /** Expenses this login may see: its own company and, when tied to a branch, that branch's employees. */
    protected function scoped(): Builder
    {
        return TravelExpense::query()
            ->where('travel_expenses.company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }

    protected function trips(): Builder
    {
        return TravelRequest::query()
            ->where('travel_requests.company_id', Company::current()->id)
            ->where('travel_requests.status', 'approved')
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }
}
