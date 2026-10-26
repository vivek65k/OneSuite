<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Contract;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RenewalManagementController extends Controller
{
    /**
     * Contracts ending within this window need a decision - renew or let
     * lapse - so they show up here instead of only on the day they expire.
     */
    protected const LOOKAHEAD_DAYS = 90;

    public function index(): View
    {
        $companyId = Company::current()->id;
        $cutoff = now()->addDays(self::LOOKAHEAD_DAYS);

        $upcoming = Contract::where('company_id', $companyId)
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->where('end_date', '<=', $cutoff)
            ->with(['account', 'owner'])
            ->orderBy('end_date')
            ->get();

        $expired = Contract::where('company_id', $companyId)
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->where('end_date', '<', now())
            ->count();

        return view('admin.crm.renewal-management.index', [
            'upcoming' => $upcoming,
            'autoRenewCount' => $upcoming->where('auto_renew', true)->count(),
            'expiredCount' => $expired,
        ]);
    }

    /**
     * Clones the contract into a fresh one starting the day after the old
     * one ends, same length and value, linked back via renewed_from - the
     * original stays untouched as history instead of being overwritten.
     */
    public function renew(Contract $contract): RedirectResponse
    {
        abort_unless($contract->company_id === Company::current()->id, 404);
        abort_unless($contract->end_date, 422, 'This contract has no end date to renew from.');

        $new = $contract->renew(Contract::nextNumber($contract->company_id));

        return redirect()->route('admin.crm.contracts.edit', $new)->with('status', 'Renewal contract created as a draft - review and activate it.');
    }
}
