<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Crm\Account;
use App\Models\Crm\AccountShare;
use App\Models\Crm\CustomerSegment;
use App\Models\Crm\Team;
use App\Models\Employee;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountController extends Controller
{
    use ExportsCsv;

    protected const SORTABLE = ['name', 'industry', 'created_at'];

    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));
        $segmentId = $request->query('customer_segment_id');
        $industry = (string) $request->query('industry', '');
        $ownerId = $request->query('owner_employee_id');
        $branchId = $request->query('branch_id');
        $sort = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $query = $this->filtered($request);

        return view('admin.crm.accounts.index', [
            'accounts' => $query->withCount('contacts')->with(['owner', 'segment', 'parent', 'branch'])->orderBy($sort, $direction)->paginate(25)->withQueryString(),
            'search' => $search,
            'segmentId' => $segmentId,
            'industry' => $industry,
            'ownerId' => $ownerId,
            'branchId' => $branchId,
            'sort' => $sort,
            'direction' => $direction,
            'segments' => $this->segmentOptions(),
            'industries' => $this->companyQuery()->whereNotNull('industry')->distinct()->orderBy('industry')->pluck('industry'),
            'owners' => $this->employeeOptions(),
            'branches' => $this->branchOptions(),
            'total' => $this->companyQuery()->count(),
            'withOwner' => (clone $this->companyQuery())->whereNotNull('owner_employee_id')->count(),
            'totalContacts' => \App\Models\Crm\Contact::where('company_id', $companyId)->count(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $accounts = $this->filtered($request)->with(['owner', 'segment', 'parent'])->get();

        return $this->streamCsv(
            'accounts.csv',
            ['Name', 'Industry', 'Website', 'Phone', 'Email', 'Parent Account', 'Owner', 'Segment'],
            (function () use ($accounts) {
                foreach ($accounts as $account) {
                    yield [
                        $account->name, $account->industry, $account->website, $account->phone, $account->email,
                        $account->parent?->name, $account->owner?->name, $account->segment?->name,
                    ];
                }
            })()
        );
    }

    protected function filtered(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $segmentId = $request->query('customer_segment_id');
        $industry = (string) $request->query('industry', '');
        $ownerId = $request->query('owner_employee_id');
        $branchId = $request->query('branch_id');

        $query = $this->companyQuery();

        if ($segmentId) {
            $query->where('customer_segment_id', $segmentId);
        }

        if ($industry !== '') {
            $query->where('industry', $industry);
        }

        if ($ownerId) {
            $query->where('owner_employee_id', $ownerId);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($search !== '') {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('industry', 'like', "%{$search}%"));
        }

        return $query;
    }

    public function create(): View
    {
        return view('admin.crm.accounts.form', [
            'account' => new Account,
            'segments' => $this->segmentOptions(),
            'employees' => $this->employeeOptions(),
            'parents' => $this->parentOptions(),
            'branches' => $this->branchOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Account::create($this->validated($request) + ['company_id' => Company::current()->id]);

        return redirect()->route('admin.crm.accounts.index')->with('status', 'Account created.');
    }

    public function edit(Account $account): View
    {
        $this->ownCompany($account);

        return view('admin.crm.accounts.form', [
            'account' => $account,
            'segments' => $this->segmentOptions(),
            'employees' => $this->employeeOptions(),
            'parents' => $this->parentOptions($account),
            'branches' => $this->branchOptions(),
            'shares' => AccountShare::where('account_id', $account->id)->with(['employee', 'team'])->get(),
            'teams' => Team::where('company_id', $account->company_id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function share(Request $request, Account $account): RedirectResponse
    {
        $this->ownCompany($account);

        $data = $request->validate([
            'employee_id' => ['nullable', 'exists:employees,id'],
            'team_id' => ['nullable', 'exists:crm_teams,id'],
            'access_level' => ['required', 'in:'.implode(',', array_keys(AccountShare::ACCESS_LEVELS))],
        ]);

        abort_if(empty($data['employee_id']) && empty($data['team_id']), 422, 'Pick an employee or a team to share with.');

        AccountShare::create($data + [
            'company_id' => $account->company_id,
            'account_id' => $account->id,
            'shared_by_employee_id' => optional(auth()->user()->employee)->id,
        ]);

        return back()->with('status', 'Account shared.');
    }

    public function unshare(Account $account, AccountShare $accountShare): RedirectResponse
    {
        $this->ownCompany($account);
        abort_unless($accountShare->account_id === $account->id, 404);
        $accountShare->delete();

        return back()->with('status', 'Share removed.');
    }

    public function update(Request $request, Account $account): RedirectResponse
    {
        $this->ownCompany($account);

        $data = $this->validated($request);

        abort_if((int) ($data['parent_account_id'] ?? 0) === $account->id, 422, 'An account cannot be its own parent.');

        $account->update($data);

        return redirect()->route('admin.crm.accounts.index')->with('status', 'Account updated.');
    }

    public function destroy(Account $account): RedirectResponse
    {
        $this->ownCompany($account);
        $account->delete();

        return back()->with('status', 'Account deleted.');
    }

    public function mergeForm(): View
    {
        return view('admin.crm.accounts.merge', ['accounts' => $this->companyQuery()->orderBy('name')->get(['id', 'name'])]);
    }

    public function merge(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'survivor_id' => ['required', 'exists:crm_accounts,id', 'different:duplicate_id'],
            'duplicate_id' => ['required', 'exists:crm_accounts,id'],
        ]);

        $survivor = $this->companyQuery()->findOrFail($data['survivor_id']);
        $duplicate = $this->companyQuery()->findOrFail($data['duplicate_id']);

        \App\Models\Crm\Contact::where('account_id', $duplicate->id)->update(['account_id' => $survivor->id]);
        \App\Models\Crm\Opportunity::where('account_id', $duplicate->id)->update(['account_id' => $survivor->id]);
        \App\Models\Crm\CaseModel::where('account_id', $duplicate->id)->update(['account_id' => $survivor->id]);
        \App\Models\Crm\Quotation::where('account_id', $duplicate->id)->update(['account_id' => $survivor->id]);
        Account::where('parent_account_id', $duplicate->id)->update(['parent_account_id' => $survivor->id]);

        $duplicate->delete();

        return redirect()->route('admin.crm.accounts.edit', $survivor)->with('status', "Merged \"{$duplicate->name}\" into \"{$survivor->name}\".");
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'billing_address' => ['nullable', 'string', 'max:2000'],
            'shipping_address' => ['nullable', 'string', 'max:2000'],
            'parent_account_id' => ['nullable', 'exists:crm_accounts,id'],
            'owner_employee_id' => ['nullable', 'exists:employees,id'],
            'customer_segment_id' => ['nullable', 'exists:crm_customer_segments,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ]);
    }

    protected function segmentOptions()
    {
        return CustomerSegment::where('company_id', Company::current()->id)->orderBy('name')->pluck('name', 'id');
    }

    protected function branchOptions()
    {
        return Branch::where('company_id', Company::current()->id)->orderBy('name')->pluck('name', 'id');
    }

    protected function employeeOptions()
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->where('status', 'active')
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    protected function parentOptions(?Account $exclude = null)
    {
        return $this->companyQuery()->when($exclude, fn ($q) => $q->whereKeyNot($exclude->id))->orderBy('name')->pluck('name', 'id');
    }

    protected function companyQuery()
    {
        return Account::query()->where('company_id', Company::current()->id);
    }

    protected function ownCompany(Account $account): void
    {
        abort_unless($account->company_id === Company::current()->id, 404);
    }
}
