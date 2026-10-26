<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\AuditLog;
use App\Models\Inventory\CustomFieldDefinition;
use App\Models\Inventory\Item;
use App\Models\Inventory\NumberingSeries;
use App\Models\Inventory\Stock;
use App\Models\Inventory\StockLedger;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;

class NumberingDocumentsController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.inventory.numbering-documents.index', [
            'series' => NumberingSeries::where('company_id', $companyId)->orderBy('document_type')->get(),
            'documentTypes' => NumberingSeries::DOCUMENT_TYPES,
            'fields' => CustomFieldDefinition::where('company_id', $companyId)->where('entity_type', 'item')->orderBy('created_at')->get(),
            'fieldTypes' => CustomFieldDefinition::FIELD_TYPES,
        ]);
    }

    public function storeSeries(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'document_type' => ['required', 'in:'.implode(',', array_keys(NumberingSeries::DOCUMENT_TYPES))],
            'prefix' => ['required', 'string', 'max:20'],
            'next_number' => ['required', 'integer', 'min:1'],
            'padding_digits' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $series = NumberingSeries::updateOrCreate(
            ['company_id' => $companyId, 'document_type' => $data['document_type']],
            $data + ['is_active' => true]
        );

        AuditLog::record($companyId, Auth::user()?->employee?->id, 'updated', 'NumberingSeries', $series->id, "Numbering series for {$data['document_type']} set to prefix {$data['prefix']}.");

        return redirect()->route('admin.inventory.numbering-documents.index')->with('status', 'Numbering series saved.');
    }

    public function destroySeries(NumberingSeries $numberingSeries): RedirectResponse
    {
        abort_unless($numberingSeries->company_id === Company::current()->id, 404);
        $numberingSeries->delete();

        return back()->with('status', 'Numbering series removed - that document type falls back to its default format.');
    }

    public function storeField(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'field_key' => ['required', 'alpha_dash', 'max:60'],
            'label' => ['required', 'string', 'max:150'],
            'field_type' => ['required', 'in:'.implode(',', array_keys(CustomFieldDefinition::FIELD_TYPES))],
            'options' => ['nullable', 'string', 'max:500'],
            'is_required' => ['nullable', 'boolean'],
        ]);

        $field = CustomFieldDefinition::create($data + [
            'company_id' => $companyId, 'entity_type' => 'item', 'is_required' => $request->boolean('is_required'), 'is_active' => true,
        ]);

        AuditLog::record($companyId, Auth::user()?->employee?->id, 'created', 'CustomFieldDefinition', $field->id, "Custom field '{$field->label}' added for products.");

        return redirect()->route('admin.inventory.numbering-documents.index')->with('status', 'Custom field added - it now appears on the product form.');
    }

    public function destroyField(CustomFieldDefinition $customFieldDefinition): RedirectResponse
    {
        abort_unless($customFieldDefinition->company_id === Company::current()->id, 404);
        $customFieldDefinition->delete();

        return back()->with('status', 'Custom field removed.');
    }

    public function importItems(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt,xlsx']]);

        $rows = $this->readSpreadsheet($request->file('file')->getRealPath());
        $imported = 0;

        foreach ($rows as $row) {
            if (empty($row['sku']) || empty($row['name'])) {
                continue;
            }

            Item::updateOrCreate(
                ['company_id' => $companyId, 'sku' => $row['sku']],
                [
                    'name' => $row['name'], 'unit' => $row['unit'] ?? 'pcs',
                    'cost_price' => $row['cost_price'] ?? 0, 'sale_price' => $row['sale_price'] ?? 0, 'is_active' => true,
                ]
            );
            $imported++;
        }

        AuditLog::record($companyId, Auth::user()?->employee?->id, 'created', 'Item', null, "Bulk import: {$imported} product(s) created/updated from file.");

        return back()->with('status', "{$imported} product(s) imported.");
    }

    public function importOpeningBalances(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt,xlsx']]);

        $rows = $this->readSpreadsheet($request->file('file')->getRealPath());
        $imported = 0;

        foreach ($rows as $row) {
            if (empty($row['sku']) || empty($row['warehouse_code']) || ! isset($row['quantity'])) {
                continue;
            }

            $item = Item::where('company_id', $companyId)->where('sku', $row['sku'])->first();
            $warehouse = Warehouse::where('company_id', $companyId)->where('code', $row['warehouse_code'])->first();
            if (! $item || ! $warehouse) {
                continue;
            }

            $stock = Stock::firstOrCreate(['warehouse_id' => $warehouse->id, 'item_id' => $item->id], ['company_id' => $companyId, 'quantity' => 0]);
            $before = (float) $stock->quantity;
            $qty = (float) $row['quantity'];
            $stock->update(['quantity' => $qty]);

            StockLedger::record([
                'company_id' => $companyId, 'warehouse_id' => $warehouse->id, 'item_id' => $item->id,
                'transaction_type' => 'opening', 'quantity_before' => $before, 'quantity_change' => $qty - $before, 'quantity_after' => $qty,
                'source_type' => 'OpeningBalanceImport', 'source_reference' => 'Bulk import', 'employee_id' => Auth::user()?->employee?->id,
            ]);
            $imported++;
        }

        AuditLog::record($companyId, Auth::user()?->employee?->id, 'created', 'Stock', null, "Opening balances imported for {$imported} item/warehouse line(s).");

        return back()->with('status', "Opening balances set for {$imported} line(s).");
    }

    protected function readSpreadsheet(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        if (count($sheet) < 2) {
            return [];
        }

        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), array_shift($sheet));
        $headers = array_values($headers);

        $rows = [];
        foreach ($sheet as $line) {
            $line = array_values($line);
            $row = [];
            foreach ($headers as $i => $header) {
                $row[$header] = $line[$i] ?? null;
            }
            $rows[] = $row;
        }

        return $rows;
    }
}
