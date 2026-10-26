<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Competitor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompetitorController extends Controller
{
    protected const SORTABLE = ['name', 'opportunities_count'];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $sort = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $query = Competitor::where('company_id', Company::current()->id)->withCount('opportunities');

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        $competitors = $query->orderBy($sort, $direction)->get();

        return view('admin.crm.competitors.index', [
            'competitors' => $competitors,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'total' => $competitors->count(),
            'totalEncounters' => $competitors->sum('opportunities_count'),
        ]);
    }

    public function create(): View
    {
        return view('admin.crm.competitors.form', ['competitor' => new Competitor]);
    }

    public function store(Request $request): RedirectResponse
    {
        Competitor::create($this->validated($request) + ['company_id' => Company::current()->id]);

        return redirect()->route('admin.crm.competitors.index')->with('status', 'Competitor added.');
    }

    public function edit(Competitor $competitor): View
    {
        abort_unless($competitor->company_id === Company::current()->id, 404);

        return view('admin.crm.competitors.form', ['competitor' => $competitor]);
    }

    public function update(Request $request, Competitor $competitor): RedirectResponse
    {
        abort_unless($competitor->company_id === Company::current()->id, 404);

        $competitor->update($this->validated($request));

        return redirect()->route('admin.crm.competitors.index')->with('status', 'Competitor updated.');
    }

    public function destroy(Competitor $competitor): RedirectResponse
    {
        abort_unless($competitor->company_id === Company::current()->id, 404);
        $competitor->delete();

        return back()->with('status', 'Competitor removed.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'strengths' => ['nullable', 'string', 'max:2000'],
            'weaknesses' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
