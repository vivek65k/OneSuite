<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\CaseModel;
use App\Models\Crm\CustomerFeedback;
use Illuminate\View\View;

class CsatController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;
        $feedback = CustomerFeedback::where('company_id', $companyId)->get();
        $casesCsat = CaseModel::where('company_id', $companyId)->whereNotNull('csat_rating')->get();

        $distribution = collect(range(1, 5))->mapWithKeys(fn ($r) => [$r => $feedback->where('rating', $r)->count()]);

        $monthly = $feedback->groupBy(fn ($f) => $f->created_at->format('Y-m'))
            ->map(fn ($group) => round($group->avg('rating'), 2))
            ->sortKeys()
            ->take(-6);

        return view('admin.crm.csat.index', [
            'avgRating' => $feedback->count() > 0 ? round($feedback->avg('rating'), 2) : 0,
            'totalResponses' => $feedback->count(),
            'promoters' => $feedback->where('rating', '>=', 4)->count(),
            'detractors' => $feedback->where('rating', '<=', 2)->count(),
            'distribution' => $distribution,
            'monthly' => $monthly,
            'avgCaseCsat' => $casesCsat->count() > 0 ? round($casesCsat->avg('csat_rating'), 2) : 0,
        ]);
    }
}
