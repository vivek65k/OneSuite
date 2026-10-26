<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\ExpenseClaim;
use App\Support\BranchContext;
use App\Support\Notifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExpenseReimbursementController extends Controller
{
    /** The most claims shown on the "to pay" list at once; it is grouped by person, so it isn't paged. */
    protected const TO_PAY_LIMIT = 500;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ExpenseClaim::class);

        // Anything that isn't a plain value (e.g. ?tab[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $state = [
            'tab' => $text('tab') === 'paid' ? 'paid' : '',
            'employee_id' => ctype_digit($text('employee_id')) ? (int) $text('employee_id') : null,
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $filtered = fn (string $status) => $this->scoped()->where('status', $status)
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->where(fn ($w) => $w->where('description', 'like', $like)
                    ->orWhereHas('employee', fn ($e) => $e->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('employee_code', 'like', $like)))
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $like)));
            });

        $with = ['employee.department:id,name', 'category:id,name', 'reviewedBy:id,name', 'reimbursedBy:id,name'];
        $paid = null;
        $groups = collect();

        if ($state['tab'] === 'paid') {
            $paid = $filtered('reimbursed')->with($with)->orderByDesc('reimbursed_at')->orderByDesc('id')->paginate(25)->withQueryString();
        } else {
            // One block per person, the one owed for longest first: that is how the money is actually paid out.
            $groups = $filtered('approved')->with($with)->orderBy('reviewed_at')->orderBy('id')->limit(self::TO_PAY_LIMIT)->get()
                ->groupBy('employee_id')->map(fn (Collection $claims) => [
                    'employee' => $claims->first()->employee,
                    'claims' => $claims,
                    'total' => (float) $claims->sum(fn (ExpenseClaim $c) => (float) $c->amount),
                    'since' => $claims->min('reviewed_at'),
                ])->sortBy(fn ($group) => $group['since']?->timestamp ?? PHP_INT_MAX)->values();
        }

        $all = $this->scoped()->whereIn('status', ['approved', 'reimbursed'])->get(['id', 'employee_id', 'amount', 'status', 'reviewed_at', 'reimbursed_at']);
        $owed = $all->where('status', 'approved');
        $done = $all->where('status', 'reimbursed');
        $sum = fn ($claims) => (float) $claims->sum(fn (ExpenseClaim $c) => (float) $c->amount);
        $month = $done->filter(fn (ExpenseClaim $c) => $c->reimbursed_at && $c->reimbursed_at->isSameMonth(today()));
        $year = $done->filter(fn (ExpenseClaim $c) => $c->reimbursed_at && $c->reimbursed_at->year === today()->year);
        // How long people waited for their money, from approval to payment.
        $waits = $year->filter(fn (ExpenseClaim $c) => $c->reviewed_at)->map(fn (ExpenseClaim $c) => max(0, (int) $c->reviewed_at->copy()->startOfDay()->diffInDays($c->reimbursed_at->copy()->startOfDay())));
        $user = $request->user();

        return view('admin.hrm.expenses.reimbursements.index', [
            'state' => $state,
            'groups' => $groups,
            'paid' => $paid,
            'employees' => Employee::query()->where('company_id', Company::current()->id)
                ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
                ->whereIn('id', $all->pluck('employee_id')->unique())->orderBy('name')->pluck('name', 'id'),
            'counts' => ['' => $filtered('approved')->count(), 'paid' => $filtered('reimbursed')->count()],
            'truncated' => $state['tab'] === '' && $filtered('approved')->count() > self::TO_PAY_LIMIT,
            'kpis' => [
                'owed' => $sum($owed),
                'owed_count' => $owed->count(),
                'owed_people' => $owed->pluck('employee_id')->unique()->count(),
                'oldest' => $owed->min('reviewed_at'),
                'month' => $sum($month),
                'month_count' => $month->count(),
                'year' => $sum($year),
                'year_count' => $year->count(),
                'average_wait' => $waits->isNotEmpty() ? (int) round($waits->avg()) : null,
            ],
            'canPay' => $user->can('update', new ExpenseClaim),
            // This login can't pay their own claim (super admin excepted), same as denyOwnRequest().
            'ownEmployeeId' => $user->is_super_admin ? null : Employee::query()->where('user_id', $user->id)->value('id'),
        ]);
    }

    public function reimburse(Request $request, ExpenseClaim $expenseClaim): RedirectResponse
    {
        $this->ensureOwn($expenseClaim);
        $this->authorize('update', $expenseClaim);
        $this->denyOwnRequest($expenseClaim->employee);

        $paidOn = $this->paidOn($request);
        $this->guardDate($expenseClaim, $paidOn);

        $this->pay(collect([$expenseClaim]), $paidOn);

        return back()->with('status', number_format((float) $expenseClaim->amount, 2).' paid back to '.($expenseClaim->employee?->name ?? 'the employee').'.');
    }

    /** Pay several claims in one go, typically everything owed to one person. */
    public function bulk(Request $request): RedirectResponse
    {
        $this->authorize('update', new ExpenseClaim);

        $ids = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:500'], 'ids.*' => ['integer']], ['ids.required' => 'Select at least one claim to pay.'])['ids'];
        $paidOn = $this->paidOn($request);
        $user = $request->user();

        $claims = $this->scoped()->whereKey($ids)->where('status', 'approved')->with('employee.user')->get()
            ->filter(fn (ExpenseClaim $c) => $user->can('update', $c)
                // Never your own claim (same rule as denyOwnRequest() on the single payment).
                && ($user->is_super_admin || (int) $c->employee?->user_id !== (int) $user->id))
            ->values();

        $claims->each(fn (ExpenseClaim $c) => $this->guardDate($c, $paidOn));

        $paid = $claims->isEmpty() ? collect() : $this->pay($claims, $paidOn);
        $skipped = count(array_unique($ids)) - $paid->count();
        $total = (float) $paid->sum(fn (ExpenseClaim $c) => (float) $c->amount);
        $people = $paid->pluck('employee_id')->unique()->count();

        return back()->with('status', ($paid->isEmpty() ? 'Nothing was paid.' : number_format($total, 2).' paid back for '.$paid->count().' '.($paid->count() === 1 ? 'claim' : 'claims').' to '.$people.' '.($people === 1 ? 'person' : 'people').'.')
            .($skipped ? ' Skipped '.$skipped.' that '.($skipped === 1 ? 'was' : 'were').' not approved, already paid or not yours to pay.' : ''));
    }

    /** A payment recorded by mistake: the claim goes back to "approved, to be paid". */
    public function undo(ExpenseClaim $expenseClaim): RedirectResponse
    {
        $this->ensureOwn($expenseClaim);
        $this->authorize('update', $expenseClaim);
        $this->denyOwnRequest($expenseClaim->employee);

        if ($expenseClaim->status !== 'reimbursed') {
            return back()->withErrors(['claim' => 'This claim is not marked as paid.']);
        }

        $expenseClaim->update(['status' => 'approved', 'reimbursed_by_user_id' => null, 'reimbursed_at' => null]);

        return back()->with('status', 'Payment of '.number_format((float) $expenseClaim->amount, 2).' to '.($expenseClaim->employee?->name ?? 'the employee').' undone. The claim is waiting to be paid again.');
    }

    /** Mark claims as paid, refusing any that someone else paid a moment ago. Returns the claims actually paid. */
    protected function pay(Collection $claims, Carbon $paidOn): Collection
    {
        $paid = DB::transaction(function () use ($claims, $paidOn) {
            $rows = ExpenseClaim::withoutGlobalScopes()->whereIn('id', $claims->pluck('id'))->lockForUpdate()->get()->keyBy('id');
            // Today's payments carry the time too; a back-dated one is noon on that day.
            $when = $paidOn->isToday() ? now() : $paidOn->copy()->setTime(12, 0);

            return $claims->filter(function (ExpenseClaim $claim) use ($rows, $when) {
                // Only an approved claim can be paid; one paid a moment ago isn't paid twice.
                if (($rows[$claim->id]->status ?? null) !== 'approved') {
                    return false;
                }

                $claim->update(['status' => 'reimbursed', 'reimbursed_by_user_id' => Auth::id(), 'reimbursed_at' => $when]);

                return true;
            })->values();
        });

        if ($claims->count() === 1 && $paid->isEmpty()) {
            throw ValidationException::withMessages(['claim' => 'Only an approved claim can be paid back. This one is '.$claims->first()->fresh()?->status.'.']);
        }

        // One message per person, however many of their claims were paid.
        $paid->groupBy('employee_id')->each(function (Collection $mine) {
            Notifier::notifyUser(
                $mine->first()->employee?->user,
                'Expenses reimbursed',
                number_format((float) $mine->sum(fn (ExpenseClaim $c) => (float) $c->amount), 2).' has been paid back to you for '.$mine->count().' expense '.($mine->count() === 1 ? 'claim' : 'claims').'.',
                route('admin.my-expenses.index'),
                'ti ti-cash',
            );
        });

        return $paid;
    }

    /** The day the money was paid: today unless an earlier date is given. */
    protected function paidOn(Request $request): Carbon
    {
        $data = $request->validate(['paid_on' => ['nullable', 'date', 'before_or_equal:today']], ['paid_on.before_or_equal' => 'The payment date can\'t be in the future.']);

        return filled($data['paid_on'] ?? null) ? Carbon::parse($data['paid_on'])->startOfDay() : today();
    }

    protected function guardDate(ExpenseClaim $claim, Carbon $paidOn): void
    {
        if ($claim->reviewed_at && $paidOn->lt($claim->reviewed_at->copy()->startOfDay())) {
            throw ValidationException::withMessages(['paid_on' => 'A claim can\'t be paid before it was approved ('.($claim->employee?->name ?? 'this one').'\'s was approved on '.$claim->reviewed_at->format('M j, Y').').']);
        }
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
}
