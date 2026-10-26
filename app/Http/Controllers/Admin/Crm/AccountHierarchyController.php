<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Account;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountHierarchyController extends Controller
{
    /**
     * Top-level accounts (no parent) with their descendants eager loaded
     * a few levels deep - small account counts expected, so a handful of
     * nested eager loads is simpler and fast enough without a recursive CTE.
     * When searching, the tree gives way to a flat matches list instead -
     * a match buried three levels down would otherwise be invisible.
     */
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));

        $roots = collect();
        $matches = collect();

        if ($search !== '') {
            $matches = Account::where('company_id', $companyId)
                ->where('name', 'like', "%{$search}%")
                ->with(['owner', 'parent'])
                ->withCount('contacts')
                ->orderBy('name')
                ->get();
        } else {
            $roots = Account::where('company_id', $companyId)
                ->whereNull('parent_account_id')
                ->with(['children.children.children', 'owner'])
                ->withCount('contacts')
                ->orderBy('name')
                ->get();
        }

        $childCount = Account::where('company_id', $companyId)->whereNotNull('parent_account_id')->count();

        return view('admin.crm.account-hierarchy.index', [
            'roots' => $roots,
            'matches' => $matches,
            'search' => $search,
            'total' => Account::where('company_id', $companyId)->count(),
            'topLevelCount' => Account::where('company_id', $companyId)->whereNull('parent_account_id')->count(),
            'childCount' => $childCount,
        ]);
    }
}
