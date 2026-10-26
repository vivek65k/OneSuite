<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Integration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrmIntegrationController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.crm.integrations.index', [
            'integrations' => Integration::where('company_id', $companyId)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:'.implode(',', array_keys(Integration::TYPES))],
            'webhook_url' => ['nullable', 'url', 'max:255'],
        ]);

        Integration::create([
            'company_id' => Company::current()->id,
            'name' => $data['name'],
            'type' => $data['type'],
            'config' => $data['webhook_url'] ? ['webhook_url' => $data['webhook_url']] : null,
            'is_connected' => false,
        ]);

        return back()->with('status', 'Integration added.');
    }

    public function toggle(Integration $integration): RedirectResponse
    {
        abort_unless($integration->company_id === Company::current()->id, 404);
        $integration->update(['is_connected' => ! $integration->is_connected]);

        return back()->with('status', $integration->is_connected ? 'Integration connected.' : 'Integration disconnected.');
    }

    public function destroy(Integration $integration): RedirectResponse
    {
        abort_unless($integration->company_id === Company::current()->id, 404);
        $integration->delete();

        return back()->with('status', 'Integration removed.');
    }
}
