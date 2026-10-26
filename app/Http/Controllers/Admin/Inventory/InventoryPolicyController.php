<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\AuditLog;
use App\Models\Inventory\Item;
use App\Models\Inventory\InventorySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class InventoryPolicyController extends Controller
{
    use ResolvesCurrentEmployee;

    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.inventory.policies.index', [
            'settings' => InventorySetting::forCompany($companyId),
            'costingMethods' => Item::COSTING_METHODS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'allow_negative_stock' => ['nullable', 'boolean'],
            'allow_backorder' => ['nullable', 'boolean'],
            'require_batch_number_validation' => ['nullable', 'boolean'],
            'require_serial_number_validation' => ['nullable', 'boolean'],
            'default_costing_method' => ['required', 'in:moving_avg,fifo'],
            'landed_cost_overhead_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $settings = InventorySetting::forCompany($companyId);
        $settings->update($data + [
            'allow_negative_stock' => $request->boolean('allow_negative_stock'),
            'allow_backorder' => $request->boolean('allow_backorder'),
            'require_batch_number_validation' => $request->boolean('require_batch_number_validation'),
            'require_serial_number_validation' => $request->boolean('require_serial_number_validation'),
        ]);

        AuditLog::record($companyId, Auth::user()?->employee?->id, 'updated', 'InventorySetting', $settings->id, 'Inventory policies updated.');

        return back()->with('status', 'Inventory policies saved.');
    }
}
