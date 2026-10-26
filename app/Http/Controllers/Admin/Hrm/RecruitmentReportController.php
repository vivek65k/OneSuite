<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Interview;
use App\Models\JobPosition;
use App\Models\OfferLetter;
use App\Support\BranchContext;
use Illuminate\View\View;

class RecruitmentReportController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Candidate::class);

        $companyId = Company::current()->id;

        $candidatesByStage = Candidate::query()
            ->where('company_id', $companyId)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->selectRaw('stage, count(*) as total')
            ->groupBy('stage')
            ->pluck('total', 'stage');

        $offersByStatus = OfferLetter::query()
            ->whereHas('candidate', fn ($q) => $q->where('company_id', $companyId)
                ->when(BranchContext::isRestricted(), fn ($q2) => $q2->where('branch_id', BranchContext::currentBranchId())))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.hrm.recruitment.recruitment-reports.index', [
            'openPositions' => JobPosition::where('company_id', $companyId)
                ->where('status', 'open')
                ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
                ->count(),
            'totalCandidates' => Candidate::where('company_id', $companyId)
                ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
                ->count(),
            'candidatesByStage' => $candidatesByStage,
            'upcomingInterviews' => Interview::whereHas('candidate', fn ($q) => $q->where('company_id', $companyId)
                ->when(BranchContext::isRestricted(), fn ($q2) => $q2->where('branch_id', BranchContext::currentBranchId())))
                ->where('status', 'scheduled')
                ->where('scheduled_at', '>=', now())
                ->count(),
            'offersByStatus' => $offersByStatus,
        ]);
    }
}
