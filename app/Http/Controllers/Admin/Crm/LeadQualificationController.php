<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadQualificationController extends Controller
{
    protected const SORTABLE = ['name', 'score', 'created_at'];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $statusFilter = (string) $request->query('status', '');
        $sort = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'score';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $base = Lead::query()->where('company_id', Company::current()->id)->whereIn('status', ['new', 'contacted']);

        if (in_array($statusFilter, ['new', 'contacted'], true)) {
            $base->where('status', $statusFilter);
        }

        if ($search !== '') {
            $base->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('company_name', 'like', "%{$search}%"));
        }

        $leads = (clone $base)->with(['source', 'assignedTo'])->orderBy($sort, $direction)->paginate(25)->withQueryString();

        $countsBase = Lead::query()->where('company_id', Company::current()->id)->whereIn('status', ['new', 'contacted']);

        return view('admin.crm.qualification.index', [
            'leads' => $leads,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'sort' => $sort,
            'direction' => $direction,
            'total' => (clone $countsBase)->count(),
            'newCount' => (clone $countsBase)->where('status', 'new')->count(),
            'contactedCount' => (clone $countsBase)->where('status', 'contacted')->count(),
        ]);
    }

    public function updateBant(Request $request, Lead $lead): RedirectResponse
    {
        abort_unless($lead->company_id === Company::current()->id, 404);

        $data = $request->validate([
            'budget' => ['nullable', 'string', 'max:100'],
            'has_need' => ['nullable', 'boolean'],
            'has_authority' => ['nullable', 'boolean'],
            'timeline' => ['nullable', 'in:'.implode(',', array_keys(Lead::TIMELINES))],
        ]);

        $lead->update($data + ['has_need' => $request->boolean('has_need'), 'has_authority' => $request->boolean('has_authority')]);

        return back()->with('status', 'Qualification details saved.');
    }

    public function qualify(Lead $lead): RedirectResponse
    {
        abort_unless($lead->company_id === Company::current()->id, 404);
        $lead->update(['status' => 'qualified']);

        return back()->with('status', 'Lead qualified.');
    }

    public function disqualify(Lead $lead): RedirectResponse
    {
        abort_unless($lead->company_id === Company::current()->id, 404);
        $lead->update(['status' => 'unqualified']);

        return back()->with('status', 'Lead marked unqualified.');
    }
}
