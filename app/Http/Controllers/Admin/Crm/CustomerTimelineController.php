<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Account;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerTimelineController extends Controller
{
    /**
     * A single chronological feed built from events we already have -
     * account/contact creation and lead conversions - rather than a
     * separate activity-log table. Good enough to answer "what happened
     * with this account" without a new logging subsystem.
     */
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $accounts = Account::where('company_id', $companyId)->orderBy('name')->pluck('name', 'id');

        $accountId = $request->query('account_id') ?: $accounts->keys()->first();
        $account = $accountId ? Account::where('company_id', $companyId)->with('contacts.lead')->find($accountId) : null;
        $type = (string) $request->query('type', '');

        $events = collect();

        if ($account) {
            $events->push(['type' => 'account', 'date' => $account->created_at, 'icon' => 'ti-building', 'tone' => 'primary', 'title' => 'Account created', 'detail' => $account->name]);

            foreach ($account->contacts as $contact) {
                $events->push(['type' => 'contact', 'date' => $contact->created_at, 'icon' => 'ti-user-plus', 'tone' => 'info', 'title' => 'Contact added', 'detail' => $contact->name.($contact->job_title ? ' · '.$contact->job_title : '')]);

                if ($contact->lead && $contact->lead->converted_at) {
                    $events->push(['type' => 'conversion', 'date' => $contact->lead->converted_at, 'icon' => 'ti-repeat', 'tone' => 'success', 'title' => 'Converted from a lead', 'detail' => $contact->name]);
                }
            }
        }

        if ($type !== '') {
            $events = $events->where('type', $type);
        }

        return view('admin.crm.customer-timeline.index', [
            'accounts' => $accounts,
            'account' => $account,
            'type' => $type,
            'events' => $events->sortByDesc('date')->values(),
        ]);
    }
}
