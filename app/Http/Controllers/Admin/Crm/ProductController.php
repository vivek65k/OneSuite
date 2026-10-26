<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    protected const SORTABLE = ['name', 'sku', 'unit_price'];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $sort = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $query = Product::where('company_id', Company::current()->id);

        if ($search !== '') {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
        }

        $products = $query->orderBy($sort, $direction)->paginate(25)->withQueryString();

        return view('admin.crm.products.index', [
            'products' => $products,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'total' => Product::where('company_id', Company::current()->id)->count(),
            'activeCount' => Product::where('company_id', Company::current()->id)->where('is_active', true)->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.crm.products.form', ['product' => new Product]);
    }

    public function store(Request $request): RedirectResponse
    {
        Product::create($this->validated($request) + ['company_id' => Company::current()->id]);

        return redirect()->route('admin.crm.products.index')->with('status', 'Product created.');
    }

    public function edit(Product $product): View
    {
        abort_unless($product->company_id === Company::current()->id, 404);

        return view('admin.crm.products.form', ['product' => $product]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->company_id === Company::current()->id, 404);

        $product->update($this->validated($request));

        return redirect()->route('admin.crm.products.index')->with('status', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        abort_unless($product->company_id === Company::current()->id, 404);
        $product->delete();

        return back()->with('status', 'Product removed.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active', true)];
    }
}
