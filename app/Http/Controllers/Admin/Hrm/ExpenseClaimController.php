<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ExpenseCategory;
use App\Models\ExpenseClaim;
use App\Support\ApprovalFlow;
use App\Support\BranchContext;
use App\Support\Notifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExpenseClaimController extends Controller
{
    protected const TABS = ['pending', 'approved', 'rejected', 'reimbursed'];

    /** Employees who have left don't get new claims entered for them. */
    protected const INACTIVE = ['terminated', 'inactive'];

    /** A claim can be made for money spent at most this many months ago. */
    protected const MONTHS_BACK = 12;

    /** The amount column is decimal(12,2). */
    protected const MAX_AMOUNT = 9999999999.99;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ExpenseClaim::class);

        // Anything that isn't a plain value (e.g. ?status[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $id = fn (string $key) => ctype_digit($text($key)) ? (int) $text($key) : null;
        // The old page filtered with ?status=; those links keep working.
        $tab = $text('tab') !== '' ? $text('tab') : $text('status');
        $state = [
            'tab' => in_array($tab, self::TABS, true) ? $tab : '',
            'category' => trim(mb_substr($text('category'), 0, 120)),
            'employee_id' => $id('employee_id'),
            'department_id' => $id('department_id'),
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $filtered = fn () => $this->scoped()
            // By name, not id: each branch has its own copy of a category such as "Travel".
            ->when($state['category'] !== '', fn ($q) => $q->whereHas('category', fn ($c) => $c->where('name', $state['category'])))
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['department_id'], fn ($q, $departmentId) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $departmentId)))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->where(fn ($w) => $w->where('description', 'like', $like)
                    ->orWhereHas('employee', fn ($e) => $e->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('employee_code', 'like', $like)))
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $like)));
            });

        $claims = $filtered()
            ->when($state['tab'], fn ($q, $status) => $q->where('status', $status))
            ->with(['employee.department:id,name', 'category:id,name', 'reviewedBy:id,name', 'reimbursedBy:id,name'])
            ->orderByDesc('expense_date')->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $all = $this->scoped()->with('category:id,name')->get(['id', 'employee_id', 'expense_category_id', 'amount', 'expense_date', 'status', 'reimbursed_at', 'created_at']);
        $pending = $all->where('status', 'pending');
        $approved = $all->where('status', 'approved');
        $paidThisMonth = $all->where('status', 'reimbursed')->filter(fn (ExpenseClaim $c) => $c->reimbursed_at && $c->reimbursed_at->isSameMonth(today()));
        $thisMonth = $all->where('status', '!=', 'rejected')->filter(fn (ExpenseClaim $c) => $c->expense_date->isSameMonth(today()));
        $sum = fn ($claims) => (float) $claims->sum(fn (ExpenseClaim $c) => (float) $c->amount);
        $people = $this->employeeQuery()->orderBy('name')->get(['id', 'name', 'employee_code', 'department_id', 'branch_id', 'status', 'user_id']);
        $user = $request->user();
        $canReview = $user->can('update', new ExpenseClaim);
        $canCreate = $user->can('create', ExpenseClaim::class);
        $formPeople = $people->whereNotIn('status', self::INACTIVE)->values();

        return view('admin.hrm.expenses.claims.index', [
            'claims' => $claims,
            'state' => $state,
            'employees' => $people->pluck('name', 'id'),
            'formPeople' => $formPeople,
            'departments' => Department::query()->where('company_id', Company::current()->id)
                ->whereIn('id', $people->pluck('department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            'categoryNames' => $all->pluck('category.name')->filter()->unique()->sort()->values(),
            // Categories belong to a branch, so the form offers each employee their own branch's list.
            'categoriesByBranch' => $canCreate || $canReview ? ExpenseCategory::query()->where('company_id', Company::current()->id)->where('is_active', true)
                ->where(fn ($w) => $w->whereIn('branch_id', $people->pluck('branch_id')->filter()->unique())->orWhereNull('branch_id'))->orderBy('name')->get(['id', 'name', 'branch_id'])
                // A category with no branch is company-wide: it is offered in every branch's list.
                ->pipe(fn ($all) => $people->pluck('branch_id')->filter()->unique()->mapWithKeys(fn ($branchId) => [$branchId => $all
                    ->filter(fn (ExpenseCategory $c) => $c->branch_id === null || (int) $c->branch_id === (int) $branchId)
                    ->map(fn (ExpenseCategory $c) => ['id' => $c->id, 'name' => $c->name])->values()])) : collect(),
            // What each person has claimed this month, for the panel beside the form.
            'monthByEmployee' => $thisMonth->groupBy('employee_id')->map(fn ($group) => ['count' => $group->count(), 'amount' => $sum($group), 'pending' => $group->where('status', 'pending')->count()]),
            // Where each pending claim on this page stands in the approval chain, and whether this login can act on it.
            'stages' => $claims->getCollection()->where('status', 'pending')->mapWithKeys(function (ExpenseClaim $c) use ($user, $canReview) {
                $stage = ApprovalFlow::state($c);
                $own = ! $user->is_super_admin && $c->employee && (int) $c->employee->user_id === (int) $user->id;

                return [$c->id => ['required' => $stage['required'], 'done' => $stage['done'], 'waiting' => $stage['waiting']?->name, 'own' => $own, 'can_act' => $canReview && ! $own && ApprovalFlow::awaits($user, $c, $stage)]];
            }),
            'counts' => ['all' => $filtered()->count()] + collect(self::TABS)->mapWithKeys(fn ($t) => [$t => $filtered()->where('status', $t)->count()])->all(),
            'kpis' => [
                'pending' => $pending->count(),
                'pending_amount' => $sum($pending),
                'oldest' => $pending->min('created_at'),
                'to_pay' => $sum($approved),
                'to_pay_count' => $approved->count(),
                'paid_month' => $sum($paidThisMonth),
                'paid_month_count' => $paidThisMonth->count(),
                'month' => $sum($thisMonth),
                'month_count' => $thisMonth->count(),
                'top' => $thisMonth->groupBy(fn (ExpenseClaim $c) => $c->category?->name ?? 'Uncategorised')->map(fn ($g) => $sum($g))->sortDesc()->keys()->first(),
            ],
            'canCreate' => $canCreate,
            'limits' => ['min' => today()->subMonths(self::MONTHS_BACK)->toDateString(), 'max' => today()->toDateString()],
        ]);
    }

    public function approval(Request $request): View
    {
        $this->authorize('viewAny', ExpenseClaim::class);

        // Anything that isn't a plain value (e.g. ?employee_id[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $id = fn (string $key) => ctype_digit($text($key)) ? (int) $text($key) : null;
        $state = [
            'window' => in_array($text('window'), ['week', 'large'], true) ? $text('window') : '',
            'sort' => in_array($text('sort'), ['amount', 'newest'], true) ? $text('sort') : '',
            'category' => trim(mb_substr($text('category'), 0, 120)),
            'employee_id' => $id('employee_id'),
            'department_id' => $id('department_id'),
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $waiting = $this->scoped()->where('status', 'pending')->with('category:id,name')->get(['id', 'employee_id', 'expense_category_id', 'amount', 'expense_date', 'created_at']);
        // "Large" is relative to this company's own claims: the top quarter of what is waiting, and at least 3 claims to compare.
        $amounts = $waiting->map(fn (ExpenseClaim $c) => (float) $c->amount)->sort()->values();
        $largeFrom = $amounts->count() >= 3 ? (float) $amounts[(int) floor($amounts->count() * 0.75)] : null;

        $filtered = fn () => $this->scoped()->where('status', 'pending')
            ->when($state['category'] !== '', fn ($q) => $q->whereHas('category', fn ($c) => $c->where('name', $state['category'])))
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['department_id'], fn ($q, $departmentId) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $departmentId)))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->where(fn ($w) => $w->where('description', 'like', $like)
                    ->orWhereHas('employee', fn ($e) => $e->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('employee_code', 'like', $like)))
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $like)));
            });
        $windowed = fn (Builder $q, string $window) => match ($window) {
            // Waiting more than a week.
            'week' => $q->where('created_at', '<', today()->subDays(7)),
            'large' => $largeFrom === null ? $q->whereRaw('1 = 0') : $q->where('amount', '>=', $largeFrom),
            default => $q,
        };

        $claims = $windowed($filtered(), $state['window'])
            ->with(['employee.department:id,name', 'category:id,name'])
            ->when($state['sort'] === 'amount', fn ($q) => $q->orderByDesc('amount'))
            ->when($state['sort'] === 'newest', fn ($q) => $q->orderByDesc('created_at'), fn ($q) => $q->orderBy('created_at'))
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $shown = $claims->getCollection();
        $employeeIds = $shown->pluck('employee_id')->unique();
        $user = $request->user();
        $canReview = $user->can('update', new ExpenseClaim);
        // Everything else these people have claimed, to put each claim in context.
        $history = $this->scoped()->whereIn('employee_id', $employeeIds)->where('status', '!=', 'rejected')
            ->whereDate('expense_date', '>=', today()->subMonths(self::MONTHS_BACK))
            ->get(['id', 'employee_id', 'expense_category_id', 'amount', 'expense_date', 'status'])->groupBy('employee_id');
        $people = $this->employeeQuery()->orderBy('name')->get(['id', 'name', 'department_id']);

        return view('admin.hrm.expenses.approval', [
            'claims' => $claims,
            'state' => $state,
            'employees' => $people->whereIn('id', $waiting->pluck('employee_id')->unique())->pluck('name', 'id'),
            'departments' => Department::query()->where('company_id', Company::current()->id)
                ->whereIn('id', $people->pluck('department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            'categoryNames' => $waiting->pluck('category.name')->filter()->unique()->sort()->values(),
            'largeFrom' => $largeFrom,
            'context' => $shown->mapWithKeys(function (ExpenseClaim $c) use ($history) {
                $others = $history->get($c->employee_id, collect())->where('id', '!=', $c->id);
                $month = $others->filter(fn (ExpenseClaim $o) => $o->expense_date->isSameMonth($c->expense_date));
                $sameCategory = $others->where('expense_category_id', $c->expense_category_id);

                return [$c->id => [
                    'month_count' => $month->count(),
                    'month_amount' => (float) $month->sum(fn (ExpenseClaim $o) => (float) $o->amount),
                    // What this person usually claims in this category, when there is something to compare with.
                    'usual' => $sameCategory->count() >= 2 ? (float) $sameCategory->avg(fn (ExpenseClaim $o) => (float) $o->amount) : null,
                    // Another claim in the same category on the same day: possibly the same receipt split or entered twice.
                    'same_day' => $sameCategory->filter(fn (ExpenseClaim $o) => $o->expense_date->isSameDay($c->expense_date))->values(),
                ]];
            }),
            // Where each claim stands in the approval chain, and whether this login can act on it.
            'stages' => $shown->mapWithKeys(function (ExpenseClaim $c) use ($user, $canReview) {
                $stage = ApprovalFlow::state($c);
                $own = ! $user->is_super_admin && $c->employee && (int) $c->employee->user_id === (int) $user->id;

                return [$c->id => ['required' => $stage['required'], 'done' => $stage['done'], 'waiting' => $stage['waiting']?->name, 'own' => $own, 'can_act' => $canReview && ! $own && ApprovalFlow::awaits($user, $c, $stage)]];
            }),
            'counts' => ['all' => $filtered()->count(), 'week' => $windowed($filtered(), 'week')->count(), 'large' => $windowed($filtered(), 'large')->count()],
            'kpis' => [
                'waiting' => $waiting->count(),
                'amount' => (float) $amounts->sum(),
                'people' => $waiting->pluck('employee_id')->unique()->count(),
                'old' => $waiting->filter(fn (ExpenseClaim $c) => $c->created_at && $c->created_at->lt(today()->subDays(7)))->count(),
                'oldest' => $waiting->min('created_at'),
                'largest' => $amounts->isNotEmpty() ? (float) $amounts->last() : null,
            ],
            'canReview' => $canReview,
        ]);
    }

    /** Approve several claims at once. Each one is checked exactly like a single approval; the rest are skipped. */
    public function bulkApprove(Request $request): RedirectResponse
    {
        $this->authorize('update', new ExpenseClaim);

        $ids = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:200'], 'ids.*' => ['integer']], ['ids.required' => 'Select at least one claim.'])['ids'];
        $user = $request->user();
        $approved = 0;
        $forwarded = 0;
        $total = 0.0;

        foreach ($this->scoped()->whereKey($ids)->where('status', 'pending')->with('employee.user')->get() as $expenseClaim) {
            $reviewable = $user->can('update', $expenseClaim)
                // Never your own claim (same rule as denyOwnRequest() on the single approve).
                && ($user->is_super_admin || (int) $expenseClaim->employee?->user_id !== (int) $user->id)
                // Already approved at this manager's level: nothing for them to add.
                && ApprovalFlow::awaits($user, $expenseClaim);

            if (! $reviewable) {
                continue;
            }

            try {
                if ($this->settle($expenseClaim) === null) {
                    $approved++;
                    $total += (float) $expenseClaim->amount;
                } else {
                    $forwarded++;
                }
            } catch (ValidationException) {
                // Someone else dealt with it in the meantime.
            }
        }

        $skipped = count(array_unique($ids)) - $approved - $forwarded;

        return back()->with('status', 'Approved '.$approved.' '.($approved === 1 ? 'claim' : 'claims').($approved ? ' ('.number_format($total, 2).')' : '').'.'
            .($forwarded ? ' '.$forwarded.' moved to the next approval level.' : '')
            .($skipped ? ' Skipped '.$skipped.' that '.($skipped === 1 ? 'was' : 'were').' not yours to approve or no longer pending.' : ''));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ExpenseClaim::class);

        $employeeId = $request->input('employee_id');
        $employee = is_scalar($employeeId) && ctype_digit((string) $employeeId)
            ? $this->employeeQuery()->whereNotIn('status', self::INACTIVE)->whereKey((int) $employeeId)->first(['id', 'name', 'branch_id', 'user_id'])
            : null;

        if (! $employee) {
            throw ValidationException::withMessages(['employee_id' => 'Choose an employee from the list.']);
        }

        $data = $this->validated($request, $employee);
        $expenseClaim = ExpenseClaim::create($data + ['company_id' => Company::current()->id, 'employee_id' => $employee->id, 'status' => 'pending']);

        Notifier::notifyUsersWithPermission(
            'update_expense_claim',
            'New expense claim',
            "{$employee->name} submitted a claim for ".number_format((float) $expenseClaim->amount, 2).'.',
            route('admin.expense-approval.index'),
            'ti ti-receipt-2',
            $employee->branch_id,
            employeeId: $employee->id,
        );

        return back()->with('status', 'Claim of '.number_format((float) $expenseClaim->amount, 2).' for '.$employee->name.' submitted. It is waiting for approval.');
    }

    public function update(Request $request, ExpenseClaim $expenseClaim): RedirectResponse
    {
        $this->ensureOwn($expenseClaim);
        $this->authorize('update', $expenseClaim);

        if ($expenseClaim->status !== 'pending') {
            return back()->withErrors(['claim' => 'This claim has already been '.$expenseClaim->status.', so it can\'t be changed.']);
        }

        $expenseClaim->update($this->validated($request, $expenseClaim->employee, $expenseClaim));

        return back()->with('status', 'Expense claim updated.');
    }

    public function approve(ExpenseClaim $expenseClaim): RedirectResponse
    {
        $this->ensureOwn($expenseClaim);
        $this->authorize('update', $expenseClaim);
        $this->denyOwnRequest($expenseClaim->employee);

        $waiting = $this->settle($expenseClaim);

        return back()->with('status', $waiting ?? 'Claim of '.number_format((float) $expenseClaim->amount, 2).' for '.($expenseClaim->employee?->name ?? 'the employee').' approved. It can now be reimbursed.');
    }

    public function reject(Request $request, ExpenseClaim $expenseClaim): RedirectResponse
    {
        $this->ensureOwn($expenseClaim);
        $this->authorize('update', $expenseClaim);
        $this->denyOwnRequest($expenseClaim->employee);

        $remarks = is_scalar($request->input('remarks')) && trim((string) $request->input('remarks')) !== '' ? mb_substr(trim((string) $request->input('remarks')), 0, 1000) : null;

        DB::transaction(function () use ($expenseClaim, $remarks) {
            $this->lockPending($expenseClaim);
            ApprovalFlow::rejected($expenseClaim, $remarks);
            $expenseClaim->update(['status' => 'rejected', 'reviewed_by_user_id' => Auth::id(), 'reviewed_at' => now(), 'remarks' => $remarks]);
        });

        Notifier::notifyUser(
            $expenseClaim->employee?->user,
            'Expense claim rejected',
            'Your claim for '.number_format((float) $expenseClaim->amount, 2).' was rejected.'.($remarks ? " Reason: {$remarks}" : ''),
            route('admin.my-expenses.index'),
            'ti ti-circle-x',
        );

        return back()->with('status', 'Claim of '.number_format((float) $expenseClaim->amount, 2).' for '.($expenseClaim->employee?->name ?? 'the employee').' rejected.');
    }

    public function destroy(ExpenseClaim $expenseClaim): RedirectResponse
    {
        $this->ensureOwn($expenseClaim);
        $this->authorize('delete', $expenseClaim);

        // A reimbursed claim is a record of money paid out.
        if ($expenseClaim->status === 'reimbursed') {
            return back()->withErrors(['claim' => 'This claim has been reimbursed, so it can\'t be deleted.']);
        }

        $expenseClaim->delete();

        return back()->with('status', 'Expense claim removed.');
    }

    /**
     * Record this login's approval. Returns null when the claim is now
     * approved, or the "moved up a level" message when someone further up
     * still has to approve it.
     */
    protected function settle(ExpenseClaim $expenseClaim): ?string
    {
        $waiting = DB::transaction(function () use ($expenseClaim) {
            $this->lockPending($expenseClaim);

            // Multi-level approval: stop here while a manager further up still has to approve.
            if ($waiting = ApprovalFlow::forward($expenseClaim)) {
                return $waiting;
            }

            $expenseClaim->update(['status' => 'approved', 'reviewed_by_user_id' => Auth::id(), 'reviewed_at' => now()]);

            return null;
        });

        if ($waiting === null) {
            Notifier::notifyUser(
                $expenseClaim->employee?->user,
                'Expense claim approved',
                'Your claim for '.number_format((float) $expenseClaim->amount, 2).' was approved.',
                route('admin.my-expenses.index'),
                'ti ti-circle-check',
            );
        }

        return $waiting;
    }

    /** Lock the claim and make sure nobody decided it a moment ago (or the button was pressed twice). */
    protected function lockPending(ExpenseClaim $expenseClaim): void
    {
        $row = ExpenseClaim::withoutGlobalScopes()->whereKey($expenseClaim->id)->lockForUpdate()->first();

        if ($row->status !== 'pending') {
            throw ValidationException::withMessages(['claim' => 'This claim has already been '.$row->status.'.']);
        }
    }

    protected function validated(Request $request, ?Employee $employee, ?ExpenseClaim $existing = null): array
    {
        $data = $request->validate([
            'expense_category_id' => ['required'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.self::MAX_AMOUNT],
            'expense_date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.today()->subMonths(self::MONTHS_BACK)->toDateString()],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'expense_category_id.required' => 'Choose what the money was spent on.',
            'amount.min' => 'The amount must be more than zero.',
            'amount.max' => 'The amount is too large.',
            'expense_date.before_or_equal' => 'An expense can\'t be dated in the future.',
            'expense_date.after_or_equal' => 'A claim can be made for money spent in the last '.self::MONTHS_BACK.' months.',
        ]);

        // The category has to be an active one from the employee's own branch (or the one the claim already has).
        $categoryId = is_scalar($data['expense_category_id']) && ctype_digit((string) $data['expense_category_id']) ? (int) $data['expense_category_id'] : 0;
        $category = ExpenseCategory::query()->where('company_id', Company::current()->id)->whereKey($categoryId)->first();
        $keeps = $existing && (int) $existing->expense_category_id === $categoryId;

        if (! $category || (! $keeps && (! $category->is_active || ($category->branch_id !== null && $employee && (int) $category->branch_id !== (int) $employee->branch_id)))) {
            throw ValidationException::withMessages(['expense_category_id' => 'Choose a category from the list.']);
        }

        $amount = round((float) $data['amount'], 2);
        $date = Carbon::parse($data['expense_date'])->toDateString();

        // The same expense entered twice would be paid twice.
        if ($employee && ExpenseClaim::withoutGlobalScopes()->where('company_id', Company::current()->id)->where('employee_id', $employee->id)
            ->where('expense_category_id', $category->id)->whereDate('expense_date', $date)->where('amount', $amount)->where('status', '!=', 'rejected')
            ->when($existing, fn ($q) => $q->whereKeyNot($existing->id))->exists()) {
            throw ValidationException::withMessages(['amount' => $employee->name.' already has a '.$category->name.' claim of '.number_format($amount, 2).' for '.Carbon::parse($date)->format('M j, Y').'.']);
        }

        return [
            'expense_category_id' => $category->id,
            'amount' => $amount,
            'expense_date' => $date,
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
        ];
    }

    /** 404 (not 403) for another company's or branch's claim, so the URL doesn't confirm it exists. */
    protected function ensureOwn(ExpenseClaim $expenseClaim): void
    {
        abort_unless($this->scoped()->whereKey($expenseClaim->id)->exists(), 404);
    }

    /** Claims this login may see: its own company and, when tied to a branch, that branch's employees. */
    protected function scoped(): Builder
    {
        return ExpenseClaim::query()
            ->where('expense_claims.company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }

    protected function employeeQuery(): Builder
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }
}
