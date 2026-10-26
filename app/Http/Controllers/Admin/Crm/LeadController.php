<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Concerns\ExportsCsv;
use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Crm\Activity;
use App\Models\Crm\Lead;
use App\Models\Crm\LeadSource;
use App\Models\Crm\Note;
use App\Models\Employee;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadController extends Controller
{
    use ExportsCsv, ResolvesCurrentEmployee;

    protected const SORTABLE = ['name', 'score', 'status', 'created_at'];

    public function index(Request $request, string $scope = 'all'): View
    {
        $status = (string) $request->query('status', '');
        $search = trim((string) $request->query('search', ''));
        $sourceId = $request->query('lead_source_id');
        $sort = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'created_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $query = $this->companyQuery()->where('is_recycled', false)->with(['source', 'assignedTo'])->orderBy($sort, $direction);

        if ($scope === 'mine') {
            $query->where('assigned_to_employee_id', $this->currentEmployee()->id);
        } elseif ($scope === 'unassigned') {
            $query->whereNull('assigned_to_employee_id');
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($sourceId) {
            $query->where('lead_source_id', $sourceId);
        }

        if ($search !== '') {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('company_name', 'like', "%{$search}%"));
        }

        $scopedBase = $this->scopedCompanyQuery($scope);

        return view('admin.crm.leads.index', [
            'scope' => $scope,
            'status' => $status,
            'search' => $search,
            'sourceId' => $sourceId,
            'sort' => $sort,
            'direction' => $direction,
            'sources' => $this->sourceOptions(),
            'leads' => $query->paginate(25)->withQueryString(),
            'employees' => $this->employeeOptions(),
            'counts' => [
                'all' => $this->companyQuery()->where('is_recycled', false)->count(),
                'mine' => (clone $this->companyQuery())->where('is_recycled', false)->where('assigned_to_employee_id', optional(auth()->user()->employee)->id)->count(),
                'unassigned' => (clone $this->companyQuery())->where('is_recycled', false)->whereNull('assigned_to_employee_id')->count(),
            ],
            'statusCounts' => (clone $scopedBase)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
            'bulk_action' => ['required', 'in:assign,delete,status'],
            'assigned_to_employee_id' => ['nullable', 'exists:employees,id'],
            'status' => ['nullable', Rule::in(array_keys(Lead::STATUSES))],
        ]);

        $leads = $this->companyQuery()->whereIn('id', $data['ids'])->get();

        if ($data['bulk_action'] === 'assign') {
            $leads->each(fn ($lead) => $lead->update(['assigned_to_employee_id' => $data['assigned_to_employee_id']]));
            $message = "Assigned {$leads->count()} lead(s).";
        } elseif ($data['bulk_action'] === 'status') {
            $leads->each(fn ($lead) => $lead->update(['status' => $data['status']]));
            $message = "Updated status for {$leads->count()} lead(s).";
        } else {
            $count = $leads->count();
            Lead::whereIn('id', $leads->pluck('id'))->delete();
            $message = "Deleted {$count} lead(s).";
        }

        return back()->with('status', $message);
    }

    public function create(): View
    {
        return view('admin.crm.leads.create', [
            'lead' => new Lead,
            'sources' => $this->sourceOptions(),
            'employees' => $this->employeeOptions(),
            'branches' => $this->branchOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Lead::create($this->validated($request) + ['company_id' => Company::current()->id]);

        return redirect()->route('admin.crm.leads.index')->with('status', 'Lead created.');
    }

    public function show(Lead $lead): View
    {
        $this->ownCompany($lead);

        $activities = Activity::where('company_id', $lead->company_id)->where('related_type', Lead::class)
            ->where('related_id', $lead->id)->with('owner')->orderByDesc('created_at')->get();

        $notes = Note::where('company_id', $lead->company_id)->where('notable_type', Lead::class)
            ->where('notable_id', $lead->id)->with('owner')->orderByDesc('created_at')->get();

        $timeline = $activities->map(fn ($a) => ['type' => $a->type, 'subject' => $a->subject, 'detail' => $a->description, 'owner' => $a->owner?->name, 'when' => $a->created_at])
            ->concat($notes->map(fn ($n) => ['type' => 'note', 'subject' => 'Note', 'detail' => $n->body, 'owner' => $n->owner?->name, 'when' => $n->created_at]))
            ->sortByDesc('when')->values();

        return view('admin.crm.leads.show', [
            'lead' => $lead->load(['source', 'assignedTo', 'branch', 'campaign']),
            'timeline' => $timeline,
        ]);
    }

    public function edit(Lead $lead): View
    {
        $this->ownCompany($lead);

        return view('admin.crm.leads.edit', [
            'lead' => $lead,
            'sources' => $this->sourceOptions(),
            'employees' => $this->employeeOptions(),
            'branches' => $this->branchOptions(),
        ]);
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $this->ownCompany($lead);

        $lead->update($this->validated($request));

        return redirect()->route('admin.crm.leads.index')->with('status', 'Lead updated.');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $this->ownCompany($lead);
        $lead->delete();

        return back()->with('status', 'Lead deleted.');
    }

    public function export(): StreamedResponse
    {
        $leads = $this->companyQuery()->with(['source', 'assignedTo'])->get();

        return $this->streamCsv(
            'leads.csv',
            ['Name', 'Email', 'Phone', 'Company', 'Source', 'Status', 'Score', 'Assigned To'],
            (function () use ($leads) {
                foreach ($leads as $lead) {
                    yield [
                        $lead->name, $lead->email, $lead->phone, $lead->company_name,
                        $lead->source?->name, Lead::STATUSES[$lead->status] ?? $lead->status,
                        $lead->score, $lead->assignedTo?->name,
                    ];
                }
            })()
        );
    }

    public function importPreview(Request $request): View|RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls']]);

        $path = $request->file('file')->store('lead-imports');
        $rows = $this->readSpreadsheet(\Illuminate\Support\Facades\Storage::path($path));

        if (empty($rows)) {
            return back()->with('status', 'The file appears to be empty.');
        }

        $header = array_shift($rows);

        session(['lead_import_path' => $path, 'lead_import_header' => $header]);

        return view('admin.crm.leads.import-map', [
            'header' => $header,
            'sampleRows' => array_slice($rows, 0, 5),
            'targetFields' => [
                'name' => 'Name (required)', 'email' => 'Email', 'phone' => 'Phone',
                'company_name' => 'Company', 'budget' => 'Budget', 'timeline' => 'Timeline',
            ],
        ]);
    }

    public function importConfirm(Request $request): RedirectResponse
    {
        $data = $request->validate(['mapping' => ['required', 'array']]);

        $path = session('lead_import_path');
        $header = session('lead_import_header');
        abort_unless($path && $header, 400, 'Import session expired - please upload the file again.');

        $rows = $this->readSpreadsheet(\Illuminate\Support\Facades\Storage::path($path));
        array_shift($rows);

        $companyId = Company::current()->id;
        $imported = 0;

        foreach ($rows as $row) {
            $record = array_combine($header, array_pad($row, count($header), null));
            $leadData = [];
            foreach ($data['mapping'] as $field => $column) {
                if ($column !== '' && isset($record[$column])) {
                    $leadData[$field] = $record[$column];
                }
            }

            if (empty($leadData['name'])) {
                continue;
            }

            Lead::create($leadData + ['company_id' => $companyId, 'status' => 'new']);
            $imported++;
        }

        \Illuminate\Support\Facades\Storage::delete($path);
        session()->forget(['lead_import_path', 'lead_import_header']);

        return redirect()->route('admin.crm.leads.index')->with('status', "Imported {$imported} lead(s).");
    }

    protected function readSpreadsheet(string $fullPath): array
    {
        $spreadsheet = IOFactory::load($fullPath);

        return $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'lead_source_id' => ['nullable', 'exists:crm_lead_sources,id'],
            'assigned_to_employee_id' => ['nullable', 'exists:employees,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'status' => ['required', Rule::in(array_keys(Lead::STATUSES))],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    protected function sourceOptions()
    {
        return LeadSource::where('company_id', Company::current()->id)->where('is_active', true)->orderBy('name')->pluck('name', 'id');
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

    protected function branchOptions()
    {
        return Branch::where('company_id', Company::current()->id)->orderBy('name')->pluck('name', 'id');
    }

    protected function companyQuery()
    {
        return Lead::query()->where('company_id', Company::current()->id);
    }

    protected function scopedCompanyQuery(string $scope)
    {
        $query = $this->companyQuery()->where('is_recycled', false);

        if ($scope === 'mine') {
            $query->where('assigned_to_employee_id', optional(auth()->user()->employee)->id);
        } elseif ($scope === 'unassigned') {
            $query->whereNull('assigned_to_employee_id');
        }

        return $query;
    }

    protected function ownCompany(Lead $lead): void
    {
        abort_unless($lead->company_id === Company::current()->id, 404);
    }
}
