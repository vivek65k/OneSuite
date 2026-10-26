<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Account;
use App\Models\Crm\CaseModel;
use App\Models\Crm\Note;
use App\Models\Crm\Opportunity;
use App\Models\Crm\Quotation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class Customer360Controller extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $accounts = Account::where('company_id', $companyId)->orderBy('name')->pluck('name', 'id');

        $accountId = $request->query('account_id') ?: $accounts->keys()->first();
        $account = $accountId
            ? Account::where('company_id', $companyId)
                ->with(['owner', 'segment', 'parent', 'children', 'contacts.owner', 'contacts.lead'])
                ->withCount('contacts')
                ->find($accountId)
            : null;

        $opportunities = $account ? Opportunity::where('company_id', $companyId)->where('account_id', $account->id)->with('stage')->orderByDesc('created_at')->get() : collect();
        $quotations = $account ? Quotation::where('company_id', $companyId)->where('account_id', $account->id)->orderByDesc('created_at')->get() : collect();
        $cases = $account ? CaseModel::where('company_id', $companyId)->where('account_id', $account->id)->orderByDesc('created_at')->get() : collect();
        $notes = $account ? Note::where('company_id', $companyId)->where('notable_type', Account::class)->where('notable_id', $account->id)->with('owner')->orderByDesc('created_at')->get() : collect();

        return view('admin.crm.customer-360.index', [
            'accounts' => $accounts,
            'account' => $account,
            'opportunities' => $opportunities,
            'quotations' => $quotations,
            'cases' => $cases,
            'notes' => $notes,
        ]);
    }
}
