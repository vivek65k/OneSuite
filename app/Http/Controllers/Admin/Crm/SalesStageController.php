<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\SalesStage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesStageController extends Controller
{
    public function index(): View
    {
        $stages = SalesStage::where('company_id', Company::current()->id)
            ->withCount('opportunities')
            ->orderBy('sort_order')
            ->get();

        return view('admin.crm.sales-stages.index', [
            'stages' => $stages,
            'totalOpportunities' => $stages->sum('opportunities_count'),
        ]);
    }

    public function create(): View
    {
        return view('admin.crm.sales-stages.form', ['stage' => new SalesStage]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $maxOrder = (int) SalesStage::where('company_id', $companyId)->max('sort_order');

        SalesStage::create($this->validated($request) + ['company_id' => $companyId, 'sort_order' => $maxOrder + 1]);

        return redirect()->route('admin.crm.sales-stages.index')->with('status', 'Stage created.');
    }

    public function edit(SalesStage $salesStage): View
    {
        abort_unless($salesStage->company_id === Company::current()->id, 404);

        return view('admin.crm.sales-stages.form', ['stage' => $salesStage]);
    }

    public function update(Request $request, SalesStage $salesStage): RedirectResponse
    {
        abort_unless($salesStage->company_id === Company::current()->id, 404);

        $salesStage->update($this->validated($request));

        return redirect()->route('admin.crm.sales-stages.index')->with('status', 'Stage updated.');
    }

    public function destroy(SalesStage $salesStage): RedirectResponse
    {
        abort_unless($salesStage->company_id === Company::current()->id, 404);
        abort_if($salesStage->opportunities()->exists(), 422, 'Move or close the opportunities in this stage before deleting it.');
        $salesStage->delete();

        return back()->with('status', 'Stage removed.');
    }

    public function moveUp(SalesStage $salesStage): RedirectResponse
    {
        return $this->swap($salesStage, '<');
    }

    public function moveDown(SalesStage $salesStage): RedirectResponse
    {
        return $this->swap($salesStage, '>');
    }

    protected function swap(SalesStage $stage, string $operator): RedirectResponse
    {
        abort_unless($stage->company_id === Company::current()->id, 404);

        $neighbor = SalesStage::where('company_id', $stage->company_id)
            ->where('sort_order', $operator, $stage->sort_order)
            ->orderBy('sort_order', $operator === '<' ? 'desc' : 'asc')
            ->first();

        if ($neighbor) {
            [$a, $b] = [$stage->sort_order, $neighbor->sort_order];
            $stage->update(['sort_order' => $b]);
            $neighbor->update(['sort_order' => $a]);
        }

        return back();
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'win_probability' => ['required', 'integer', 'min:0', 'max:100'],
            'is_won' => ['nullable', 'boolean'],
            'is_lost' => ['nullable', 'boolean'],
        ]) + ['is_won' => $request->boolean('is_won'), 'is_lost' => $request->boolean('is_lost')];
    }
}
