<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Opportunity;
use App\Models\Crm\SalesStage;
use Illuminate\View\View;

class PipelineReportController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;
        $open = Opportunity::where('company_id', $companyId)->where('status', 'open')->with(['stage', 'owner'])->get();

        $byStage = SalesStage::where('company_id', $companyId)->orderBy('sort_order')->get()->map(function ($stage) use ($open) {
            $set = $open->where('sales_stage_id', $stage->id);

            return [
                'name' => $stage->name,
                'count' => $set->count(),
                'amount' => (float) $set->sum('amount'),
                'weighted' => (float) $set->sum('weighted_amount'),
            ];
        });

        $byOwner = $open->groupBy(fn ($o) => $o->owner?->name ?? 'Unassigned')
            ->map(fn ($group) => ['count' => $group->count(), 'amount' => (float) $group->sum('amount')])
            ->sortByDesc('amount');

        $now = now();
        $aging = [
            '0-30 days' => $open->filter(fn ($o) => $o->created_at->diffInDays($now) <= 30)->count(),
            '31-60 days' => $open->filter(fn ($o) => $o->created_at->diffInDays($now) > 30 && $o->created_at->diffInDays($now) <= 60)->count(),
            '61-90 days' => $open->filter(fn ($o) => $o->created_at->diffInDays($now) > 60 && $o->created_at->diffInDays($now) <= 90)->count(),
            '90+ days' => $open->filter(fn ($o) => $o->created_at->diffInDays($now) > 90)->count(),
        ];

        return view('admin.crm.pipeline-reports.index', [
            'byStage' => $byStage,
            'byOwner' => $byOwner,
            'aging' => $aging,
            'totalOpen' => $open->count(),
            'totalValue' => (float) $open->sum('amount'),
            'totalWeighted' => (float) $open->sum('weighted_amount'),
        ]);
    }
}
