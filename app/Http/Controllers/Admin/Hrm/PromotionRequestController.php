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
use App\Support\Notifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PromotionRequestController extends Controller
{
    /** People who have left can't be promoted. */
    protected const INACTIVE = ['terminated', 'inactive'];

    /** A request can ask for a promotion this far ahead; it is applied on approval, never before its date. */
    protected const MONTHS_AHEAD = 6;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PromotionRequest::class);

        // Anything that isn't a plain value (e.g. ?search[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $id = fn (string $key) => ctype_digit($text($key)) ? (int) $text($key) : null;
        $state = [
            'status' => array_key_exists($text('status'), PromotionRequest::STATUSES) ? $text('status') : '',
            'employee_id' => $id('employee_id'),
            'department_id' => $id('department_id'),
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $filtered = fn () => $this->scoped()
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['department_id'], fn ($q, $departmentId) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $departmentId)))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->where(fn ($w) => $w->where('to_designation', 'like', $like)
                    ->orWhereHas('employee', fn ($e) => $e->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('employee_code', 'like', $like))));
            });

        $requests = $filtered()
            ->when($state['status'], fn ($q, $status) => $q->where('status', $status))
            ->with(['employee.department:id,name', 'reviewedBy:id,name'])
            ->orderByRaw("status = 'pending' desc")->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $all = $this->scoped()->get(['id', 'employee_id', 'status', 'created_at', 'reviewed_at']);
        $pending = $all->where('status', 'pending');
        $decided = $all->whereIn('status', ['approved', 'rejected'])->filter(fn (PromotionRequest $r) => $r->reviewed_at?->year === today()->year);
        $byStatus = $filtered()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $people = $this->employeeQuery()->with('department:id,name')->orderBy('name')
            ->get(['id', 'name', 'employee_code', 'avatar', 'department_id', 'designation', 'joining_date', 'status', 'user_id']);
        $active = $people->whereNotIn('status', self::INACTIVE)->values();
        $canCreate = $request->user()->can('create', PromotionRequest::class);
        // Last promotion and last appraisal rating: what an approver wants to see beside the request.
        $ids = $active->pluck('id')->merge($requests->pluck('employee_id'))->unique();
        $lastPromotion = EmployeePromotion::query()->whereIn('employee_id', $ids)->orderBy('promotion_date')->orderBy('id')->get()->groupBy('employee_id')->map->last();
        $ratings = Appraisal::query()->whereIn('employee_id', $ids)->where('status', 'completed')->whereNotNull('overall_rating')
            ->orderBy('completed_at')->get(['employee_id', 'overall_rating'])->groupBy('employee_id')->map->last();
        $context = $people->whereIn('id', $ids)->mapWithKeys(function (Employee $e) use ($lastPromotion, $ratings) {
            $last = $lastPromotion->get($e->id);
            $since = $last?->promotion_date ?? $e->joining_date;

            return [$e->id => [
                'name' => $e->name,
                'designation' => trim((string) $e->designation),
                'since' => $since?->format('M j, Y'),
                'months' => $since ? (int) $since->diffInMonths(today()) : null,
                'sinceKind' => $last ? 'last promoted' : 'joined',
                'lastDate' => $last?->promotion_date->toDateString(),
                'joined' => $e->joining_date?->toDateString(),
                'rating' => ($r = $ratings->get($e->id)) ? rtrim(rtrim(number_format((float) $r->overall_rating, 2), '0'), '.') : null,
            ]];
        });

        return view('admin.hrm.approvals.promotion-requests', [
            'requests' => $requests,
            'state' => $state,
            'employees' => $people->pluck('name', 'id'),
            'departments' => Department::query()->where('company_id', Company::current()->id)
                ->whereIn('id', $people->pluck('department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            'counts' => [
                'all' => (int) $byStatus->sum(),
                'pending' => (int) ($byStatus['pending'] ?? 0),
                'approved' => (int) ($byStatus['approved'] ?? 0),
                'rejected' => (int) ($byStatus['rejected'] ?? 0),
            ],
            'kpis' => [
                'pending' => $pending->count(),
                'oldest_days' => $pending->isNotEmpty() ? (int) $pending->min('created_at')->startOfDay()->diffInDays(today()) : null,
                'approved' => $decided->where('status', 'approved')->count(),
                'rejected' => $decided->where('status', 'rejected')->count(),
                'rate' => $decided->isNotEmpty() ? (int) round($decided->where('status', 'approved')->count() / $decided->count() * 100) : null,
            ],
            'context' => $context,
            'canCreate' => $canCreate,
            'payable' => $active,
            'pendingFor' => $pending->pluck('employee_id')->flip(),
            'designations' => $people->pluck('designation')->filter()->map(fn ($d) => trim($d))->unique()->sort()->values(),
            'myEmployeeId' => Auth::user()->is_super_admin ? null : $people->firstWhere('user_id', Auth::id())?->id,
            'maxDate' => today()->addMonths(self::MONTHS_AHEAD)->toDateString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', PromotionRequest::class);

        $companyId = Company::current()->id;

        $personId = $request->input('employee_id');
        // Only someone this login may manage: its own company and, when tied to a branch, that branch.
        $employee = is_scalar($personId) && ctype_digit((string) $personId) ? $this->employeeQuery()->whereKey((int) $personId)->first() : null;
        if (! $employee) {
            throw ValidationException::withMessages(['employee_id' => 'Choose an employee from the list.']);
        }

        $data = $request->validate([
            'to_designation' => ['required', 'string', 'max:255'],
            'promotion_date' => ['required', 'date', 'after_or_equal:'.today()->subYear()->toDateString(), 'before_or_equal:'.today()->addMonths(self::MONTHS_AHEAD)->toDateString()],
            'notes' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'promotion_date.before_or_equal' => 'A promotion can be requested at most '.self::MONTHS_AHEAD.' months ahead.',
            'promotion_date.after_or_equal' => 'The effective date can be at most one year back.',
            'notes.*' => 'Say why they should be promoted (5 to 1,000 characters). The approver has only this to go on.',
        ]);

        $to = $this->title($data['to_designation']);
        $date = Carbon::parse($data['promotion_date']);

        DB::transaction(function () use ($employee, $to, $date, $data, $companyId) {
            // One at a time per person, so two quick submits can't both pass the checks.
            $person = Employee::withoutGlobalScopes()->whereKey($employee->id)->lockForUpdate()->first();

            if ($problem = $this->problem($person, $to, $date, false)) {
                throw ValidationException::withMessages([$problem[0] => $problem[1]]);
            }

            if (PromotionRequest::withoutGlobalScopes()->where('employee_id', $person->id)->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['employee_id' => $person->name.' already has a promotion request waiting for a decision. Decide that one first.']);
            }

            PromotionRequest::create([
                'company_id' => $companyId,
                'employee_id' => $person->id,
                'from_designation' => filled($person->designation) ? trim($person->designation) : null,
                'to_designation' => $to,
                'promotion_date' => $date->toDateString(),
                'notes' => trim(strip_tags($data['notes'])),
                'status' => 'pending',
            ]);
        });

        Notifier::notifyUsersWithPermission(
            'update_promotion_request',
            'Promotion to approve',
            Auth::user()->name.' put '.$employee->name.' forward for '.$to.'.',
            route('admin.promotion-requests.index'),
            'ti ti-arrow-up-circle',
            $employee->branch_id,
            employeeId: $employee->id,
        );

        return back()->with('status', 'Promotion request for '.$employee->name.' submitted. It now waits for approval.');
    }

    public function approve(Request $request, PromotionRequest $promotionRequest): RedirectResponse
    {
        $this->ensureOwn($promotionRequest);
        $this->authorize('update', $promotionRequest);
        $this->guardSelfReview($promotionRequest);

        // The approver may adjust the title or the date; blank keeps what was asked for.
        $data = $request->validate([
            'to_designation' => ['nullable', 'string', 'max:255'],
            'promotion_date' => ['nullable', 'date', 'after_or_equal:'.today()->subYear()->toDateString()],
            'remarks' => ['nullable', 'string', 'max:500'],
        ], ['promotion_date.after_or_equal' => 'The effective date can be at most one year back.']);

        if ($promotionRequest->status !== 'pending') {
            return back()->withErrors(['approve' => 'This request has already been decided.']);
        }

        $employee = $promotionRequest->employee;
        abort_unless($employee, 404);

        $to = filled($data['to_designation'] ?? null) ? $this->title($data['to_designation']) : trim($promotionRequest->to_designation);
        $date = Carbon::parse($data['promotion_date'] ?? $promotionRequest->promotion_date)->startOfDay();

        if ($problem = $this->problem($employee, $to, $date, true)) {
            return back()->withErrors(['approve' => 'Not approved. '.$problem[1]]);
        }

        // The title they hold today, which may have changed since the request was filed.
        $from = filled($employee->designation) ? trim($employee->designation) : null;
        $changed = mb_strtolower($to) !== mb_strtolower(trim($promotionRequest->to_designation)) || ! $date->isSameDay($promotionRequest->promotion_date);
        $note = collect([
            $changed ? 'Asked for '.$promotionRequest->to_designation.' from '.$promotionRequest->promotion_date->format('M j, Y').'.' : null,
            filled($data['remarks'] ?? null) ? trim(strip_tags($data['remarks'])) : null,
        ])->filter()->implode(' ');

        $done = DB::transaction(function () use ($promotionRequest, $employee, $from, $to, $date, $note) {
            // Locked re-read: two clicks (or two approvers) must not promote on the same request twice.
            $fresh = PromotionRequest::query()->whereKey($promotionRequest->id)->lockForUpdate()->first();

            if (! $fresh || $fresh->status !== 'pending') {
                return false;
            }

            // Checked again under lock: the person may have been promoted, or have left, a moment ago.
            $person = Employee::withoutGlobalScopes()->whereKey($employee->id)->lockForUpdate()->first();
            if ($problem = $this->problem($person, $to, $date, true)) {
                throw ValidationException::withMessages(['approve' => 'Not approved. '.$problem[1]]);
            }

            $employee->promotions()->create([
                'from_designation' => $from,
                'to_designation' => $to,
                'promotion_date' => $date->toDateString(),
                'notes' => $fresh->notes,
            ]);
            $employee->update(['designation' => $to]);

            $fresh->update([
                'from_designation' => $from,
                'to_designation' => $to,
                'promotion_date' => $date->toDateString(),
                'status' => 'approved',
                'reviewed_by_user_id' => Auth::id(),
                'reviewed_at' => now(),
                'remarks' => $note !== '' ? $note : null,
            ]);

            return true;
        });

        if (! $done) {
            return back()->withErrors(['approve' => 'This request has already been decided.']);
        }

        Notifier::notifyUser(
            $employee->user,
            'You have been promoted',
            'Your promotion to '.$to.' was approved, effective '.$date->format('M j, Y').'.',
            route('admin.my-profile.index'),
            'ti ti-arrow-up-circle',
        );

        return back()->with('status', 'Approved. '.$employee->name.' is now '.$to.($from ? ' (was '.$from.')' : '').'.');
    }

    public function reject(Request $request, PromotionRequest $promotionRequest): RedirectResponse
    {
        $this->ensureOwn($promotionRequest);
        $this->authorize('update', $promotionRequest);
        $this->guardSelfReview($promotionRequest);

        $data = $request->validate(['remarks' => ['required', 'string', 'min:3', 'max:500']], ['remarks.*' => 'Say why the request is rejected (3 to 500 characters).']);

        $done = PromotionRequest::query()->whereKey($promotionRequest->id)->where('status', 'pending')->update([
            'status' => 'rejected',
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at' => now(),
            'remarks' => trim(strip_tags($data['remarks'])),
        ]);

        if (! $done) {
            return back()->withErrors(['reject' => 'This request has already been decided.']);
        }

        Notifier::notifyUser(
            $promotionRequest->employee?->user,
            'Promotion request not approved',
            'The request to promote you to '.$promotionRequest->to_designation.' was not approved.',
            route('admin.my-profile.index'),
            'ti ti-arrow-up-circle',
        );

        return back()->with('status', 'Promotion request for '.($promotionRequest->employee?->name ?? 'the employee').' rejected.');
    }

    /** Take back a request that hasn't been decided. */
    public function destroy(PromotionRequest $promotionRequest): RedirectResponse
    {
        $this->ensureOwn($promotionRequest);
        $this->authorize('delete', $promotionRequest);

        // The delete is conditional, so a withdrawal racing an approval can't erase the record of a promotion that happened.
        if (! PromotionRequest::query()->whereKey($promotionRequest->id)->where('status', 'pending')->delete()) {
            return back()->withErrors(['withdraw' => 'This request has already been decided, so it stays on record.']);
        }

        return back()->with('status', 'Promotion request for '.($promotionRequest->employee?->name ?? 'the employee').' withdrawn.');
    }

    /** A designation as typed, without markup or stray spaces. */
    protected function title(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags($value)));
    }

    /**
     * Why this promotion can't go ahead, as [field, message], or null when it can.
     * $applying is true at approval: the promotion takes effect now, so the date
     * must have arrived and must come after the person's latest promotion.
     */
    protected function problem(Employee $employee, string $to, Carbon $date, bool $applying): ?array
    {
        $date = $date->copy()->startOfDay();

        if (in_array($employee->status, self::INACTIVE, true)) {
            return ['employee_id', $employee->name.' is no longer with the company.'];
        }

        if ($to === '') {
            return ['to_designation', 'Enter the new designation.'];
        }

        if (mb_strtolower($to) === mb_strtolower(trim((string) $employee->designation))) {
            return ['to_designation', $employee->name.' is already "'.$to.'". Enter the new designation.'];
        }

        if ($employee->joining_date && $date->lt($employee->joining_date->copy()->startOfDay())) {
            return ['promotion_date', $employee->name.' joined on '.$employee->joining_date->format('M j, Y').'. A promotion can\'t be dated before that.'];
        }

        $latest = EmployeePromotion::query()->where('employee_id', $employee->id)->orderByDesc('promotion_date')->first();

        if ($latest && $date->lte($latest->promotion_date)) {
            return ['promotion_date', $employee->name.' was last promoted on '.$latest->promotion_date->format('M j, Y').'. This one must be dated after that.'];
        }

        if ($applying && $date->gt(today())) {
            return ['promotion_date', 'The effective date ('.$date->format('M j, Y').') has not arrived yet, and approving changes the designation straight away. Approve it on or after that date, or pick an earlier date.'];
        }

        return null;
    }

    /** Nobody decides their own promotion. A super admin is the one exception, or a one-person company could never approve anything. */
    protected function guardSelfReview(PromotionRequest $promotionRequest): void
    {
        $user = Auth::user();

        abort_if(! $user->is_super_admin && $promotionRequest->employee && (int) $promotionRequest->employee->user_id === (int) $user->id, 403, 'You can\'t decide your own promotion request.');
    }

    /** 404 (not 403) for another company's or branch's request, so the URL doesn't confirm it exists. */
    protected function ensureOwn(PromotionRequest $promotionRequest): void
    {
        abort_unless($this->scoped()->whereKey($promotionRequest->id)->exists(), 404);
    }

    protected function scoped(): Builder
    {
        return PromotionRequest::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }

    protected function employeeQuery(): Builder
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }
}
