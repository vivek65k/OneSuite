<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Activity;
use App\Models\Crm\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AiLeadScoringController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;
        $leads = Lead::where('company_id', $companyId)->whereNotIn('status', ['converted'])
            ->with('source')->orderByDesc('created_at')->get();

        $rows = $leads->map(fn ($lead) => $this->scoreLead($lead))->sortByDesc('score')->values();

        return view('admin.crm.ai-lead-scoring.index', [
            'rows' => $rows,
            'hotCount' => $rows->where('score', '>=', 70)->count(),
            'avgScore' => $rows->count() > 0 ? round($rows->avg('score')) : 0,
        ]);
    }

    public function recalculateAll(): RedirectResponse
    {
        $companyId = Company::current()->id;
        $leads = Lead::where('company_id', $companyId)->whereNotIn('status', ['converted'])->get();

        foreach ($leads as $lead) {
            $row = $this->scoreLead($lead);
            $lead->update(['score' => $row['score']]);
        }

        return back()->with('status', "Recalculated scores for {$leads->count()} lead(s).");
    }

    protected function scoreLead(Lead $lead): array
    {
        $factors = [];
        $score = 0;

        if ($lead->email) {
            $score += 15;
            $factors[] = ['label' => 'Has email', 'points' => 15];
        }
        if ($lead->phone) {
            $score += 10;
            $factors[] = ['label' => 'Has phone', 'points' => 10];
        }
        if ($lead->lead_source_id) {
            $score += 10;
            $factors[] = ['label' => 'Known source', 'points' => 10];
        }

        $activityCount = Activity::where('company_id', $lead->company_id)->where('related_type', Lead::class)->where('related_id', $lead->id)->count();
        $activityPoints = min($activityCount * 5, 30);
        if ($activityPoints > 0) {
            $score += $activityPoints;
            $factors[] = ['label' => "{$activityCount} activity(ies) logged", 'points' => $activityPoints];
        }

        $daysOld = $lead->created_at->diffInDays(now());
        if ($daysOld <= 7) {
            $score += 15;
            $factors[] = ['label' => 'Created within 7 days', 'points' => 15];
        } elseif ($daysOld <= 30) {
            $score += 5;
            $factors[] = ['label' => 'Created within 30 days', 'points' => 5];
        }

        if ($lead->status === 'qualified') {
            $score += 25;
            $factors[] = ['label' => 'Status: Qualified', 'points' => 25];
        } elseif ($lead->status === 'contacted') {
            $score += 10;
            $factors[] = ['label' => 'Status: Contacted', 'points' => 10];
        }

        return ['lead' => $lead, 'score' => min($score, 100), 'factors' => $factors];
    }
}
