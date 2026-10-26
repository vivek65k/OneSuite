<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Inventory\Stock;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarehouseDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $warehouses = Warehouse::where('company_id', $companyId)->with(['branch', 'manager'])->get();

        $stock = Stock::where('company_id', $companyId)->with('item')->get();
        $byWarehouse = $stock->groupBy('warehouse_id');

        $rows = $warehouses->map(function ($warehouse) use ($byWarehouse) {
            $rows = $byWarehouse->get($warehouse->id, collect());

            return [
                'warehouse' => $warehouse,
                'itemCount' => $rows->pluck('item_id')->unique()->count(),
                'totalUnits' => $rows->sum('quantity'),
                'totalValue' => $rows->sum(fn ($row) => $row->quantity * ($row->item?->cost_price ?? 0)),
            ];
        });

        $selectedId = $request->query('warehouse_id') ?: $warehouses->first()?->id;
        $selectedRows = $selectedId ? $byWarehouse->get((int) $selectedId, collect()) : collect();

        return view('admin.inventory.warehouse-dashboard.index', [
            'rows' => $rows,
            'warehouses' => $warehouses,
            'selectedId' => $selectedId,
            'selectedWarehouse' => $warehouses->firstWhere('id', (int) $selectedId),
            'selectedRows' => $selectedRows,
            'totalWarehouses' => $warehouses->count(),
            'totalValue' => $rows->sum('totalValue'),
            'employees' => Employee::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'branches' => Branch::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:2000'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'manager_employee_id' => ['nullable', 'exists:employees,id'],
            'operating_hours' => ['nullable', 'string', 'max:100'],
            'type' => ['required', 'in:'.implode(',', array_keys(Warehouse::TYPES))],
            'picking_strategy' => ['required', 'in:'.implode(',', array_keys(Warehouse::PICKING_STRATEGIES))],
        ]);

        Warehouse::create($data + ['company_id' => $companyId, 'is_active' => true]);

        return back()->with('status', 'Warehouse added.');
    }

    public function update(Request $request, Warehouse $warehouse): RedirectResponse
    {
        abort_unless($warehouse->company_id === Company::current()->id, 404);
        $data = $request->validate([
            'manager_employee_id' => ['nullable', 'exists:employees,id'],
            'operating_hours' => ['nullable', 'string', 'max:100'],
            'type' => ['required', 'in:'.implode(',', array_keys(Warehouse::TYPES))],
            'picking_strategy' => ['required', 'in:'.implode(',', array_keys(Warehouse::PICKING_STRATEGIES))],
        ]);
        $warehouse->update($data);

        return back()->with('status', 'Warehouse settings updated.');
    }

    public function toggle(Warehouse $warehouse): RedirectResponse
    {
        abort_unless($warehouse->company_id === Company::current()->id, 404);
        $warehouse->update(['is_active' => ! $warehouse->is_active]);

        return back()->with('status', $warehouse->is_active ? 'Warehouse activated.' : 'Warehouse deactivated.');
    }
}
