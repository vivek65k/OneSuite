<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\EmployeeDocument;
use App\Models\Resignation;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SeparationDocumentController extends Controller
{
    public function experienceLetter(): View
    {
        return $this->render('experience_letter');
    }

    public function relievingLetter(): View
    {
        return $this->render('relieving_letter');
    }

    protected function render(string $type): View
    {
        $this->authorize('viewAny', EmployeeDocument::class);

        $companyId = Company::current()->id;

        $resignations = Resignation::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['accepted', 'completed'])
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($eq) => $eq->where('branch_id', BranchContext::currentBranchId())))
            ->with(['employee', 'employee.documents' => fn ($q) => $q->where('category', $type)])
            ->orderByDesc('last_working_day')
            ->get();

        return view('admin.hrm.separation.documents', [
            'type' => $type,
            'typeLabel' => EmployeeDocument::TYPES[$type],
            'resignations' => $resignations,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', EmployeeDocument::class);

        $companyId = Company::current()->id;

        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $companyId)->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))],
            'category' => ['required', Rule::in(['experience_letter', 'relieving_letter'])],
            'name' => ['required', 'string', 'max:255'],
            'file' => EmployeeDocument::fileRules(),
        ], EmployeeDocument::fileMessages());

        $data['company_id'] = $companyId;
        // Private storage: the file is only reachable through the checked download link.
        $data['file_path'] = EmployeeDocument::storeUpload($request->file('file'));
        unset($data['file']);

        EmployeeDocument::create($data);

        return back()->with('status', 'Document issued.');
    }

    public function destroy(EmployeeDocument $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        $document->delete();

        return back()->with('status', 'Document removed.');
    }
}
