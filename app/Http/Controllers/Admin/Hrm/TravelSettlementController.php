<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\TravelRequest;
use App\Models\TravelSettlement;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TravelSettlementController extends Controller
{
    protected const TABS = ['waiting', 'settled'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', TravelSettlement::class);

        // Anything that isn't a plain value (e.g. ?tab[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $state = [
            'tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : '',
            'employee_id' => ctype_digit($text('employee_id')) ? (int) $text('employee_id') : null,
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $all = $this->summarised($this->trips())->with('settlement')->get();
        $isSettled = fn (TravelRequest $t) => $t->settlement && $t->settlement->status === 'settled';
        $settled = $all->filter($isSettled);
        // Ready: the trip is over and every expense has been approved or rejected.
        $ready = $all->reject($isSettled)->filter(fn (TravelRequest $t) => $t->end_date->lt(today()) && (int) $t->pending_count === 0);
        $waiting = $all->reject($isSettled)->reject(fn (TravelRequest $t) => $ready->contains('id', $t->id));
        $ids = ['' => $ready->pluck('id'), 'waiting' => $waiting->pluck('id'), 'settled' => $settled->pluck('id')];

        $filtered = fn (string $tab) => $this->trips()->whereIn('travel_requests.id', $ids[$tab])
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->where(fn ($w) => $w->where('from_location', 'like', $like)->orWhere('to_location', 'like', $like)->orWhere('purpose', 'like', $like)
                    ->orWhereHas('employee', fn ($e) => $e->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('employee_code', 'like', $like))));
            });

        $trips = $this->summarised($filtered($state['tab']))
            ->with(['employee.department:id,name', 'settlement.settledBy:id,name'])
            // Waiting longest first for work to do; most recent first for what is done.
            ->when($state['tab'] === 'settled', fn ($q) => $q->orderByDesc('end_date'), fn ($q) => $q->orderBy('end_date'))->orderBy('travel_requests.id')
            ->paginate(20)
            ->withQueryString();

        $user = $request->user();
        $thisMonth = $settled->filter(fn (TravelRequest $t) => $t->settlement->settled_date && $t->settlement->settled_date->isSameMonth(today()));
        $thisYear = $settled->filter(fn (TravelRequest $t) => $t->settlement->settled_date && $t->settlement->settled_date->year === today()->year);

        return view('admin.hrm.travel.settlements.index', [
            'trips' => $trips,
            'state' => $state,
            'employees' => Employee::query()->where('company_id', Company::current()->id)
                ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
                ->whereIn('id', $all->pluck('employee_id')->unique())->orderBy('name')->pluck('name', 'id'),
            'counts' => collect($ids)->map(fn ($list, $tab) => $filtered($tab)->count())->all(),
            'kpis' => [
                'ready' => $ready->count(),
                'ready_amount' => (float) $ready->sum(fn (TravelRequest $t) => (float) $t->approved_total),
                'waiting' => $waiting->count(),
                'blocked' => $waiting->filter(fn (TravelRequest $t) => $t->end_date->lt(today()) && (int) $t->pending_count > 0)->count(),
                'month_paid' => (float) $thisMonth->sum(fn (TravelRequest $t) => (float) $t->settlement->reimbursement_amount),
                'month_count' => $thisMonth->count(),
                'year_cost' => (float) $thisYear->sum(fn (TravelRequest $t) => (float) $t->settlement->total_booking_cost + (float) $t->settlement->total_expense_amount),
                'year_count' => $thisYear->count(),
            ],
            'canSettle' => $user->can('update', new TravelSettlement),
            // This login can't settle their own trip (super admin excepted), same as denyOwnRequest().
            'ownEmployeeId' => $user->is_super_admin ? null : Employee::query()->where('user_id', $user->id)->value('id'),
        ]);
    }

    public function settle(Request $request, TravelRequest $travelRequest): RedirectResponse
    {
        $trip = $this->tripFor($travelRequest);
        $this->authorize('update', $trip->settlement ?? new TravelSettlement);
        $this->denyOwnRequest($trip->employee);

        $data = $request->validate([
            'reimbursement_amount' => ['required', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $amount = round((float) $data['reimbursement_amount'], 2);
        $remarks = filled($data['remarks'] ?? null) ? trim($data['remarks']) : null;

        $paid = DB::transaction(function () use ($trip, $amount, $remarks) {
            // Locked so two people can't settle the same trip, and nothing is added to it meanwhile.
            TravelRequest::withoutGlobalScopes()->whereKey($trip->id)->lockForUpdate()->first();
            $existing = TravelSettlement::withoutGlobalScopes()->where('travel_request_id', $trip->id)->lockForUpdate()->first();

            if ($existing && $existing->status === 'settled') {
                throw ValidationException::withMessages(['settlement' => 'This trip was already settled on '.($existing->settled_date?->format('M j, Y') ?? 'an earlier date').'. Reopen it first if it needs correcting.']);
            }
            if ($trip->end_date->gte(today())) {
                throw ValidationException::withMessages(['settlement' => 'This trip runs until '.$trip->end_date->format('M j, Y').'. It can be settled once it is over.']);
            }

            $pending = $trip->expenses()->where('status', 'pending')->count();

            if ($pending) {
                throw ValidationException::withMessages(['settlement' => $pending.' '.($pending === 1 ? 'expense is' : 'expenses are').' still waiting for approval on this trip. Approve or reject '.($pending === 1 ? 'it' : 'them').' first; after settling they can\'t be changed.']);
            }

            $bookings = round((float) $trip->bookings()->sum('cost'), 2);
            $expenses = round((float) $trip->expenses()->where('status', 'approved')->sum('amount'), 2);

            // The employee is paid back what was approved, never more. Less is possible (an advance), with a note.
            if ($amount > $expenses) {
                throw ValidationException::withMessages(['reimbursement_amount' => 'Only '.number_format($expenses, 2).' of expenses were approved on this trip, so the reimbursement can\'t be '.number_format($amount, 2).'.']);
            }
            if ($amount < $expenses && $remarks === null) {
                throw ValidationException::withMessages(['remarks' => 'You are paying back '.number_format($expenses - $amount, 2).' less than the approved expenses. Add a note saying why (for example an advance already paid).']);
            }

            $values = [
                'company_id' => $trip->company_id, 'employee_id' => $trip->employee_id,
                'total_booking_cost' => $bookings, 'total_expense_amount' => $expenses, 'reimbursement_amount' => $amount,
                'status' => 'settled', 'settled_date' => today()->toDateString(), 'settled_by_user_id' => Auth::id(), 'remarks' => $remarks,
            ];
            $existing ? $existing->update($values) : TravelSettlement::create($values + ['travel_request_id' => $trip->id]);

            return $amount;
        });

        return back()->with('status', 'Trip of '.($trip->employee?->name ?? 'the employee').' to '.$trip->to_location.' settled. '.($paid > 0 ? number_format($paid, 2).' to be paid back.' : 'Nothing to pay back.'));
    }

    /** Undo a settlement so the trip's bookings and expenses can be corrected, then settle it again. */
    public function reopen(TravelRequest $travelRequest): RedirectResponse
    {
        $trip = $this->tripFor($travelRequest);
        $settlement = TravelSettlement::withoutGlobalScopes()->where('travel_request_id', $trip->id)->first();

        $this->authorize('update', $settlement ?? new TravelSettlement);
        $this->denyOwnRequest($trip->employee);

        if (! $settlement || $settlement->status !== 'settled') {
            return back()->withErrors(['settlement' => 'This trip is not settled.']);
        }

        $settlement->update(['status' => 'pending', 'settled_date' => null, 'settled_by_user_id' => null]);

        return back()->with('status', 'Settlement reopened. The trip\'s bookings and expenses can be changed again. If '.number_format((float) $settlement->reimbursement_amount, 2).' was already paid, remember to account for it.');
    }

    /** The trip as this login may see it, or 404 for another company's, another branch's or an unapproved one. */
    protected function tripFor(TravelRequest $travelRequest): TravelRequest
    {
        $trip = $this->trips()->whereKey($travelRequest->id)->with(['employee', 'settlement'])->first();

        abort_unless($trip, 404);

        return $trip;
    }

    /** Add what was booked, approved, still pending and rejected to each trip. */
    protected function summarised(Builder $query): Builder
    {
        return $query
            ->withSum('bookings as booked_total', 'cost')
            ->withSum(['expenses as approved_total' => fn ($q) => $q->where('status', 'approved')], 'amount')
            ->withSum(['expenses as pending_total' => fn ($q) => $q->where('status', 'pending')], 'amount')
            ->withCount(['bookings', 'expenses as approved_count' => fn ($q) => $q->where('status', 'approved'), 'expenses as pending_count' => fn ($q) => $q->where('status', 'pending')]);
    }

    /** Approved trips this login may settle: its own company and, when tied to a branch, that branch's employees. */
    protected function trips(): Builder
    {
        return TravelRequest::query()
            ->where('travel_requests.company_id', Company::current()->id)
            ->where('travel_requests.status', 'approved')
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }
}
