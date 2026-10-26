<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\ExpenseCategory;
use App\Models\ExpenseClaim;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExpenseCategoryController extends Controller
{
    protected const TABS = ['active', 'inactive'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ExpenseCategory::class);

        // Anything that isn't a plain value (e.g. ?search[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $state = [
            'tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : '',
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $all = $this->scoped()
            ->withCount('claims')
            ->withCount(['claims as pending_count' => fn ($q) => $q->where('status', 'pending')])
            ->withSum(['claims as accepted_total' => fn ($q) => $q->whereIn('status', ['approved', 'reimbursed'])], 'amount')
            ->withMax('claims as last_used', 'expense_date')
            ->orderByDesc('is_active')->orderBy('name')->orderBy('id')
            ->get();

        $needle = mb_strtolower($state['search']);
        $filtered = $all->when($needle !== '', fn ($c) => $c->filter(fn (ExpenseCategory $x) => str_contains(mb_strtolower($x->name.' '.$x->description), $needle)))->values();
        $active = $all->where('is_active', true);
        $top = $active->sortByDesc(fn (ExpenseCategory $c) => (float) $c->accepted_total)->first();
        $restricted = BranchContext::isRestricted();

        return view('admin.hrm.expenses.categories.index', [
            'categories' => $state['tab'] ? $filtered->where('is_active', $state['tab'] === 'active')->values() : $filtered,
            'state' => $state,
            'counts' => ['all' => $filtered->count(), 'active' => $filtered->where('is_active', true)->count(), 'inactive' => $filtered->where('is_active', false)->count()],
            'spendTotal' => (float) $all->sum(fn (ExpenseCategory $c) => (float) $c->accepted_total),
            'kpis' => [
                'active' => $active->count(),
                'inactive' => $all->count() - $active->count(),
                'unused' => $active->where('claims_count', 0)->count(),
                'top' => $top && (float) $top->accepted_total > 0 ? $top : null,
                'claims' => (int) $all->sum('claims_count'),
            ],
            // Without a branch selected, the same name can exist once per branch: say which is which.
            'branches' => $restricted ? collect() : Branch::query()->where('company_id', Company::current()->id)->pluck('name', 'id'),
            'existing' => $all->pluck('name')->map(fn ($n) => mb_strtolower(trim($n)))->unique()->values(),
            'canCreate' => $request->user()->can('create', ExpenseCategory::class),
            'editId' => ctype_digit($text('edit')) ? (int) $text('edit') : null,
        ]);
    }

    /** The category is added on the list page; the old "add" link still lands somewhere useful. */
    public function create(): RedirectResponse
    {
        $this->authorize('create', ExpenseCategory::class);

        return redirect()->route('admin.expense-categories.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ExpenseCategory::class);

        $data = $this->validated($request);

        ExpenseCategory::create($data + ['company_id' => Company::current()->id, 'branch_id' => BranchContext::currentBranchId()]);

        return redirect()->route('admin.expense-categories.index')->with('status', '"'.$data['name'].'" added. People can now claim expenses under it.');
    }

    /** Editing happens in a dialog on the list page; the old edit link opens it. */
    public function edit(ExpenseCategory $expenseCategory): RedirectResponse
    {
        $this->ensureOwn($expenseCategory);
        $this->authorize('update', $expenseCategory);

        return redirect()->route('admin.expense-categories.index', ['edit' => $expenseCategory->id]);
    }

    public function update(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        $this->ensureOwn($expenseCategory);
        $this->authorize('update', $expenseCategory);

        $expenseCategory->update($this->validated($request, $expenseCategory));

        return redirect()->route('admin.expense-categories.index')->with('status', 'Expense category updated.');
    }

    /** Switch a category off (or back on) without touching the claims already made under it. */
    public function toggle(ExpenseCategory $expenseCategory): RedirectResponse
    {
        $this->ensureOwn($expenseCategory);
        $this->authorize('update', $expenseCategory);

        $expenseCategory->update(['is_active' => ! $expenseCategory->is_active]);

        return back()->with('status', '"'.$expenseCategory->name.'" is now '.($expenseCategory->is_active ? 'active' : 'switched off. Claims already made under it are kept, but nobody can pick it for a new one').'.');
    }

    public function destroy(ExpenseCategory $expenseCategory): RedirectResponse
    {
        $this->ensureOwn($expenseCategory);
        $this->authorize('delete', $expenseCategory);

        // Deleting a category would also delete every claim made under it, paid ones included.
        $claims = ExpenseClaim::withoutGlobalScopes()->where('expense_category_id', $expenseCategory->id)->count();

        if ($claims) {
            return back()->withErrors(['category' => '"'.$expenseCategory->name.'" has '.$claims.' expense '.($claims === 1 ? 'claim' : 'claims').', so it can\'t be deleted. Switch it off instead; the claims are kept.']);
        }

        $expenseCategory->delete();

        return redirect()->route('admin.expense-categories.index')->with('status', 'Expense category deleted.');
    }

    protected function validated(Request $request, ?ExpenseCategory $expenseCategory = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $name = trim(preg_replace('/\s+/', ' ', $data['name']));
        // A category belongs to a branch; the name only has to be unique within it.
        $branchId = $expenseCategory ? $expenseCategory->branch_id : BranchContext::currentBranchId();

        if (ExpenseCategory::query()->where('company_id', Company::current()->id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId), fn ($q) => $q->whereNull('branch_id'))
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->when($expenseCategory, fn ($q) => $q->whereKeyNot($expenseCategory->id))->exists()) {
            throw ValidationException::withMessages(['name' => 'There is already a category called "'.$name.'".']);
        }

        return [
            'name' => $name,
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            // The add form has no switch (new ones start active); the edit dialog sends it.
            'is_active' => $expenseCategory ? $request->boolean('is_active') : ($request->has('is_active') ? $request->boolean('is_active') : true),
        ];
    }

    /** 404 (not 403) for another company's or branch's category, so the URL doesn't confirm it exists. */
    protected function ensureOwn(ExpenseCategory $expenseCategory): void
    {
        abort_unless($this->scoped()->whereKey($expenseCategory->id)->exists(), 404);
    }

    /** Categories this login may manage: its own company and, when tied to a branch, that branch's. */
    protected function scoped(): Builder
    {
        return ExpenseCategory::query()
            ->where('expense_categories.company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }
}
