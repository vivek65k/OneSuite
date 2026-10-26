<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\TravelBooking;
use App\Models\TravelRequest;
use App\Models\TravelSettlement;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TravelBookingController extends Controller
{
    protected const TABS = ['unbooked', 'over', 'past'];

    /** The cost column is decimal(12,2). */
    protected const MAX_COST = 9999999999.99;

    /** A booking may fall this many days outside the trip (a flight the evening before, a late return). */
    protected const SLACK_DAYS = 1;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', TravelBooking::class);

        // Anything that isn't a plain value (e.g. ?tab[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $state = [
            'tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : '',
            'type' => array_key_exists($text('type'), TravelBooking::TYPES) ? $text('type') : '',
            'employee_id' => ctype_digit($text('employee_id')) ? (int) $text('employee_id') : null,
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $all = $this->trips()->withSum('bookings as booked', 'cost')->withCount('bookings')->get(['id', 'employee_id', 'start_date', 'end_date', 'estimated_cost']);
        $open = $all->filter(fn (TravelRequest $t) => $t->end_date->gte(today()));
        $unbooked = $open->where('bookings_count', 0);
        $over = $all->filter(fn (TravelRequest $t) => $t->estimated_cost !== null && (float) $t->booked > (float) $t->estimated_cost);
        $ids = ['' => $open->pluck('id'), 'unbooked' => $unbooked->pluck('id'), 'over' => $over->pluck('id'), 'past' => $all->filter(fn (TravelRequest $t) => $t->end_date->lt(today()))->pluck('id')];

        $filtered = fn (string $tab) => $this->trips()->whereIn('id', $ids[$tab])
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['type'], fn ($q, $type) => $q->whereHas('bookings', fn ($b) => $b->where('booking_type', $type)))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->where(fn ($w) => $w->where('from_location', 'like', $like)->orWhere('to_location', 'like', $like)->orWhere('purpose', 'like', $like)
                    ->orWhereHas('employee', fn ($e) => $e->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('employee_code', 'like', $like)))
                    ->orWhereHas('bookings', fn ($b) => $b->where(fn ($x) => $x->where('provider', 'like', $like)->orWhere('booking_reference', 'like', $like))));
            });

        $trips = $filtered($state['tab'])
            ->with(['employee.department:id,name', 'bookings' => fn ($q) => $q->orderBy('travel_date')->orderBy('id'), 'settlement'])
            // Trips still ahead: the next to leave first. Finished ones: the most recent first.
            ->when($state['tab'] === 'past', fn ($q) => $q->orderByDesc('start_date'), fn ($q) => $q->orderBy('start_date'))->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        $soon = $unbooked->filter(fn (TravelRequest $t) => $t->start_date->lte(today()->addDays(7)));
        $people = Employee::query()->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->whereIn('id', $all->pluck('employee_id')->unique())->orderBy('name')->pluck('name', 'id');

        return view('admin.hrm.travel.bookings.index', [
            'trips' => $trips,
            'state' => $state,
            'employees' => $people,
            'counts' => collect($ids)->map(fn ($list, $tab) => $filtered($tab)->count())->all(),
            'kpis' => [
                'open' => $open->count(),
                'unbooked' => $unbooked->count(),
                'soon' => $soon->count(),
                'booked' => (float) $open->sum(fn (TravelRequest $t) => (float) $t->booked),
                'bookings' => (int) $open->sum('bookings_count'),
                'estimate' => (float) $open->sum(fn (TravelRequest $t) => (float) $t->estimated_cost),
                'over' => $over->count(),
                'over_by' => (float) $over->sum(fn (TravelRequest $t) => (float) $t->booked - (float) $t->estimated_cost),
            ],
            'canCreate' => $request->user()->can('create', TravelBooking::class),
            'slack' => self::SLACK_DAYS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', TravelBooking::class);

        $tripId = $request->input('travel_request_id');
        // Only an approved trip of this login's own company and branch can be booked for.
        $trip = is_scalar($tripId) && ctype_digit((string) $tripId) ? $this->trips()->whereKey((int) $tripId)->with('employee:id,name')->first() : null;

        if (! $trip) {
            throw ValidationException::withMessages(['travel_request_id' => 'Choose an approved trip from the list.']);
        }

        $this->guardSettled($trip);
        $data = $this->validated($request, $trip);

        TravelBooking::create($data + ['company_id' => $trip->company_id, 'travel_request_id' => $trip->id]);

        return back()->with('status', TravelBooking::TYPES[$data['booking_type']].' booking added to '.($trip->employee?->name ?? 'the employee').'\'s trip to '.$trip->to_location.'.');
    }

    public function update(Request $request, TravelBooking $booking): RedirectResponse
    {
        $trip = $this->tripOf($booking);
        $this->authorize('update', $booking);

        $this->guardSettled($trip);
        $booking->update($this->validated($request, $trip, $booking));

        return back()->with('status', 'Booking updated.');
    }

    public function destroy(TravelBooking $booking): RedirectResponse
    {
        $trip = $this->tripOf($booking);
        $this->authorize('delete', $booking);

        $this->guardSettled($trip);
        $booking->delete();

        return back()->with('status', 'Booking removed.');
    }

    protected function validated(Request $request, TravelRequest $trip, ?TravelBooking $existing = null): array
    {
        $data = $request->validate([
            'booking_type' => ['required', Rule::in(array_keys(TravelBooking::TYPES))],
            'provider' => ['nullable', 'string', 'max:255'],
            'booking_reference' => ['nullable', 'string', 'max:255'],
            'cost' => ['required', 'numeric', 'min:0', 'max:'.self::MAX_COST],
            'travel_date' => ['required', 'date'],
            'details' => ['nullable', 'string', 'max:1000'],
        ], ['cost.max' => 'The cost is too large.']);

        $date = Carbon::parse($data['travel_date'])->startOfDay();
        $first = $trip->start_date->copy()->subDays(self::SLACK_DAYS);
        $last = $trip->end_date->copy()->addDays(self::SLACK_DAYS);

        if ($date->lt($first) || $date->gt($last)) {
            throw ValidationException::withMessages(['travel_date' => 'The trip runs '.$trip->start_date->format('M j').' – '.$trip->end_date->format('M j, Y').', so the booking date must be between '.$first->format('M j').' and '.$last->format('M j, Y').'.']);
        }

        $reference = filled($data['booking_reference'] ?? null) ? trim($data['booking_reference']) : null;

        // The same ticket or confirmation number entered twice would count its cost twice.
        if ($reference !== null && TravelBooking::withoutGlobalScopes()->where('travel_request_id', $trip->id)
            ->whereRaw('LOWER(booking_reference) = ?', [mb_strtolower($reference)])
            ->when($existing, fn ($q) => $q->whereKeyNot($existing->id))->exists()) {
            throw ValidationException::withMessages(['booking_reference' => 'Reference "'.$reference.'" is already recorded for this trip.']);
        }

        return [
            'booking_type' => $data['booking_type'],
            'provider' => filled($data['provider'] ?? null) ? trim($data['provider']) : null,
            'booking_reference' => $reference,
            'cost' => round((float) $data['cost'], 2),
            'travel_date' => $date->toDateString(),
            'details' => filled($data['details'] ?? null) ? trim($data['details']) : null,
        ];
    }

    /** A settled trip's costs are final: its settlement was worked out from these bookings. */
    protected function guardSettled(TravelRequest $trip): void
    {
        if (TravelSettlement::withoutGlobalScopes()->where('travel_request_id', $trip->id)->where('status', 'settled')->exists()) {
            throw ValidationException::withMessages(['booking' => 'This trip has already been settled, so its bookings can no longer be changed.']);
        }
    }

    /** The booking's trip, or 404 when it is another company's or branch's (so the URL doesn't confirm it exists). */
    protected function tripOf(TravelBooking $booking): TravelRequest
    {
        $trip = $this->trips()->whereKey($booking->travel_request_id)->first();

        abort_unless($trip, 404);

        return $trip;
    }

    /** Approved trips this login may book for: its own company and, when tied to a branch, that branch's employees. */
    protected function trips(): Builder
    {
        return TravelRequest::query()
            ->where('travel_requests.company_id', Company::current()->id)
            ->where('travel_requests.status', 'approved')
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }
}
