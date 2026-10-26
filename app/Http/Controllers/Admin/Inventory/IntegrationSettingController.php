<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\AuditLog;
use App\Models\Inventory\IntegrationSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class IntegrationSettingController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        $existing = IntegrationSetting::where('company_id', $companyId)->get()->keyBy('integration_key');
        $integrations = collect(IntegrationSetting::KEYS)->map(fn ($label, $key) => $existing->get($key) ?? new IntegrationSetting(['integration_key' => $key, 'is_enabled' => false]));

        return view('admin.inventory.integrations.index', [
            'integrations' => $integrations,
            'keys' => IntegrationSetting::KEYS,
        ]);
    }

    /**
     * Configuration only - no outbound calls are made from here. Enabling
     * an integration just records that this module is ready to connect;
     * wiring the actual API client is a separate implementation task.
     */
    public function update(Request $request, string $integrationKey): RedirectResponse
    {
        abort_unless(array_key_exists($integrationKey, IntegrationSetting::KEYS), 404);
        $companyId = Company::current()->id;

        $data = $request->validate([
            'is_enabled' => ['nullable', 'boolean'],
            'endpoint_url' => ['nullable', 'url', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $integration = IntegrationSetting::updateOrCreate(
            ['company_id' => $companyId, 'integration_key' => $integrationKey],
            $data + ['is_enabled' => $request->boolean('is_enabled')]
        );

        AuditLog::record($companyId, Auth::user()?->employee?->id, 'updated', 'IntegrationSetting', $integration->id, "{$integration->integration_key} integration ".($integration->is_enabled ? 'enabled' : 'disabled').'.');

        return back()->with('status', 'Integration settings saved.');
    }
}
