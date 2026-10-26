<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\PriceBook;
use App\Models\Crm\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PriceBookController extends Controller
{
    public function index(): View
    {
        $priceBooks = PriceBook::where('company_id', Company::current()->id)->withCount('products')->orderBy('name')->get();

        return view('admin.crm.price-books.index', [
            'priceBooks' => $priceBooks,
            'total' => $priceBooks->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.crm.price-books.form', ['priceBook' => new PriceBook]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $this->validated($request);

        if ($data['is_default']) {
            PriceBook::where('company_id', $companyId)->update(['is_default' => false]);
        }

        PriceBook::create($data + ['company_id' => $companyId]);

        return redirect()->route('admin.crm.price-books.index')->with('status', 'Price book created.');
    }

    public function edit(PriceBook $priceBook): View
    {
        abort_unless($priceBook->company_id === Company::current()->id, 404);

        return view('admin.crm.price-books.show', [
            'priceBook' => $priceBook,
            'products' => Product::where('company_id', $priceBook->company_id)->where('is_active', true)->orderBy('name')->get(),
            'entries' => $priceBook->products()->get()->keyBy('id'),
        ]);
    }

    public function update(Request $request, PriceBook $priceBook): RedirectResponse
    {
        abort_unless($priceBook->company_id === Company::current()->id, 404);

        $data = $this->validated($request);

        if ($data['is_default']) {
            PriceBook::where('company_id', $priceBook->company_id)->where('id', '!=', $priceBook->id)->update(['is_default' => false]);
        }

        $priceBook->update($data);

        return back()->with('status', 'Price book updated.');
    }

    public function destroy(PriceBook $priceBook): RedirectResponse
    {
        abort_unless($priceBook->company_id === Company::current()->id, 404);
        $priceBook->delete();

        return redirect()->route('admin.crm.price-books.index')->with('status', 'Price book removed.');
    }

    public function setPrice(Request $request, PriceBook $priceBook): RedirectResponse
    {
        abort_unless($priceBook->company_id === Company::current()->id, 404);

        $data = $request->validate([
            'product_id' => ['required', 'exists:crm_products,id'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        abort_unless(Product::where('id', $data['product_id'])->where('company_id', $priceBook->company_id)->exists(), 404);

        $priceBook->products()->syncWithoutDetaching([$data['product_id'] => ['price' => $data['price']]]);

        return back()->with('status', 'Price set.');
    }

    public function removePrice(PriceBook $priceBook, Product $product): RedirectResponse
    {
        abort_unless($priceBook->company_id === Company::current()->id, 404);
        $priceBook->products()->detach($product->id);

        return back()->with('status', 'Price override removed - this product now uses its catalog price.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', 'max:10'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_default' => $request->boolean('is_default'), 'is_active' => $request->boolean('is_active', true)];
    }
}
