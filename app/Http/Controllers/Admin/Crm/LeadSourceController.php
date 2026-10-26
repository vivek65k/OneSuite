<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\LeadSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadSourceController extends Controller
{
    protected const SORTABLE = ['name', 'leads_count', 'is_active'];

    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));
        $statusFilter = (string) $request->query('status', '');
        $sort = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $query = LeadSource::where('company_id', $companyId)->withCount('leads');

        if ($statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        $sources = $query->orderBy($sort, $direction)->get();

        $allSources = LeadSource::where('company_id', $companyId)->withCount('leads')->get();

        return view('admin.crm.lead-sources.index', [
            'sources' => $sources,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'sort' => $sort,
            'direction' => $direction,
            'activeCount' => $allSources->where('is_active', true)->count(),
            'totalLeads' => $allSources->sum('leads_count'),
            'totalSources' => $allSources->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.crm.lead-sources.form', ['source' => new LeadSource]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);

        LeadSource::create($data + ['company_id' => Company::current()->id, 'is_active' => true]);

        return redirect()->route('admin.crm.lead-sources.index')->with('status', 'Lead source added.');
    }

    public function edit(LeadSource $leadSource): View
    {
        abort_unless($leadSource->company_id === Company::current()->id, 404);

        return view('admin.crm.lead-sources.form', ['source' => $leadSource]);
    }

    public function update(Request $request, LeadSource $leadSource): RedirectResponse
    {
        abort_unless($leadSource->company_id === Company::current()->id, 404);

        $leadSource->update($request->validate(['name' => ['required', 'string', 'max:255']]));

        return redirect()->route('admin.crm.lead-sources.index')->with('status', 'Lead source updated.');
    }

    public function toggle(LeadSource $leadSource): RedirectResponse
    {
        abort_unless($leadSource->company_id === Company::current()->id, 404);

        $leadSource->update(['is_active' => ! $leadSource->is_active]);

        return back()->with('status', $leadSource->is_active ? 'Lead source activated.' : 'Lead source deactivated.');
    }

    public function destroy(LeadSource $leadSource): RedirectResponse
    {
        abort_unless($leadSource->company_id === Company::current()->id, 404);
        $leadSource->delete();

        return back()->with('status', 'Lead source removed.');
    }
}
