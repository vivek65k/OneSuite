<?php

namespace App\Http\Controllers\Admin\Ess;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\ApprovalStep;
use App\Models\Employee;
use App\Models\TravelExpense;
use App\Models\TravelRequest;
use App\Models\TravelSettlement;
use App\Support\ApprovalFlow;
use App\Support\Notifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MyTravelController extends Controller
{
    use ResolvesCurrentEmployee;

    protected const TABS = ['pending', 'upcoming', 'past', 'rejected'];

    /** People who have left can't request new trips. */
    protected const INACTIVE = ['terminated', 'inactive'];

    /** The same limits HR's travel request form uses. */
    public const DAYS_BACK = 90;

    public const MONTHS_AHEAD = 12;

    public const MAX_DAYS = 90;

    protected const MAX_AMOUNT = 9999999999.99;

    /** An expense may be dated this many days either side of the trip (the cab to the airport the night before). */
    public const SLACK_DAYS = 1;

    public function index(Request $request): View
    {
        $employee = $this->currentEmployee();

        // Anything that isn't a plain value (e.g. ?tab[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $state = ['tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : ''];

        $all = $this->mine($employee)->with(['reviewedBy:id,name', 'bookings', 'expenses', 'settlement'])->get()
            ->map(function (TravelRequest $t) {
                $settled = $t->settlement?->status === 'settled';
                $t->setAttribute('stage', match (true) {
                    $t->status === 'pending' => 'pending',
                    $t->status === 'rejected' => 'rejected',
                    $t->end_date->lt(today()) => 'past',
                    $t->start_date->lte(today()) => 'now',
                    default => 'upcoming',
                });
                $t->setAttribute('days', (int) $t->start_date->diffInDays($t->end_date) + 1);
                $t->setAttribute('days_to_go', (int) today()->diffInDays($t->start_date, false));
                $t->setAttribute('settled', $settled);
                $t->setAttribute('booked', (float) $t->bookings->sum('cost'));
                $t->setAttribute('claimed', (float) $t->expenses->where('status', '!=', 'rejected')->sum('amount'));
                $t->setAttribute('approved_expenses', (float) $t->expenses->where('status', 'approved')->sum('amount'));
                // Expenses go on an approved trip once it is under way (or the day before), until it is settled.
                $t->setAttribute('can_expense', $t->status === 'approved' && ! $settled && $t->start_date->copy()->subDays(self::SLACK_DAYS)->lte(today()));

                return $t;
            })
            // What is ahead first (soonest on top), then what is done, newest first.
            ->sortBy(fn (TravelRequest $t) => in_array($t->stage, ['now', 'upcoming', 'pending'], true)
                ? [0, $t->start_date->timestamp] : [1, -1 * $t->start_date->timestamp])->values();
        $tabbed = fn (Collection $rows, string $tab) => match ($tab) {
            'pending' => $rows->where('stage', 'pending'),
            'upcoming' => $rows->whereIn('stage', ['now', 'upcoming']),
            'past' => $rows->where('stage', 'past'),
            'rejected' => $rows->where('stage', 'rejected'),
            default => $rows,
        };
        // Who a pending request is waiting on, and whether anyone has approved it yet (after which it can't be changed).
        $flow = $all->where('status', 'pending')->mapWithKeys(function (TravelRequest $t) {
            $s = ApprovalFlow::state($t);

            return [$t->id => ['name' => $s['waiting']?->name, 'done' => $s['done'], 'required' => $s['required']]];
        });
        $approved = $all->where('status', 'approved');
        $next = $all->first(fn (TravelRequest $t) => in_array($t->stage, ['now', 'upcoming'], true));
        $owed = $approved->where('settled', false);

        return view('admin.ess.travel', [
            'state' => $state,
            'trips' => $tabbed($all, $state['tab'])->values(),
            'flow' => $flow,
            'counts' => ['all' => $all->count()] + collect(self::TABS)->mapWithKeys(fn ($tab) => [$tab => $tabbed($all, $tab)->count()])->all(),
            'kpis' => [
                'next' => $next,
                'upcoming' => $tabbed($all, 'upcoming')->count(),
                'pending' => $all->where('stage', 'pending')->count(),
                'trips_year' => $approved->filter(fn (TravelRequest $t) => $t->start_date->year === now()->year)->count(),
                'days_year' => (int) $approved->filter(fn (TravelRequest $t) => $t->start_date->year === now()->year)->sum('days'),
                'owed' => (float) $owed->sum('approved_expenses'),
                'owed_pending' => (float) $owed->sum(fn (TravelRequest $t) => (float) $t->expenses->where('status', 'pending')->sum('amount')),
                'to_claim' => $owed->filter(fn (TravelRequest $t) => $t->stage === 'past' && $t->expenses->isEmpty())->count(),
            ],
            'canRequest' => ! in_array($employee->status, self::INACTIVE, true),
            'employeeStatus' => $employee->status,
            'minDate' => today()->subDays(self::DAYS_BACK)->toDateString(),
            'maxDate' => today()->addMonths(self::MONTHS_AHEAD)->toDateString(),
            // What the form checks against before sending, so an overlap is caught while typing.
            'booked' => $all->whereIn('status', ['pending', 'approved'])->map(fn (TravelRequest $t) => ['id' => $t->id, 'from' => $t->start_date->toDateString(), 'to' => $t->end_date->toDateString(), 'label' => $t->from_location.' to '.$t->to_location])->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $this->currentEmployee();

        if (in_array($employee->status, self::INACTIVE, true)) {
            throw ValidationException::withMessages(['purpose' => 'Your employee record is '.$employee->status.', so new trips can\'t be requested. Ask HR.']);
        }

        $trip = DB::transaction(function () use ($request, $employee) {
            // One request at a time per person, so two quick submits can't both pass the overlap check.
            DB::table('employees')->where('id', $employee->id)->lockForUpdate()->first();
            $trip = (new TravelRequest)->forceFill($this->validated($request, $employee) + ['company_id' => $employee->company_id, 'employee_id' => $employee->id, 'status' => 'pending']);
            $trip->save();

            return $trip;
        });

        Notifier::notifyUsersWithPermission(
            'update_travel_request',
            'New travel request',
            "{$employee->name} asked to travel from {$trip->from_location} to {$trip->to_location}, {$trip->start_date->format('M j')} – {$trip->end_date->format('M j, Y')}.",
            route('admin.travel-approval.index'),
            'ti ti-plane',
            $employee->branch_id,
            employeeId: $employee->id,
        );

        return back()->with('status', 'Your trip to '.$trip->to_location.' ('.$trip->start_date->format('M j').' – '.$trip->end_date->format('M j').') was sent for approval.');
    }

    /** Correct a request that nobody has approved yet. */
    public function update(Request $request, int $travelRequest): RedirectResponse
    {
        $employee = $this->currentEmployee();
        $record = $this->owned($employee, $travelRequest);

        DB::transaction(function () use ($request, $employee, $record) {
            DB::table('employees')->where('id', $employee->id)->lockForUpdate()->first();
            $row = TravelRequest::withoutGlobalScopes()->whereKey($record->id)->lockForUpdate()->first();

            if ($row->status !== 'pending') {
                throw ValidationException::withMessages(['trip' => 'This trip is already '.$row->status.', so it can\'t be changed.']);
            }
            // Someone has signed off on these dates and costs; changing them afterwards would slip past that approval.
            if (ApprovalFlow::state($row)['done'] > 0) {
                throw ValidationException::withMessages(['trip' => 'Someone has already approved this trip. Withdraw it and send a new request if it needs changing.']);
            }
            $row->forceFill($this->validated($request, $employee, $row))->save();
        });

        return back()->with('status', 'Your travel request was updated.');
    }

    /** Take back a request that is still waiting for approval. */
    public function destroy(int $travelRequest): RedirectResponse
    {
        $employee = $this->currentEmployee();
        $record = $this->owned($employee, $travelRequest);

        DB::transaction(function () use ($record) {
            // The delete is conditional, so a withdrawal racing an approval can't remove an approved trip.
            if (! TravelRequest::withoutGlobalScopes()->whereKey($record->id)->where('status', 'pending')->delete()) {
                throw ValidationException::withMessages(['trip' => 'This trip is already '.(TravelRequest::withoutGlobalScopes()->whereKey($record->id)->value('status') ?? 'gone').', so it can\'t be withdrawn. Ask HR if it is no longer needed.']);
            }
            ApprovalStep::query()->where('approvable_type', $record->getMorphClass())->where('approvable_id', $record->id)->delete();
        });

        return back()->with('status', 'Your request to travel to '.$record->to_location.' was withdrawn.');
    }

    /** Claim something spent on one of your own approved trips. */
    public function storeExpense(Request $request, int $travelRequest): RedirectResponse
    {
        $employee = $this->currentEmployee();
        $trip = $this->owned($employee, $travelRequest);

        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(TravelExpense::CATEGORIES))],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.self::MAX_AMOUNT],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
        ], [
            'category.*' => 'Choose what kind of expense it was.',
            'amount.*' => 'Enter the amount spent, more than zero.',
            'expense_date.before_or_equal' => 'An expense can\'t be dated in the future.',
        ]);
        $amount = round((float) $data['amount'], 2);
        $date = Carbon::parse($data['expense_date'])->startOfDay();
        $label = TravelExpense::CATEGORIES[$data['category']];

        DB::transaction(function () use ($trip, $data, $amount, $date, $label) {
            $row = TravelRequest::withoutGlobalScopes()->whereKey($trip->id)->lockForUpdate()->first();

            if ($row->status !== 'approved') {
                throw ValidationException::withMessages(['expense' => 'Expenses can be claimed once the trip is approved; this one is '.$row->status.'.']);
            }
            $this->guardSettled($row->id);

            $first = $row->start_date->copy()->subDays(self::SLACK_DAYS);
            $last = $row->end_date->copy()->addDays(self::SLACK_DAYS);
            if ($date->lt($first) || $date->gt($last)) {
                throw ValidationException::withMessages(['expense_date' => 'The trip runs '.$row->start_date->format('M j').' – '.$row->end_date->format('M j, Y').', so the expense must be dated between '.$first->format('M j').' and '.$last->format('M j, Y').'.']);
            }
            // The same claim entered twice would be paid twice.
            if (TravelExpense::withoutGlobalScopes()->where('travel_request_id', $row->id)->where('category', $data['category'])
                ->whereDate('expense_date', $date)->where('amount', $amount)->where('status', '!=', 'rejected')->exists()) {
                throw ValidationException::withMessages(['amount' => 'A '.strtolower($label).' expense of '.number_format($amount, 2).' on '.$date->format('M j').' is already recorded for this trip.']);
            }

            (new TravelExpense)->forceFill([
                'company_id' => $row->company_id, 'travel_request_id' => $row->id, 'employee_id' => $row->employee_id,
                'category' => $data['category'], 'amount' => $amount, 'expense_date' => $date->toDateString(), 'status' => 'pending',
            ])->save();
        });

        Notifier::notifyUsersWithPermission(
            'update_travel_expense',
            'Travel expense to approve',
            $employee->name.' claimed '.number_format($amount, 2).' ('.$label.') for the trip to '.$trip->to_location.'.',
            route('admin.travel-expenses.index'),
            'ti ti-receipt',
            $employee->branch_id,
            employeeId: $employee->id,
        );

        return back()->with('status', $label.' expense of '.number_format($amount, 2).' added to your trip to '.$trip->to_location.'. It is waiting for approval.');
    }

    /** Remove one of your own trip expenses that nobody has decided on yet. */
    public function destroyExpense(int $travelRequest, int $expense): RedirectResponse
    {
        $employee = $this->currentEmployee();
        $trip = $this->owned($employee, $travelRequest);
        $record = TravelExpense::withoutGlobalScopes()->where('travel_request_id', $trip->id)->where('employee_id', $employee->id)->whereKey($expense)->firstOrFail();

        DB::transaction(function () use ($record, $trip) {
            $this->guardSettled($trip->id);
            if (! TravelExpense::withoutGlobalScopes()->whereKey($record->id)->where('status', 'pending')->delete()) {
                throw ValidationException::withMessages(['expense' => 'This expense has already been '.(TravelExpense::withoutGlobalScopes()->whereKey($record->id)->value('status') ?? 'removed').', so it can\'t be removed.']);
            }
        });

        return back()->with('status', 'Expense of '.number_format((float) $record->amount, 2).' removed.');
    }

    /** This employee's own trips, whatever their role may browse elsewhere. */
    protected function mine(Employee $employee): Builder
    {
        return TravelRequest::withoutGlobalScopes()->where('employee_id', $employee->id)->where('company_id', $employee->company_id);
    }

    /** 404 (not 403) for someone else's trip, so the id doesn't confirm it exists. */
    protected function owned(Employee $employee, int $id): TravelRequest
    {
        return $this->mine($employee)->whereKey($id)->firstOrFail();
    }

    /** A settled trip's costs are final: its reimbursement was worked out from these expenses. */
    protected function guardSettled(int $tripId): void
    {
        if (TravelSettlement::withoutGlobalScopes()->where('travel_request_id', $tripId)->where('status', 'settled')->exists()) {
            throw ValidationException::withMessages(['expense' => 'This trip has already been settled, so its expenses can no longer be changed.']);
        }
    }

    protected function validated(Request $request, Employee $employee, ?TravelRequest $existing = null): array
    {
        $first = today()->subDays(self::DAYS_BACK)->toDateString();
        $last = today()->addMonths(self::MONTHS_AHEAD)->toDateString();

        $data = $request->validate([
            'purpose' => ['required', 'string', 'min:3', 'max:255'],
            'from_location' => ['required', 'string', 'max:255'],
            'to_location' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date', 'after_or_equal:'.$first, 'before_or_equal:'.$last],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'mode_of_travel' => ['required', Rule::in(array_keys(TravelRequest::MODES))],
            'estimated_cost' => ['nullable', 'numeric', 'min:0', 'max:'.self::MAX_AMOUNT],
        ], [
            'purpose.*' => 'Say what the trip is for.',
            'start_date.after_or_equal' => 'A trip can be entered at most '.self::DAYS_BACK.' days after it started.',
            'start_date.before_or_equal' => 'A trip can be requested at most '.self::MONTHS_AHEAD.' months ahead.',
            'end_date.after_or_equal' => 'The return date can\'t be before the departure date.',
            'mode_of_travel.*' => 'Choose how you will travel.',
            'estimated_cost.*' => 'The estimated cost must be a number, zero or more.',
        ]);

        $clean = fn (string $value) => trim(preg_replace('/\s+/', ' ', strip_tags($value)));
        [$from, $to] = [$clean($data['from_location']), $clean($data['to_location'])];
        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end = Carbon::parse($data['end_date'])->startOfDay();

        if ($from === '' || $to === '') {
            throw ValidationException::withMessages(['from_location' => 'Enter where the trip starts and where it goes.']);
        }
        if (mb_strtolower($from) === mb_strtolower($to)) {
            throw ValidationException::withMessages(['to_location' => 'The destination is the same as the starting point.']);
        }
        if ($start->diffInDays($end) + 1 > self::MAX_DAYS) {
            throw ValidationException::withMessages(['end_date' => 'A single trip can last at most '.self::MAX_DAYS.' days. Split a longer stay into several requests.']);
        }
        if ($employee->joining_date && $start->lt($employee->joining_date)) {
            throw ValidationException::withMessages(['start_date' => 'That is before you joined ('.$employee->joining_date->format('M j, Y').').']);
        }

        // One person can't be on two trips on the same day.
        $clash = $this->mine($employee)->whereIn('status', ['pending', 'approved'])->when($existing, fn ($q) => $q->whereKeyNot($existing->id))
            ->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start)->first();
        if ($clash) {
            throw ValidationException::withMessages(['start_date' => 'This overlaps your '.$clash->status.' trip from '.$clash->from_location.' to '.$clash->to_location.', '.$clash->start_date->format('M j').' – '.$clash->end_date->format('M j, Y').'.']);
        }

        return [
            'purpose' => $clean($data['purpose']),
            'from_location' => $from,
            'to_location' => $to,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'mode_of_travel' => $data['mode_of_travel'],
            'estimated_cost' => filled($data['estimated_cost'] ?? null) ? round((float) $data['estimated_cost'], 2) : null,
        ];
    }
}
