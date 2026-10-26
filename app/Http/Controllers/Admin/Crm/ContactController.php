<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Account;
use App\Models\Crm\Activity;
use App\Models\Crm\Contact;
use App\Models\Crm\CustomerSegment;
use App\Models\Crm\Note;
use App\Models\Crm\Opportunity;
use App\Models\Employee;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactController extends Controller
{
    use ExportsCsv;

    protected const SORTABLE = ['name', 'job_title', 'created_at'];

    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));
        $accountId = $request->query('account_id');
        $segmentId = $request->query('customer_segment_id');
        $ownerId = $request->query('owner_employee_id');
        $sort = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        return view('admin.crm.contacts.index', [
            'contacts' => $this->filtered($request)->with(['account', 'owner', 'segment'])->orderBy($sort, $direction)->paginate(25)->withQueryString(),
            'search' => $search,
            'accountId' => $accountId,
            'segmentId' => $segmentId,
            'ownerId' => $ownerId,
            'sort' => $sort,
            'direction' => $direction,
            'accounts' => $this->accountOptions(),
            'segments' => $this->segmentOptions(),
            'owners' => $this->employeeOptions(),
            'total' => $this->companyQuery()->count(),
            'withAccount' => (clone $this->companyQuery())->whereNotNull('account_id')->count(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $contacts = $this->filtered($request)->with(['account', 'owner', 'segment'])->get();

        return $this->streamCsv(
            'contacts.csv',
            ['Name', 'Job Title', 'Email', 'Phone', 'Account', 'Owner', 'Segment'],
            (function () use ($contacts) {
                foreach ($contacts as $contact) {
                    yield [
                        $contact->name, $contact->job_title, $contact->email, $contact->phone,
                        $contact->account?->name ?? $contact->company_name, $contact->owner?->name, $contact->segment?->name,
                    ];
                }
            })()
        );
    }

    protected function filtered(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $accountId = $request->query('account_id');
        $segmentId = $request->query('customer_segment_id');
        $ownerId = $request->query('owner_employee_id');

        $query = $this->companyQuery();

        if ($accountId) {
            $query->where('account_id', $accountId);
        }

        if ($segmentId) {
            $query->where('customer_segment_id', $segmentId);
        }

        if ($ownerId) {
            $query->where('owner_employee_id', $ownerId);
        }

        if ($search !== '') {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('company_name', 'like', "%{$search}%"));
        }

        return $query;
    }

    public function show(Contact $contact): View
    {
        $this->ownCompany($contact);
        $companyId = $contact->company_id;

        $opportunities = Opportunity::where('company_id', $companyId)->where('contact_id', $contact->id)->with('stage')->get();

        $activities = Activity::where('company_id', $companyId)->where('related_type', Contact::class)
            ->where('related_id', $contact->id)->with('owner')->orderByDesc('created_at')->get();
        $notes = Note::where('company_id', $companyId)->where('notable_type', Contact::class)
            ->where('notable_id', $contact->id)->with('owner')->orderByDesc('created_at')->get();

        $timeline = $activities->map(fn ($a) => ['type' => $a->type, 'subject' => $a->subject, 'detail' => $a->description, 'owner' => $a->owner?->name, 'when' => $a->created_at])
            ->concat($notes->map(fn ($n) => ['type' => 'note', 'subject' => 'Note', 'detail' => $n->body, 'owner' => $n->owner?->name, 'when' => $n->created_at]))
            ->sortByDesc('when')->values();

        return view('admin.crm.contacts.show', [
            'contact' => $contact->load(['account', 'owner', 'segment']),
            'opportunities' => $opportunities,
            'timeline' => $timeline,
        ]);
    }

    public function create(): View
    {
        return view('admin.crm.contacts.form', [
            'contact' => new Contact,
            'accounts' => $this->accountOptions(),
            'segments' => $this->segmentOptions(),
            'employees' => $this->employeeOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Contact::create($this->validated($request) + ['company_id' => Company::current()->id]);

        return redirect()->route('admin.crm.contacts.index')->with('status', 'Contact created.');
    }

    public function edit(Contact $contact): View
    {
        $this->ownCompany($contact);

        return view('admin.crm.contacts.form', [
            'contact' => $contact,
            'accounts' => $this->accountOptions(),
            'segments' => $this->segmentOptions(),
            'employees' => $this->employeeOptions(),
        ]);
    }

    public function update(Request $request, Contact $contact): RedirectResponse
    {
        $this->ownCompany($contact);

        $contact->update($this->validated($request));

        return redirect()->route('admin.crm.contacts.index')->with('status', 'Contact updated.');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $this->ownCompany($contact);
        $contact->delete();

        return back()->with('status', 'Contact deleted.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'account_id' => ['nullable', 'exists:crm_accounts,id'],
            'owner_employee_id' => ['nullable', 'exists:employees,id'],
            'customer_segment_id' => ['nullable', 'exists:crm_customer_segments,id'],
        ]);
    }

    protected function accountOptions()
    {
        return Account::where('company_id', Company::current()->id)->orderBy('name')->pluck('name', 'id');
    }

    protected function segmentOptions()
    {
        return CustomerSegment::where('company_id', Company::current()->id)->orderBy('name')->pluck('name', 'id');
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

    protected function companyQuery()
    {
        return Contact::query()->where('company_id', Company::current()->id);
    }

    protected function ownCompany(Contact $contact): void
    {
        abort_unless($contact->company_id === Company::current()->id, 404);
    }
}
