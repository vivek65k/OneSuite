<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Campaign;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmsCampaignController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));
        $channelFilter = (string) $request->query('channel', '');

        $query = Campaign::where('company_id', $companyId)->whereIn('type', ['sms', 'whatsapp'])->withCount('members');

        if (in_array($channelFilter, ['sms', 'whatsapp'], true)) {
            $query->where('type', $channelFilter);
        }

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        $campaigns = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $all = Campaign::where('company_id', $companyId)->whereIn('type', ['sms', 'whatsapp'])->get();

        return view('admin.crm.sms-campaigns.index', [
            'campaigns' => $campaigns,
            'search' => $search,
            'channelFilter' => $channelFilter,
            'totalSent' => $all->sum('sent_count'),
            'totalResponded' => $all->sum('responded_count'),
            'smsCount' => $all->where('type', 'sms')->count(),
            'whatsappCount' => $all->where('type', 'whatsapp')->count(),
        ]);
    }
}
