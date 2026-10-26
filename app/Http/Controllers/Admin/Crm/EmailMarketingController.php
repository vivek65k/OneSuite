<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Campaign;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailMarketingController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));
        $statusFilter = (string) $request->query('status', '');

        $query = Campaign::where('company_id', $companyId)->where('type', 'email')->withCount('members');

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        $campaigns = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $all = Campaign::where('company_id', $companyId)->where('type', 'email')->get();

        return view('admin.crm.email-marketing.index', [
            'campaigns' => $campaigns,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'statuses' => Campaign::STATUSES,
            'totalSent' => $all->sum('sent_count'),
            'avgOpenRate' => $all->count() > 0 ? round($all->avg(fn ($c) => $c->open_rate), 1) : 0,
            'avgClickRate' => $all->count() > 0 ? round($all->avg(fn ($c) => $c->click_rate), 1) : 0,
        ]);
    }
}
