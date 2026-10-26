<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Campaign;
use Illuminate\View\View;

class CampaignReportController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        $campaigns = Campaign::where('company_id', $companyId)->withCount('members')->get()->map(function ($campaign) {
            return [
                'campaign' => $campaign,
                'cost' => (float) $campaign->actual_cost,
                'members' => $campaign->members_count,
                'converted' => $campaign->converted_count,
                'cost_per_conversion' => $campaign->converted_count > 0 ? round($campaign->actual_cost / $campaign->converted_count, 2) : 0,
            ];
        })->sortByDesc(fn ($row) => $row['converted'])->values();

        return view('admin.crm.campaign-reports.index', [
            'campaigns' => $campaigns,
            'totalCost' => $campaigns->sum('cost'),
            'totalConverted' => $campaigns->sum('converted'),
            'totalMembers' => $campaigns->sum('members'),
        ]);
    }
}
