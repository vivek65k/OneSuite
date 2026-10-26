<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Campaign;
use App\Models\Crm\CampaignMember;
use App\Models\Crm\Contact;
use App\Models\Crm\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignMemberController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $campaignId = $request->query('campaign_id', '');
        $statusFilter = (string) $request->query('status', '');

        $query = CampaignMember::where('company_id', $companyId)->with(['campaign', 'member']);

        if ($campaignId !== '') {
            $query->where('campaign_id', $campaignId);
        }

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        $members = $query->orderByDesc('added_at')->paginate(25)->withQueryString();

        return view('admin.crm.campaign-members.index', [
            'members' => $members,
            'campaigns' => Campaign::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'campaignId' => $campaignId,
            'statusFilter' => $statusFilter,
            'statuses' => CampaignMember::STATUSES,
            'leads' => Lead::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'contacts' => Contact::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;

        $data = $request->validate([
            'campaign_id' => ['required', 'exists:crm_campaigns,id'],
            'member_type' => ['required', 'in:lead,contact'],
            'member_id' => ['required', 'integer'],
        ]);

        $campaign = Campaign::where('company_id', $companyId)->findOrFail($data['campaign_id']);
        $memberClass = CampaignMember::MEMBERABLE[$data['member_type']];
        abort_unless($memberClass::where('company_id', $companyId)->where('id', $data['member_id'])->exists(), 404);

        CampaignMember::firstOrCreate([
            'campaign_id' => $campaign->id,
            'member_type' => $memberClass,
            'member_id' => $data['member_id'],
        ], [
            'company_id' => $companyId,
            'status' => 'added',
            'added_at' => now(),
        ]);

        return back()->with('status', 'Member added to campaign.');
    }

    public function updateStatus(Request $request, CampaignMember $campaignMember): RedirectResponse
    {
        abort_unless($campaignMember->company_id === Company::current()->id, 404);

        $data = $request->validate(['status' => ['required', 'in:'.implode(',', array_keys(CampaignMember::STATUSES))]]);

        $campaignMember->update($data);
        $campaignMember->campaign->recalculateCounts();

        return back()->with('status', 'Member status updated.');
    }

    public function destroy(CampaignMember $campaignMember): RedirectResponse
    {
        abort_unless($campaignMember->company_id === Company::current()->id, 404);
        $campaign = $campaignMember->campaign;
        $campaignMember->delete();
        $campaign->recalculateCounts();

        return back()->with('status', 'Member removed from campaign.');
    }
}
