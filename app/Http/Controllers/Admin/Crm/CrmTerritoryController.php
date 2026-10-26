<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Team;
use App\Models\Crm\Territory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrmTerritoryController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.crm.territories.index', [
            'territories' => Territory::where('company_id', $companyId)->with('team')->orderBy('name')->get(),
            'teams' => Team::where('company_id', $companyId)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'team_id' => ['nullable', 'exists:crm_teams,id'],
            'criteria' => ['nullable', 'string', 'max:255'],
        ]);

        Territory::create($data + ['company_id' => Company::current()->id]);

        return back()->with('status', 'Territory created.');
    }

    public function destroy(Territory $territory): RedirectResponse
    {
        abort_unless($territory->company_id === Company::current()->id, 404);
        $territory->delete();

        return back()->with('status', 'Territory removed.');
    }
}
