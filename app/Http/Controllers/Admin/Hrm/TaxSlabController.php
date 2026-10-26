<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\TaxDeclaration;
use App\Models\TaxSlab;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TaxSlabController extends Controller
{
    /**
     * Indian income-tax slabs as of FY 2025-26, offered as a starting point.
     * [from, to (null = no upper limit), rate %]. Rates change with each
     * budget, so the page tells the user to check them before relying on them.
     */
    protected const STANDARD = [
        'new' => [[0, 400000, 0], [400000, 800000, 5], [800000, 1200000, 10], [1200000, 1600000, 15], [1600000, 2000000, 20], [2000000, 2400000, 25], [2400000, null, 30]],
        'old' => [[0, 250000, 0], [250000, 500000, 5], [500000, 1000000, 20], [1000000, null, 30]],
    ];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', TaxSlab::class);

        $asked = is_scalar($request->query('financial_year')) ? (string) $request->query('financial_year') : '';
        $financialYear = $this->validYear($asked) ? $asked : TaxDeclaration::currentFinancialYear();
        $slabs = $this->scoped()->where('financial_year', $financialYear)->orderBy('min_income')->get()->groupBy('regime');
        $previousYear = $this->shiftYear($financialYear, -1);

        return view('admin.hrm.settings.tax-slabs.index', [
            'financialYear' => $financialYear,
            'years' => collect($this->offeredYears())->merge($this->scoped()->distinct()->pluck('financial_year'))
                ->filter(fn ($y) => $this->validYear($y, false))->unique()->sort()->values(),
            'regimes' => collect(TaxSlab::REGIMES)->map(fn (string $label, string $regime) => [
                'label' => $label,
                'slabs' => $slabs->get($regime, collect())->values(),
                'issues' => $this->issues($slabs->get($regime, collect())),
            ]),
            'previousYear' => $previousYear,
            'previousCount' => $this->scoped()->where('financial_year', $previousYear)->count(),
            'declarations' => TaxDeclaration::query()->where('company_id', Company::current()->id)->where('financial_year', $financialYear)
                ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())))->count(),
        ]);
    }

    /** Slabs are added and changed on the list page now; old links still land somewhere useful. */
    public function create(): RedirectResponse
    {
        $this->authorize('create', TaxSlab::class);

        return redirect()->route('admin.tax-slabs.index');
    }

    public function edit(TaxSlab $taxSlab): RedirectResponse
    {
        $this->ensureOwn($taxSlab);
        $this->authorize('update', $taxSlab);

        return redirect()->route('admin.tax-slabs.index', ['financial_year' => $taxSlab->financial_year]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', TaxSlab::class);

        $data = $this->validated($request);
        $this->guardOverlap($data);

        TaxSlab::create($data + ['company_id' => Company::current()->id, 'branch_id' => BranchContext::currentBranchId()]);

        return redirect()->route('admin.tax-slabs.index', ['financial_year' => $data['financial_year']])->with('status', 'Slab added to the '.strtolower(TaxSlab::REGIMES[$data['regime']]).'.');
    }

    public function update(Request $request, TaxSlab $taxSlab): RedirectResponse
    {
        $this->ensureOwn($taxSlab);
        $this->authorize('update', $taxSlab);

        // A slab stays in its own year and regime; only its range and rate change.
        $data = $this->validated($request, $taxSlab);
        $this->guardOverlap($data, $taxSlab);

        $taxSlab->update(['min_income' => $data['min_income'], 'max_income' => $data['max_income'], 'tax_rate_percent' => $data['tax_rate_percent']]);

        return redirect()->route('admin.tax-slabs.index', ['financial_year' => $taxSlab->financial_year])->with('status', 'Slab updated.');
    }

    public function destroy(TaxSlab $taxSlab): RedirectResponse
    {
        $this->ensureOwn($taxSlab);
        $this->authorize('delete', $taxSlab);

        $financialYear = $taxSlab->financial_year;
        $taxSlab->delete();

        return redirect()->route('admin.tax-slabs.index', ['financial_year' => $financialYear])->with('status', 'Slab removed.');
    }

    /**
     * Fill the regimes that have no slabs yet for a year, either from last
     * year's slabs or from the standard set. Regimes that already have slabs
     * are left alone, so this can never overwrite someone's work.
     */
    public function fill(Request $request): RedirectResponse
    {
        $this->authorize('create', TaxSlab::class);

        $data = $request->validate([
            'financial_year' => ['required', 'string'],
            'source' => ['required', Rule::in(['previous', 'standard'])],
            'regime' => ['nullable', Rule::in(array_keys(TaxSlab::REGIMES))],
        ]);

        if (! $this->validYear($data['financial_year'])) {
            throw ValidationException::withMessages(['financial_year' => 'Choose one of the financial years offered.']);
        }

        $year = $data['financial_year'];
        $existing = $this->scoped()->where('financial_year', $year)->pluck('regime')->unique();
        $empty = collect(array_keys(TaxSlab::REGIMES))
            ->when($data['regime'] ?? null, fn (Collection $c, string $only) => $c->filter(fn ($r) => $r === $only))
            ->reject(fn (string $regime) => $existing->contains($regime));

        if ($empty->isEmpty()) {
            return back()->withErrors(['fill' => 'Slabs already exist there. Remove them first if you want to start again.']);
        }

        $previous = $data['source'] === 'previous'
            ? $this->scoped()->where('financial_year', $this->shiftYear($year, -1))->orderBy('min_income')->get()->groupBy('regime')
            : collect();

        $rows = $empty->flatMap(fn (string $regime) => $data['source'] === 'previous'
            ? $previous->get($regime, collect())->map(fn (TaxSlab $s) => [$regime, (float) $s->min_income, $s->max_income !== null ? (float) $s->max_income : null, (float) $s->tax_rate_percent])
            : collect(self::STANDARD[$regime])->map(fn (array $band) => [$regime, ...$band]));

        if ($rows->isEmpty()) {
            return back()->withErrors(['fill' => 'There are no slabs for '.$this->shiftYear($year, -1).' to copy.']);
        }

        DB::transaction(fn () => $rows->each(fn (array $row) => TaxSlab::create([
            'company_id' => Company::current()->id,
            'branch_id' => BranchContext::currentBranchId(),
            'financial_year' => $year,
            'regime' => $row[0],
            'min_income' => $row[1],
            'max_income' => $row[2],
            'tax_rate_percent' => $row[3],
        ])));

        return redirect()->route('admin.tax-slabs.index', ['financial_year' => $year])
            ->with('status', $rows->count().' slabs added'.($data['source'] === 'previous' ? ' from '.$this->shiftYear($year, -1) : ' from the standard set').'. Check the rates against the current tax rules.');
    }

    protected function validated(Request $request, ?TaxSlab $slab = null): array
    {
        $data = $request->validate([
            'regime' => [$slab ? 'nullable' : 'required', Rule::in(array_keys(TaxSlab::REGIMES))],
            'financial_year' => [$slab ? 'nullable' : 'required', 'string'],
            'min_income' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'max_income' => ['nullable', 'numeric', 'gt:min_income', 'max:999999999999.99'],
            'tax_rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [
            'max_income.gt' => 'The upper limit must be more than the lower limit. Leave it empty for "and above".',
        ]);

        $data['regime'] = $slab?->regime ?? $data['regime'];
        $data['financial_year'] = $slab?->financial_year ?? $data['financial_year'];

        // Free text here never matched what payroll and declarations look up ("2026-2027").
        if (! $slab && ! $this->validYear($data['financial_year'])) {
            throw ValidationException::withMessages(['financial_year' => 'Choose one of the financial years offered ('.implode(', ', $this->offeredYears()).').']);
        }

        return [
            'regime' => $data['regime'],
            'financial_year' => $data['financial_year'],
            'min_income' => round((float) $data['min_income'], 2),
            'max_income' => filled($data['max_income'] ?? null) ? round((float) $data['max_income'], 2) : null,
            'tax_rate_percent' => round((float) $data['tax_rate_percent'], 2),
        ];
    }

    /** Two slabs of one regime and year must not tax the same income twice. */
    protected function guardOverlap(array $data, ?TaxSlab $ignore = null): void
    {
        $clash = $this->scoped()
            ->where('financial_year', $data['financial_year'])->where('regime', $data['regime'])
            ->when($ignore, fn ($q) => $q->whereKeyNot($ignore->id))
            ->get()
            ->first(fn (TaxSlab $other) => $data['min_income'] < ($other->max_income !== null ? (float) $other->max_income : INF)
                && (float) $other->min_income < ($data['max_income'] ?? INF));

        if ($clash) {
            $range = number_format((float) $clash->min_income).($clash->max_income !== null ? ' to '.number_format((float) $clash->max_income) : ' and above');

            throw ValidationException::withMessages(['min_income' => 'This overlaps the existing slab for '.$range.' ('.rtrim(rtrim((string) $clash->tax_rate_percent, '0'), '.').'%). Change that slab first, or pick a range that does not overlap.']);
        }
    }

    /** What is wrong with one regime's slabs, in plain words. Empty when they cover all income cleanly. */
    protected function issues(Collection $slabs): array
    {
        if ($slabs->isEmpty()) {
            return [];
        }

        $issues = [];
        $sorted = $slabs->sortBy('min_income')->values();

        if ((float) $sorted->first()->min_income > 0) {
            $issues[] = 'Income below '.number_format((float) $sorted->first()->min_income).' is not covered by any slab.';
        }

        foreach ($sorted as $i => $slab) {
            $next = $sorted->get($i + 1);

            if ($next && $slab->max_income !== null && (float) $next->min_income > (float) $slab->max_income) {
                $issues[] = 'Income between '.number_format((float) $slab->max_income).' and '.number_format((float) $next->min_income).' is not covered by any slab.';
            }
        }

        if ($sorted->last()->max_income !== null) {
            $issues[] = 'Income above '.number_format((float) $sorted->last()->max_income).' is not taxed. The top slab should have no upper limit.';
        }

        return $issues;
    }

    /** 404 (not 403) for another company's or branch's slab, so the URL doesn't confirm it exists. */
    protected function ensureOwn(TaxSlab $taxSlab): void
    {
        abort_unless($this->scoped()->whereKey($taxSlab->id)->exists(), 404);
    }

    /** Slabs this login may see: its own company and, when tied to a branch, that branch's plus company-wide ones. */
    protected function scoped(): Builder
    {
        return TaxSlab::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where(fn ($w) => $w->where('branch_id', BranchContext::currentBranchId())->orWhereNull('branch_id')));
    }

    /** Last, current and next financial year, e.g. ["2025-2026", "2026-2027", "2027-2028"]. */
    protected function offeredYears(): array
    {
        return collect([-1, 0, 1])->map(fn (int $i) => $this->shiftYear(TaxDeclaration::currentFinancialYear(), $i))->all();
    }

    protected function shiftYear(string $financialYear, int $by): string
    {
        $start = (int) substr($financialYear, 0, 4) + $by;

        return $start.'-'.($start + 1);
    }

    /** A real April-to-March year ("2026-2027"); with $offeredOnly, also one of the three on offer. */
    protected function validYear(mixed $value, bool $offeredOnly = true): bool
    {
        if (! is_string($value) || ! preg_match('/^(\d{4})-(\d{4})$/', $value, $m) || (int) $m[2] !== (int) $m[1] + 1) {
            return false;
        }

        return ! $offeredOnly || in_array($value, $this->offeredYears(), true);
    }
}
