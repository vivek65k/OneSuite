<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Campaign;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignController extends Controller
{
    use ExportsCsv;

    protected const SORTABLE = ['name', 'type', 'status', 'start_date', 'budget', 'sent_count'];

    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));
        $typeFilter = (string) $request->query('type', '');
        $statusFilter = (string) $request->query('status', '');
        $sort = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'start_date';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $query = Campaign::where('company_id', $companyId)->with('owner')->withCount('members');

        if ($typeFilter !== '') {
            $query->where('type', $typeFilter);
        }

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        $campaigns = $query->orderBy($sort, $direction)->paginate(20)->withQueryString();

        $all = Campaign::where('company_id', $companyId)->get();

        return view('admin.crm.campaigns.index', [
            'campaigns' => $campaigns,
            'search' => $search,
            'typeFilter' => $typeFilter,
            'statusFilter' => $statusFilter,
            'sort' => $sort,
            'direction' => $direction,
            'types' => Campaign::TYPES,
            'statuses' => Campaign::STATUSES,
            'activeCount' => $all->where('status', 'active')->count(),
            'totalBudget' => $all->sum('budget'),
            'totalSpend' => $all->sum('actual_cost'),
            'totalSent' => $all->sum('sent_count'),
        ]);
    }

    public function export(Request $request)
    {
        $companyId = Company::current()->id;
        $campaigns = Campaign::where('company_id', $companyId)->with('owner')->get();

        return $this->streamCsv('campaigns.csv', ['Name', 'Type', 'Status', 'Start Date', 'End Date', 'Budget', 'Actual Cost', 'Sent', 'Opened', 'Clicked', 'Owner'], $campaigns->map(fn ($c) => [
            $c->name, Campaign::TYPES[$c->type] ?? $c->type, Campaign::STATUSES[$c->status] ?? $c->status,
            $c->start_date?->format('Y-m-d'), $c->end_date?->format('Y-m-d'), $c->budget, $c->actual_cost,
            $c->sent_count, $c->opened_count, $c->clicked_count, $c->owner?->name,
        ]));
    }

    public function create(Request $request): View
    {
        $companyId = Company::current()->id;

        return view('admin.crm.campaigns.form', [
            'campaign' => new Campaign(['type' => $request->query('type', 'email')]),
            'employees' => Employee::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $campaign = Campaign::create($data + ['company_id' => Company::current()->id]);

        return redirect()->route('admin.crm.campaigns.edit', $campaign)->with('status', 'Campaign created.');
    }

    public function edit(Campaign $campaign): View
    {
        abort_unless($campaign->company_id === Company::current()->id, 404);

        return view('admin.crm.campaigns.form', [
            'campaign' => $campaign,
            'employees' => Employee::where('company_id', $campaign->company_id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        abort_unless($campaign->company_id === Company::current()->id, 404);
        $campaign->update($this->validated($request));

        return redirect()->route('admin.crm.campaigns.edit', $campaign)->with('status', 'Campaign updated.');
    }

    public function show(Campaign $campaign): View
    {
        abort_unless($campaign->company_id === Company::current()->id, 404);

        $members = $campaign->members()->with('member')->orderByDesc('added_at')->paginate(20);

        return view('admin.crm.campaigns.show', [
            'campaign' => $campaign,
            'members' => $members,
        ]);
    }

    public function send(Campaign $campaign): RedirectResponse
    {
        abort_unless($campaign->company_id === Company::current()->id, 404);

        $sent = $campaign->members()->where('status', 'added')->update(['status' => 'sent', 'added_at' => now()]);
        $campaign->recalculateCounts();

        if ($campaign->status === 'draft') {
            $campaign->update(['status' => 'active']);
        }

        return back()->with('status', $sent > 0 ? "Sent to {$sent} member(s)." : 'No pending members to send to.');
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        abort_unless($campaign->company_id === Company::current()->id, 404);
        $campaign->delete();

        return redirect()->route('admin.crm.campaigns.index')->with('status', 'Campaign deleted.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:'.implode(',', array_keys(Campaign::TYPES))],
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(Campaign::STATUSES))],
            'subject' => ['nullable', 'string', 'max:255'],
            'message_body' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'actual_cost' => ['nullable', 'numeric', 'min:0'],
            'owner_employee_id' => ['nullable', 'exists:employees,id'],
            'description' => ['nullable', 'string'],
        ]);
    }
}
