<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Campaign;
use App\Models\Crm\LandingPage;
use App\Models\Crm\WebForm;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketingAnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;

        $campaigns = Campaign::where('company_id', $companyId)->withCount('members')->get();
        $webForms = WebForm::where('company_id', $companyId)->get();
        $landingPages = LandingPage::where('company_id', $companyId)->get();

        $byType = collect(Campaign::TYPES)->map(function ($label, $type) use ($campaigns) {
            $set = $campaigns->where('type', $type);

            return [
                'label' => $label,
                'count' => $set->count(),
                'sent' => $set->sum('sent_count'),
                'opened' => $set->sum('opened_count'),
                'clicked' => $set->sum('clicked_count'),
                'responded' => $set->sum('responded_count'),
                'budget' => (float) $set->sum('budget'),
                'cost' => (float) $set->sum('actual_cost'),
            ];
        })->filter(fn ($row) => $row['count'] > 0)->values();

        $topCampaigns = $campaigns->sortByDesc(fn ($c) => $c->members_count)->take(5)->values();

        return view('admin.crm.marketing-analytics.index', [
            'byType' => $byType,
            'topCampaigns' => $topCampaigns,
            'totalCampaigns' => $campaigns->count(),
            'activeCampaigns' => $campaigns->where('status', 'active')->count(),
            'totalBudget' => (float) $campaigns->sum('budget'),
            'totalSpend' => (float) $campaigns->sum('actual_cost'),
            'totalSent' => $campaigns->sum('sent_count'),
            'totalOpened' => $campaigns->sum('opened_count'),
            'totalClicked' => $campaigns->sum('clicked_count'),
            'totalResponded' => $campaigns->sum('responded_count'),
            'totalForms' => $webForms->count(),
            'totalFormSubmissions' => $webForms->sum('submissions_count'),
            'totalLandingPages' => $landingPages->count(),
            'totalLandingViews' => $landingPages->sum('views_count'),
            'totalLandingConversions' => $landingPages->sum('conversions_count'),
        ]);
    }
}
