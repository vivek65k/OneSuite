<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\OfferLetter;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RecruitmentApprovalController extends Controller
{
    /**
     * Offers still in 'draft' need sign-off before they go out to the
     * candidate. Approve moves the existing OfferLetter.status straight to
     * 'sent' (skipping the manual edit screen); reject moves it to
     * 'withdrawn'. No new table - this is just a queue over an existing
     * status that OfferLetterController's own screens already respect.
     */
    public function index(): View
    {
        $this->authorize('viewAny', OfferLetter::class);

        $offers = OfferLetter::query()
            ->whereHas('candidate', fn ($q) => $q->where('company_id', Company::current()->id)
                ->when(BranchContext::isRestricted(), fn ($q2) => $q2->where('branch_id', BranchContext::currentBranchId())))
            ->where('status', 'draft')
            ->with(['candidate', 'jobPosition'])
            ->orderByDesc('offer_date')
            ->get();

        return view('admin.hrm.approvals.recruitment-approval', ['offers' => $offers]);
    }

    public function approve(OfferLetter $offerLetter): RedirectResponse
    {
        $this->authorize('update', $offerLetter);

        abort_unless($offerLetter->status === 'draft', 422, 'Only draft offers can be approved.');

        $offerLetter->update(['status' => 'sent']);

        return back()->with('status', 'Offer approved and marked sent.');
    }

    public function reject(OfferLetter $offerLetter): RedirectResponse
    {
        $this->authorize('update', $offerLetter);

        abort_unless($offerLetter->status === 'draft', 422, 'Only draft offers can be rejected.');

        $offerLetter->update(['status' => 'withdrawn']);

        return back()->with('status', 'Offer withdrawn.');
    }
}
