<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\HrPolicy;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HrPolicyController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', HrPolicy::class);

        $policies = HrPolicy::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->orderBy('title')
            ->get();

        return view('admin.hrm.settings.hr-policies.index', ['policies' => $policies]);
    }

    public function create(): View
    {
        $this->authorize('create', HrPolicy::class);

        return view('admin.hrm.settings.hr-policies.create', ['policy' => new HrPolicy()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', HrPolicy::class);

        $data = $this->validated($request);
        $data['company_id'] = Company::current()->id;
        $data['branch_id'] = BranchContext::currentBranchId();

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('hr-policies', 'public');
        }

        HrPolicy::create($data);

        return redirect()->route('admin.hr-policies.index')->with('status', 'Policy added.');
    }

    public function edit(HrPolicy $hrPolicy): View
    {
        $this->authorize('update', $hrPolicy);

        return view('admin.hrm.settings.hr-policies.edit', ['policy' => $hrPolicy]);
    }

    public function update(Request $request, HrPolicy $hrPolicy): RedirectResponse
    {
        $this->authorize('update', $hrPolicy);

        $data = $this->validated($request);

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('hr-policies', 'public');
        }

        $hrPolicy->update($data);

        return redirect()->route('admin.hr-policies.index')->with('status', 'Policy updated.');
    }

    public function destroy(HrPolicy $hrPolicy): RedirectResponse
    {
        $this->authorize('delete', $hrPolicy);

        $hrPolicy->delete();

        return redirect()->route('admin.hr-policies.index')->with('status', 'Policy removed.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'effective_date' => ['nullable', 'date'],
            'file' => ['nullable', 'file', 'max:10240'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        unset($data['file']);

        return $data;
    }
}
