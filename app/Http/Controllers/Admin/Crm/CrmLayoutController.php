<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\CrmLayout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrmLayoutController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $entity = $request->query('entity', 'lead');

        $layout = CrmLayout::where('company_id', $companyId)->where('entity', $entity)->first();
        $fields = $layout?->fields_json ?? array_map(
            fn ($f) => ['field' => $f, 'visible' => true],
            CrmLayout::DEFAULT_FIELDS[$entity] ?? []
        );

        return view('admin.crm.layouts.index', [
            'entity' => $entity,
            'entities' => CrmLayout::ENTITIES,
            'fields' => $fields,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'entity' => ['required', 'in:'.implode(',', array_keys(CrmLayout::ENTITIES))],
            'fields_json' => ['required', 'array'],
        ]);

        CrmLayout::updateOrCreate(
            ['company_id' => Company::current()->id, 'entity' => $data['entity']],
            ['fields_json' => $data['fields_json']]
        );

        return back()->with('status', 'Layout saved.');
    }
}
