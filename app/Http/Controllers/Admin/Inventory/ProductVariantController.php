<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\Item;
use App\Models\Inventory\VariantAttribute;
use App\Models\Inventory\VariantValue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductVariantController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.inventory.variants.index', [
            'products' => Item::where('company_id', $companyId)->where('is_variant', false)->withCount('variants')->with('variants.variantValues')->orderBy('name')->get(),
            'attributes' => VariantAttribute::where('company_id', $companyId)->with('values')->orderBy('name')->get(),
        ]);
    }

    public function storeAttribute(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);
        VariantAttribute::create($data + ['company_id' => Company::current()->id]);

        return back()->with('status', 'Attribute added.');
    }

    public function storeValue(Request $request, VariantAttribute $variantAttribute): RedirectResponse
    {
        abort_unless($variantAttribute->company_id === Company::current()->id, 404);
        $data = $request->validate(['value' => ['required', 'string', 'max:100']]);

        VariantValue::create(['attribute_id' => $variantAttribute->id, 'value' => $data['value']]);

        return back()->with('status', 'Value added.');
    }

    public function generate(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;

        $data = $request->validate([
            'item_id' => ['required', 'exists:inv_items,id'],
            'value_ids' => ['required', 'array', 'min:1'],
            'value_ids.*' => ['array', 'min:1'],
            'value_ids.*.*' => ['exists:inv_variant_values,id'],
        ]);

        $parent = Item::where('company_id', $companyId)->findOrFail($data['item_id']);

        $groups = collect($data['value_ids'])->map(fn ($ids) => VariantValue::whereIn('id', $ids)->get());

        $combinations = $groups->reduce(function ($carry, $values) {
            if ($carry === null) {
                return $values->map(fn ($v) => collect([$v]));
            }

            return $carry->flatMap(fn ($combo) => $values->map(fn ($v) => $combo->concat([$v])));
        }, null) ?? collect();

        $created = 0;
        foreach ($combinations as $combo) {
            $label = $combo->pluck('value')->implode('-');
            $sku = ($parent->sku ?: Str::slug($parent->name)).'-'.Str::slug($label);

            if (Item::where('company_id', $companyId)->where('sku', $sku)->exists()) {
                continue;
            }

            $variant = Item::create([
                'company_id' => $companyId,
                'sku' => $sku,
                'name' => $parent->name.' ('.$combo->pluck('value')->implode(' / ').')',
                'category_id' => $parent->category_id,
                'brand_id' => $parent->brand_id,
                'unit' => $parent->unit,
                'cost_price' => $parent->cost_price,
                'sale_price' => $parent->sale_price,
                'reorder_level' => $parent->reorder_level,
                'parent_item_id' => $parent->id,
                'is_variant' => true,
                'is_active' => true,
            ]);

            $variant->variantValues()->sync($combo->pluck('id'));
            $created++;
        }

        return back()->with('status', "Generated {$created} variant(s).");
    }

    public function destroyVariant(Item $variant): RedirectResponse
    {
        abort_unless($variant->company_id === Company::current()->id && $variant->is_variant, 404);
        $variant->delete();

        return back()->with('status', 'Variant removed.');
    }
}
