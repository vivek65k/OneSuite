<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\DocumentTemplate;
use App\Models\EmployeeDocument;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DocumentTemplateController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', DocumentTemplate::class);

        $templates = DocumentTemplate::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->orderBy('name')
            ->get();

        return view('admin.hrm.settings.document-templates.index', ['templates' => $templates]);
    }

    public function create(): View
    {
        $this->authorize('create', DocumentTemplate::class);

        return view('admin.hrm.settings.document-templates.create', ['template' => new DocumentTemplate()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', DocumentTemplate::class);

        $data = $this->validated($request);
        $data['company_id'] = Company::current()->id;
        $data['branch_id'] = BranchContext::currentBranchId();

        DocumentTemplate::create($data);

        return redirect()->route('admin.document-templates.index')->with('status', 'Template added.');
    }

    public function edit(DocumentTemplate $documentTemplate): View
    {
        $this->authorize('update', $documentTemplate);

        return view('admin.hrm.settings.document-templates.edit', ['template' => $documentTemplate]);
    }

    public function update(Request $request, DocumentTemplate $documentTemplate): RedirectResponse
    {
        $this->authorize('update', $documentTemplate);

        $documentTemplate->update($this->validated($request));

        return redirect()->route('admin.document-templates.index')->with('status', 'Template updated.');
    }

    public function destroy(DocumentTemplate $documentTemplate): RedirectResponse
    {
        $this->authorize('delete', $documentTemplate);

        $documentTemplate->delete();

        return redirect()->route('admin.document-templates.index')->with('status', 'Template removed.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(array_keys(EmployeeDocument::TYPES))],
            'body' => ['required', 'string', 'max:20000'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
