<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DuplicateLeadController extends Controller
{
    /**
     * Groups leads that share an email or phone - the simplest useful
     * signal for "probably the same person", without a fuzzy-matching
     * library. Leads already flagged (is_duplicate_of_lead_id set) are
     * shown so the grouping stays visible after the merge decision.
     */
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));

        $leads = Lead::query()->where('company_id', $companyId)->get();

        $groups = $leads
            ->filter(fn ($lead) => $lead->email || $lead->phone)
            ->groupBy(fn ($lead) => mb_strtolower((string) $lead->email) ?: $lead->phone)
            ->filter(fn ($group) => $group->count() > 1)
            ->values();

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $groups = $groups->filter(fn ($group) => $group->contains(fn ($lead) => str_contains(mb_strtolower($lead->name), $needle)
                || str_contains(mb_strtolower((string) $lead->email), $needle)))->values();
        }

        return view('admin.crm.duplicates.index', [
            'groups' => $groups,
            'search' => $search,
            'flaggedCount' => $leads->whereNotNull('is_duplicate_of_lead_id')->count(),
        ]);
    }

    public function markDuplicate(Request $request, Lead $lead): RedirectResponse
    {
        abort_unless($lead->company_id === Company::current()->id, 404);

        $data = $request->validate(['is_duplicate_of_lead_id' => ['required', 'exists:crm_leads,id']]);

        abort_if((int) $data['is_duplicate_of_lead_id'] === $lead->id, 422, 'A lead cannot be marked as a duplicate of itself.');
        abort_unless(Lead::where('id', $data['is_duplicate_of_lead_id'])->where('company_id', $lead->company_id)->exists(), 404);

        $lead->update($data);

        return back()->with('status', 'Lead marked as duplicate.');
    }

    public function unmarkDuplicate(Lead $lead): RedirectResponse
    {
        abort_unless($lead->company_id === Company::current()->id, 404);
        $lead->update(['is_duplicate_of_lead_id' => null]);

        return back()->with('status', 'Duplicate flag removed.');
    }
}
