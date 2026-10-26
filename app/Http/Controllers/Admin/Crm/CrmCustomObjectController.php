<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\CustomObject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrmCustomObjectController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.crm.custom-objects.index', [
            'objects' => CustomObject::where('company_id', $companyId)->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.crm.custom-objects.form', ['object' => new CustomObject(['fields_json' => []])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        CustomObject::create($data + ['company_id' => Company::current()->id, 'is_active' => true]);

        return redirect()->route('admin.crm.custom-objects.index')->with('status', 'Custom object created.');
    }

    public function edit(CustomObject $customObject): View
    {
        abort_unless($customObject->company_id === Company::current()->id, 404);

        return view('admin.crm.custom-objects.form', ['object' => $customObject]);
    }

    public function update(Request $request, CustomObject $customObject): RedirectResponse
    {
        abort_unless($customObject->company_id === Company::current()->id, 404);
        $customObject->update($this->validated($request));

        return redirect()->route('admin.crm.custom-objects.index')->with('status', 'Custom object updated.');
    }

    public function toggle(CustomObject $customObject): RedirectResponse
    {
        abort_unless($customObject->company_id === Company::current()->id, 404);
        $customObject->update(['is_active' => ! $customObject->is_active]);

        return back()->with('status', $customObject->is_active ? 'Object activated.' : 'Object deactivated.');
    }

    public function destroy(CustomObject $customObject): RedirectResponse
    {
        abort_unless($customObject->company_id === Company::current()->id, 404);
        $customObject->delete();

        return redirect()->route('admin.crm.custom-objects.index')->with('status', 'Custom object deleted.');
    }

    protected function validated(Request $request): array
    {
        if ($request->filled('fields_json') && is_string($request->input('fields_json'))) {
            $request->merge(['fields_json' => json_decode($request->input('fields_json'), true) ?? []]);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:100', 'alpha_dash'],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'fields_json' => ['nullable', 'array'],
        ]) + ['fields_json' => $request->input('fields_json', [])];
    }
}
