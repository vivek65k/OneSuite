<?php

namespace App\Http\Controllers\Admin\Ess;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\ApprovalStep;
use App\Models\Employee;
use App\Models\ExpenseCategory;
use App\Models\ExpenseClaim;
use App\Support\ApprovalFlow;
use App\Support\Notifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MyExpenseController extends Controller
{
    use ResolvesCurrentEmployee;

    protected const TABS = ['pending', 'approved', 'reimbursed', 'rejected'];

    /** People who have left can't file new claims. */
    protected const INACTIVE = ['terminated', 'inactive'];

    /** How far back money spent can still be claimed; the same limit HR's claim form uses. */
    public const MONTHS_BACK = 12;

    protected const MAX_AMOUNT = 9999999999.99;

    public function index(Request $request): View
    {
        $employee = $this->currentEmployee();

        // Anything that isn't a plain value (e.g. ?tab[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $state = ['tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : ''];

        $all = $this->mine($employee)->with(['category:id,name', 'reviewedBy:id,name'])->orderByDesc('expense_date')->orderByDesc('id')->get();
        $tabbed = fn (Collection $rows, string $tab) => $tab === '' ? $rows : $rows->where('status', $tab);
        $rows = $tabbed($all, $state['tab'])->values();
        // Who a pending claim is waiting on, and whether anyone has approved it yet (after which it can't be changed).
        $flow = $all->where('status', 'pending')->mapWithKeys(function (ExpenseClaim $c) {
            $s = ApprovalFlow::state($c);

            return [$c->id => ['name' => $s['waiting']?->name, 'done' => $s['done'], 'required' => $s['required']]];
        });
        $sum = fn (string $status) => (float) $all->where('status', $status)->sum('amount');
        $categories = $this->categories($employee)->orderBy('name')->get(['id', 'name', 'description']);
        $paidThisYear = $all->where('status', 'reimbursed')->filter(fn (ExpenseClaim $c) => ($c->reimbursed_at ?? $c->expense_date)?->year === now()->year);

        return view('admin.ess.expenses', [
            'state' => $state,
            'claims' => $rows,
            'flow' => $flow,
            'counts' => ['all' => $all->count()] + collect(self::TABS)->mapWithKeys(fn ($tab) => [$tab => $tabbed($all, $tab)->count()])->all(),
            'kpis' => [
                'pending' => $sum('pending'),
                'pending_count' => $all->where('status', 'pending')->count(),
                'oldest_pending' => $all->where('status', 'pending')->min('created_at'),
                'approved' => $sum('approved'),
                'approved_count' => $all->where('status', 'approved')->count(),
                'paid_year' => (float) $paidThisYear->sum('amount'),
                'paid_year_count' => $paidThisYear->count(),
                'rejected_count' => $all->where('status', 'rejected')->count(),
                'rejected' => $sum('rejected'),
            ],
            'categories' => $categories,
            'canClaim' => ! in_array($employee->status, self::INACTIVE, true) && $categories->isNotEmpty(),
            'employeeStatus' => $employee->status,
            'minDate' => today()->subMonths(self::MONTHS_BACK)->toDateString(),
            // What the form checks against before sending, so a double entry is caught while typing.
            'existing' => $all->where('status', '!=', 'rejected')->map(fn (ExpenseClaim $c) => ['id' => $c->id, 'category' => (int) $c->expense_category_id, 'date' => $c->expense_date->toDateString(), 'amount' => (float) $c->amount])->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $this->currentEmployee();

        if (in_array($employee->status, self::INACTIVE, true)) {
            throw ValidationException::withMessages(['expense_category_id' => 'Your employee record is '.$employee->status.', so new claims can\'t be filed. Ask HR.']);
        }

        $claim = DB::transaction(function () use ($request, $employee) {
            // One claim at a time per person, so two quick submits can't both pass the double-entry check.
            DB::table('employees')->where('id', $employee->id)->lockForUpdate()->first();
            $values = $this->validated($request, $employee);

            $claim = (new ExpenseClaim)->forceFill($values + ['company_id' => $employee->company_id, 'employee_id' => $employee->id, 'status' => 'pending']);
            $claim->save();

            return $claim;
        });

        Notifier::notifyUsersWithPermission(
            'update_expense_claim',
            'New expense claim',
            "{$employee->name} submitted a {$claim->category?->name} claim for ".number_format((float) $claim->amount, 2).'.',
            route('admin.expense-approval.index'),
            'ti ti-receipt-2',
            $employee->branch_id,
            employeeId: $employee->id,
        );

        return back()->with('status', 'Your claim for '.number_format((float) $claim->amount, 2).' ('.$claim->category?->name.') was sent for approval.');
    }

    /** Correct a claim that nobody has approved yet. */
    public function update(Request $request, int $expenseClaim): RedirectResponse
    {
        $employee = $this->currentEmployee();
        $record = $this->owned($employee, $expenseClaim);

        DB::transaction(function () use ($request, $employee, $record) {
            DB::table('employees')->where('id', $employee->id)->lockForUpdate()->first();
            $row = $this->editable($record);
            $row->forceFill($this->validated($request, $employee, $row))->save();
        });

        return back()->with('status', 'Your claim was updated.');
    }

    /** Take back a claim that is still waiting for approval. */
    public function destroy(int $expenseClaim): RedirectResponse
    {
        $employee = $this->currentEmployee();
        $record = $this->owned($employee, $expenseClaim);

        DB::transaction(function () use ($record) {
            // The delete is conditional, so a withdrawal racing an approval can't remove an approved claim.
            if (! ExpenseClaim::withoutGlobalScopes()->whereKey($record->id)->where('status', 'pending')->delete()) {
                throw ValidationException::withMessages(['claim' => 'This claim is already '.(ExpenseClaim::withoutGlobalScopes()->whereKey($record->id)->value('status') ?? 'gone').', so it can\'t be withdrawn.']);
            }
            ApprovalStep::query()->where('approvable_type', $record->getMorphClass())->where('approvable_id', $record->id)->delete();
        });

        return back()->with('status', 'Your claim for '.number_format((float) $record->amount, 2).' was withdrawn.');
    }

    /** This employee's own claims, whatever their role may browse elsewhere. */
    protected function mine(Employee $employee): Builder
    {
        return ExpenseClaim::withoutGlobalScopes()->where('employee_id', $employee->id)->where('company_id', $employee->company_id);
    }

    /** 404 (not 403) for someone else's claim, so the id doesn't confirm it exists. */
    protected function owned(Employee $employee, int $id): ExpenseClaim
    {
        return $this->mine($employee)->whereKey($id)->firstOrFail();
    }

    /** Active categories of the employee's branch, plus company-wide ones. */
    protected function categories(Employee $employee): Builder
    {
        return ExpenseCategory::query()->where('company_id', $employee->company_id)->where('is_active', true)
            ->where(fn ($q) => $q->where('branch_id', $employee->branch_id)->orWhereNull('branch_id'));
    }

    /** The claim, locked, if it may still be changed: pending and not yet approved by anyone. */
    protected function editable(ExpenseClaim $record): ExpenseClaim
    {
        $row = ExpenseClaim::withoutGlobalScopes()->whereKey($record->id)->lockForUpdate()->first();

        if ($row->status !== 'pending') {
            throw ValidationException::withMessages(['claim' => 'This claim is already '.$row->status.', so it can\'t be changed.']);
        }
        // Someone has signed off on these figures; changing them afterwards would slip past that approval.
        if (ApprovalFlow::state($row)['done'] > 0) {
            throw ValidationException::withMessages(['claim' => 'Someone has already approved this claim. Withdraw it and file a new one if it needs changing.']);
        }

        return $row;
    }

    protected function validated(Request $request, Employee $employee, ?ExpenseClaim $existing = null): array
    {
        $data = $request->validate([
            'expense_category_id' => ['required'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.self::MAX_AMOUNT],
            'expense_date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.today()->subMonths(self::MONTHS_BACK)->toDateString()],
            // There is no receipt on file, so the approver needs to be told what it was for.
            'description' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'expense_category_id.required' => 'Choose what the money was spent on.',
            'amount.*' => 'Enter the amount spent, more than zero.',
            'expense_date.before_or_equal' => 'An expense can\'t be dated in the future.',
            'expense_date.after_or_equal' => 'A claim can be made for money spent in the last '.self::MONTHS_BACK.' months.',
            'description.required' => 'Say what the money was spent on.',
            'description.min' => 'Say a little more about what the money was spent on.',
        ]);

        $categoryId = is_scalar($data['expense_category_id']) && ctype_digit((string) $data['expense_category_id']) ? (int) $data['expense_category_id'] : 0;
        // An active category of the employee's branch, or the one the claim already has.
        $category = $this->categories($employee)->whereKey($categoryId)->first()
            ?? ($existing && (int) $existing->expense_category_id === $categoryId ? ExpenseCategory::query()->where('company_id', $employee->company_id)->whereKey($categoryId)->first() : null);

        if (! $category) {
            throw ValidationException::withMessages(['expense_category_id' => 'Choose a category from the list.']);
        }
        $amount = round((float) $data['amount'], 2);
        $date = Carbon::parse($data['expense_date'])->toDateString();

        if ($employee->joining_date && $date < $employee->joining_date->toDateString()) {
            throw ValidationException::withMessages(['expense_date' => 'That is before you joined ('.$employee->joining_date->format('M j, Y').').']);
        }
        // The same expense entered twice would be paid twice.
        if ($this->mine($employee)->where('expense_category_id', $category->id)->whereDate('expense_date', $date)->where('amount', $amount)
            ->where('status', '!=', 'rejected')->when($existing, fn ($q) => $q->whereKeyNot($existing->id))->exists()) {
            throw ValidationException::withMessages(['amount' => 'You already have a '.$category->name.' claim of '.number_format($amount, 2).' for '.Carbon::parse($date)->format('M j, Y').'.']);
        }

        return [
            'expense_category_id' => $category->id,
            'amount' => $amount,
            'expense_date' => $date,
            'description' => trim(preg_replace('/[ \t]+/', ' ', strip_tags($data['description']))),
        ];
    }
}
