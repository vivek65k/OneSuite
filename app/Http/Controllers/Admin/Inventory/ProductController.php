<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\Brand;
use App\Models\Inventory\Category;
use App\Models\Inventory\CustomFieldDefinition;
use App\Models\Inventory\CustomFieldValue;
use App\Models\Inventory\Item;
use App\Models\Inventory\Uom;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    protected const SORTABLE = ['name', 'sku', 'cost_price', 'sale_price', 'created_at'];

    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->query('category_id');
        $sort = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $query = Item::where('company_id', $companyId)->where('is_variant', false)->with(['category', 'brand']);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        if ($search !== '') {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
        }

        return view('admin.inventory.products.index', [
            'products' => $query->orderBy($sort, $direction)->paginate(25)->withQueryString(),
            'search' => $search,
            'categoryId' => $categoryId,
            'sort' => $sort,
            'direction' => $direction,
            'categories' => Category::where('company_id', $companyId)->orderBy('name')->get(),
            'total' => Item::where('company_id', $companyId)->where('is_variant', false)->count(),
            'tracked' => Item::where('company_id', $companyId)->where('track_stock', true)->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.inventory.products.form', $this->formData(new Item));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $item = Item::create($data + ['company_id' => Company::current()->id, 'is_active' => true]);
        $this->saveCustomFields($request, $item);

        return redirect()->route('admin.inventory.products.edit', $item)->with('status', 'Product created.');
    }

    public function edit(Item $product): View
    {
        abort_unless($product->company_id === Company::current()->id, 404);

        return view('admin.inventory.products.form', $this->formData($product));
    }

    public function update(Request $request, Item $product): RedirectResponse
    {
        abort_unless($product->company_id === Company::current()->id, 404);
        $product->update($this->validated($request));
        $this->saveCustomFields($request, $product);

        return redirect()->route('admin.inventory.products.edit', $product)->with('status', 'Product updated.');
    }

    /**
     * Values for admin-defined custom fields are stored generically in
     * inv_custom_field_values, keyed by the product's id, so new fields
     * added later don't need a products-table migration.
     */
    protected function saveCustomFields(Request $request, Item $item): void
    {
        $fields = CustomFieldDefinition::where('company_id', $item->company_id)->where('entity_type', 'item')->where('is_active', true)->get();

        foreach ($fields as $field) {
            CustomFieldValue::updateOrCreate(
                ['field_id' => $field->id, 'entity_type' => 'item', 'entity_id' => $item->id],
                ['company_id' => $item->company_id, 'value' => $request->input('custom_field.'.$field->field_key)]
            );
        }
    }

    public function destroy(Item $product): RedirectResponse
    {
        abort_unless($product->company_id === Company::current()->id, 404);
        $product->delete();

        return redirect()->route('admin.inventory.products.index')->with('status', 'Product deleted.');
    }

    protected function formData(Item $item): array
    {
        $companyId = Company::current()->id;

        $customFields = CustomFieldDefinition::where('company_id', $companyId)->where('entity_type', 'item')->where('is_active', true)->get();
        $customValues = $item->exists
            ? CustomFieldValue::where('entity_type', 'item')->where('entity_id', $item->id)->pluck('value', 'field_id')
            : collect();

        return [
            'product' => $item,
            'categories' => Category::where('company_id', $companyId)->orderBy('name')->get(),
            'brands' => Brand::where('company_id', $companyId)->orderBy('name')->get(),
            'uoms' => Uom::where('company_id', $companyId)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('company_id', $companyId)->orderBy('name')->get(),
            'customFields' => $customFields,
            'customValues' => $customValues,
        ];
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'sku' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['nullable', 'exists:inv_categories,id'],
            'brand_id' => ['nullable', 'exists:inv_brands,id'],
            'purchase_uom_id' => ['nullable', 'exists:inv_uoms,id'],
            'stock_uom_id' => ['nullable', 'exists:inv_uoms,id'],
            'sales_uom_id' => ['nullable', 'exists:inv_uoms,id'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'tax_category' => ['nullable', 'in:'.implode(',', array_keys(Item::TAX_CATEGORIES))],
            'track_stock' => ['nullable', 'boolean'],
            'track_batch' => ['nullable', 'boolean'],
            'track_serial' => ['nullable', 'boolean'],
            'track_expiry' => ['nullable', 'boolean'],
            'default_warehouse_id' => ['nullable', 'exists:inv_warehouses,id'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
        ]) + [
            'track_stock' => $request->boolean('track_stock'),
            'track_batch' => $request->boolean('track_batch'),
            'track_serial' => $request->boolean('track_serial'),
            'track_expiry' => $request->boolean('track_expiry'),
        ];
    }
}
