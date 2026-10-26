<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Campaign;
use App\Models\Crm\Lead;
use App\Models\Crm\LeadSource;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadAttributionController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $range = (string) $request->query('range', '90');
        $since = $range === 'all' ? null : now()->subDays((int) $range);

        $leadQuery = Lead::where('company_id', $companyId);
        if ($since) {
            $leadQuery->where('created_at', '>=', $since);
        }
        $leads = $leadQuery->get();

        $bySource = LeadSource::where('company_id', $companyId)->get()->map(function ($source) use ($leads) {
            $sourceLeads = $leads->where('lead_source_id', $source->id);

            return [
                'name' => $source->name,
                'total' => $sourceLeads->count(),
                'qualified' => $sourceLeads->where('status', 'qualified')->count(),
                'converted' => $sourceLeads->where('status', 'converted')->count(),
            ];
        })->filter(fn ($row) => $row['total'] > 0)->sortByDesc('total')->values();

        $unattributed = $leads->whereNull('lead_source_id')->count();

        $byCampaign = Campaign::where('company_id', $companyId)->get()->map(function ($campaign) use ($leads) {
            $campaignLeads = $leads->where('campaign_id', $campaign->id);

            return [
                'name' => $campaign->name,
                'type' => Campaign::TYPES[$campaign->type] ?? $campaign->type,
                'total' => $campaignLeads->count(),
                'qualified' => $campaignLeads->where('status', 'qualified')->count(),
                'converted' => $campaignLeads->where('status', 'converted')->count(),
                'cost' => (float) $campaign->actual_cost,
                'cost_per_lead' => $campaignLeads->count() > 0 ? round($campaign->actual_cost / $campaignLeads->count(), 2) : 0,
            ];
        })->filter(fn ($row) => $row['total'] > 0)->sortByDesc('total')->values();

        return view('admin.crm.lead-attribution.index', [
            'range' => $range,
            'bySource' => $bySource,
            'byCampaign' => $byCampaign,
            'totalLeads' => $leads->count(),
            'unattributed' => $unattributed,
            'convertedTotal' => $leads->where('status', 'converted')->count(),
        ]);
    }
}
