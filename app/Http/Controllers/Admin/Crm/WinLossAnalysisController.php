<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Competitor;
use App\Models\Crm\Opportunity;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WinLossAnalysisController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $year = (int) ($request->query('year') ?: now()->year);

        $closed = Opportunity::where('company_id', $companyId)
            ->whereIn('status', ['won', 'lost'])
            ->whereYear('closed_at', $year)
            ->with('owner')
            ->get();
        $won = $closed->where('status', 'won');
        $lost = $closed->where('status', 'lost');

        $byReason = $lost->groupBy(fn ($o) => $o->lost_reason ?: 'Not specified')->map->count()->sortDesc();

        $byOwner = $closed->groupBy(fn ($o) => $o->owner?->name ?? 'Unassigned')->map(fn ($group) => [
            'won' => $group->where('status', 'won')->count(),
            'lost' => $group->where('status', 'lost')->count(),
        ]);

        $competitors = Competitor::where('company_id', $companyId)
            ->withCount(['opportunities as won_against' => fn ($q) => $q->where('crm_opportunity_competitor.outcome', 'won')])
            ->withCount(['opportunities as lost_to' => fn ($q) => $q->where('crm_opportunity_competitor.outcome', 'lost')])
            ->get()
            ->filter(fn ($c) => $c->won_against > 0 || $c->lost_to > 0);

        // Sales cycle = days from when the deal was created to when it closed -
        // a core pipeline health metric alongside the raw win rate.
        $cycleDays = $closed->filter(fn ($o) => $o->closed_at)->map(fn ($o) => $o->created_at->diffInDays($o->closed_at));

        $years = Opportunity::where('company_id', $companyId)->whereIn('status', ['won', 'lost'])
            ->selectRaw('DISTINCT YEAR(closed_at) as year')->whereNotNull('closed_at')->orderByDesc('year')->pluck('year');

        return view('admin.crm.win-loss.index', [
            'year' => $year,
            'years' => $years->isEmpty() ? collect([$year]) : $years,
            'totalClosed' => $closed->count(),
            'wonCount' => $won->count(),
            'lostCount' => $lost->count(),
            'winRate' => $closed->count() ? round($won->count() / $closed->count() * 100) : 0,
            'wonValue' => $won->sum('amount'),
            'lostValue' => $lost->sum('amount'),
            'avgCycleDays' => $cycleDays->isNotEmpty() ? round($cycleDays->avg()) : 0,
            'byReason' => $byReason,
            'byOwner' => $byOwner,
            'competitors' => $competitors,
        ]);
    }
}
