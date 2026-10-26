<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Activity;
use App\Models\Crm\Opportunity;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AiWinProbabilityController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;
        $open = Opportunity::where('company_id', $companyId)->where('status', 'open')->with(['stage', 'account', 'competitors'])->get();
        $avgAmount = $open->count() > 0 ? $open->avg('amount') : 0;

        $rows = $open->map(fn ($o) => $this->scoreOpportunity($o, $avgAmount))->sortByDesc('probability')->values();

        return view('admin.crm.ai-win-probability.index', [
            'rows' => $rows,
            'avgProbability' => $rows->count() > 0 ? round($rows->avg('probability')) : 0,
            'likelyToWin' => $rows->where('probability', '>=', 70)->count(),
            'atRisk' => $rows->where('probability', '<', 30)->count(),
        ]);
    }

    public function recalculateAll(): RedirectResponse
    {
        $companyId = Company::current()->id;
        $open = Opportunity::where('company_id', $companyId)->where('status', 'open')->with('competitors')->get();
        $avgAmount = $open->count() > 0 ? $open->avg('amount') : 0;

        foreach ($open as $opportunity) {
            $row = $this->scoreOpportunity($opportunity, $avgAmount);
            $opportunity->update(['probability' => $row['probability']]);
        }

        return back()->with('status', "Recalculated win probability for {$open->count()} opportunity(ies).");
    }

    protected function scoreOpportunity(Opportunity $opportunity, float $avgAmount): array
    {
        $factors = [];
        $probability = $opportunity->stage?->win_probability ?? 20;
        $factors[] = ['label' => 'Stage baseline ('.($opportunity->stage?->name ?? 'none').')', 'points' => $probability];

        if ($opportunity->competitors->isNotEmpty()) {
            $probability -= 10;
            $factors[] = ['label' => 'Competitor involved', 'points' => -10];
        }

        if ($avgAmount > 0 && $opportunity->amount < $avgAmount) {
            $probability += 10;
            $factors[] = ['label' => 'Below average deal size', 'points' => 10];
        } elseif ($avgAmount > 0) {
            $probability -= 5;
            $factors[] = ['label' => 'Above average deal size', 'points' => -5];
        }

        $recentActivity = Activity::where('company_id', $opportunity->company_id)->where('related_type', Opportunity::class)
            ->where('related_id', $opportunity->id)->where('created_at', '>=', now()->subDays(14))->exists();
        if (! $recentActivity) {
            $probability -= 15;
            $factors[] = ['label' => 'No activity in 14 days', 'points' => -15];
        }

        if ($opportunity->expected_close_date && $opportunity->expected_close_date->diffInDays(now()) <= 30 && $opportunity->expected_close_date->isFuture()) {
            $probability += 10;
            $factors[] = ['label' => 'Closing within 30 days', 'points' => 10];
        }

        return ['opportunity' => $opportunity, 'probability' => max(5, min(95, $probability)), 'factors' => $factors];
    }
}
