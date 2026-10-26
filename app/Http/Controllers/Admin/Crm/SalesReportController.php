<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Opportunity;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesReportController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $months = (int) $request->query('months', 6);

        $since = now()->subMonths($months)->startOfMonth();
        $won = Opportunity::where('company_id', $companyId)->where('status', 'won')
            ->where('closed_at', '>=', $since)->with('owner')->get();
        $lost = Opportunity::where('company_id', $companyId)->where('status', 'lost')
            ->where('closed_at', '>=', $since)->get();

        $byMonth = $won->groupBy(fn ($o) => $o->closed_at->format('Y-m'))
            ->map(fn ($group) => ['count' => $group->count(), 'amount' => (float) $group->sum('amount')])
            ->sortKeys();

        $byOwner = $won->groupBy(fn ($o) => $o->owner?->name ?? 'Unassigned')
            ->map(fn ($group) => ['count' => $group->count(), 'amount' => (float) $group->sum('amount')])
            ->sortByDesc('amount');

        return view('admin.crm.sales-reports.index', [
            'months' => $months,
            'byMonth' => $byMonth,
            'byOwner' => $byOwner,
            'totalWon' => $won->count(),
            'totalWonAmount' => (float) $won->sum('amount'),
            'totalLost' => $lost->count(),
            'totalLostAmount' => (float) $lost->sum('amount'),
            'winRate' => ($won->count() + $lost->count()) > 0 ? round($won->count() / ($won->count() + $lost->count()) * 100, 1) : 0,
            'avgDealSize' => $won->count() > 0 ? round($won->avg('amount'), 2) : 0,
        ]);
    }
}
