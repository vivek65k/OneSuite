<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Lead;
use App\Models\Crm\LeadSource;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadConversionReportController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $range = (string) $request->query('range', '90');
        $since = $range === 'all' ? null : now()->subDays((int) $range);

        $query = Lead::where('company_id', $companyId);
        if ($since) {
            $query->where('created_at', '>=', $since);
        }
        $leads = $query->get();

        $funnel = collect(Lead::STATUSES)->map(fn ($label, $status) => [
            'label' => $label,
            'count' => $leads->where('status', $status)->count(),
        ]);

        $bySource = LeadSource::where('company_id', $companyId)->get()->map(function ($source) use ($leads) {
            $set = $leads->where('lead_source_id', $source->id);

            return [
                'name' => $source->name,
                'total' => $set->count(),
                'converted' => $set->where('status', 'converted')->count(),
                'rate' => $set->count() > 0 ? round($set->where('status', 'converted')->count() / $set->count() * 100, 1) : 0,
            ];
        })->filter(fn ($row) => $row['total'] > 0)->sortByDesc('total')->values();

        return view('admin.crm.lead-conversion-report.index', [
            'range' => $range,
            'funnel' => $funnel,
            'bySource' => $bySource,
            'totalLeads' => $leads->count(),
            'converted' => $leads->where('status', 'converted')->count(),
            'conversionRate' => $leads->count() > 0 ? round($leads->where('status', 'converted')->count() / $leads->count() * 100, 1) : 0,
            'avgDaysToConvert' => round($leads->where('status', 'converted')->whereNotNull('converted_at')
                ->avg(fn ($l) => $l->created_at->diffInDays($l->converted_at)) ?? 0, 1),
        ]);
    }
}
