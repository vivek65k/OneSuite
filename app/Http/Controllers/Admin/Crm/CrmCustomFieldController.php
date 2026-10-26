<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\CustomField;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrmCustomFieldController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.crm.custom-fields.index', [
            'fields' => CustomField::where('company_id', $companyId)->orderBy('entity')->orderBy('field_label')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'entity' => ['required', 'in:'.implode(',', array_keys(CustomField::ENTITIES))],
            'field_name' => ['required', 'string', 'max:100', 'alpha_dash'],
            'field_label' => ['required', 'string', 'max:255'],
            'field_type' => ['required', 'in:'.implode(',', array_keys(CustomField::FIELD_TYPES))],
            'options' => ['nullable', 'string'],
            'is_required' => ['nullable', 'boolean'],
        ]);

        $data['options'] = ! empty($data['options']) ? array_map('trim', explode(',', $data['options'])) : null;
        $data['is_required'] = $request->boolean('is_required');

        CustomField::create($data + ['company_id' => Company::current()->id, 'is_active' => true]);

        return back()->with('status', 'Custom field added.');
    }

    public function toggle(CustomField $customField): RedirectResponse
    {
        abort_unless($customField->company_id === Company::current()->id, 404);
        $customField->update(['is_active' => ! $customField->is_active]);

        return back()->with('status', $customField->is_active ? 'Field activated.' : 'Field deactivated.');
    }

    public function destroy(CustomField $customField): RedirectResponse
    {
        abort_unless($customField->company_id === Company::current()->id, 404);
        $customField->delete();

        return back()->with('status', 'Custom field removed.');
    }
}
