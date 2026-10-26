<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\Brand;
use App\Models\Inventory\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.inventory.categories.index', [
            'categories' => Category::where('company_id', $companyId)->with('parent')->withCount('items')->orderBy('name')->get(),
            'parents' => Category::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'brands' => Brand::where('company_id', $companyId)->withCount('items')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:inv_categories,id'],
            'default_storage_type' => ['nullable', 'string', 'max:100'],
            'handling_rules' => ['nullable', 'string', 'max:1000'],
        ]);

        Category::create($data + ['company_id' => Company::current()->id]);

        return back()->with('status', 'Category added.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        abort_unless($category->company_id === Company::current()->id, 404);
        $category->delete();

        return back()->with('status', 'Category removed.');
    }

    public function storeBrand(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
        ]);

        Brand::create($data + ['company_id' => Company::current()->id]);

        return back()->with('status', 'Brand added.');
    }

    public function destroyBrand(Brand $brand): RedirectResponse
    {
        abort_unless($brand->company_id === Company::current()->id, 404);
        $brand->delete();

        return back()->with('status', 'Brand removed.');
    }
}
