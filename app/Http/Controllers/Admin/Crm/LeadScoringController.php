<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadScoringController extends Controller
{
    protected const SORTABLE = ['name', 'score'];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $sort = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'score';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $base = Lead::query()->where('company_id', Company::current()->id)->whereNotIn('status', ['converted']);

        if ($search !== '') {
            $base->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('company_name', 'like', "%{$search}%"));
        }

        $leads = (clone $base)->with('source')->orderBy($sort, $direction)->paginate(25)->withQueryString();

        return view('admin.crm.scoring.index', [
            'leads' => $leads,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'total' => (clone $base)->count(),
            'avgScore' => round((clone $base)->avg('score') ?? 0),
            'hotCount' => (clone $base)->where('score', '>=', 70)->count(),
        ]);
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        abort_unless($lead->company_id === Company::current()->id, 404);

        $lead->update($request->validate(['score' => ['required', 'integer', 'min:0', 'max:100']]));

        return back()->with('status', 'Score updated.');
    }
}
