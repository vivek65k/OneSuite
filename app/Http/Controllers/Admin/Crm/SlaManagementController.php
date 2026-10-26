<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\CaseModel;
use App\Models\Crm\SlaPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SlaManagementController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        $policies = SlaPolicy::where('company_id', $companyId)->withCount('cases')->orderBy('name')->get();

        $cases = CaseModel::where('company_id', $companyId)->whereNotIn('status', ['resolved', 'closed'])
            ->whereNotNull('resolution_due_at')->with(['account', 'assignedTo'])->orderBy('resolution_due_at')->get();

        return view('admin.crm.sla-management.index', [
            'policies' => $policies,
            'breached' => $cases->filter(fn ($c) => $c->is_overdue)->values(),
            'dueSoon' => $cases->filter(fn ($c) => ! $c->is_overdue && $c->resolution_due_at->diffInHours(now()) <= 24)->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        SlaPolicy::create($data + ['company_id' => Company::current()->id]);

        return back()->with('status', 'SLA policy created.');
    }

    public function update(Request $request, SlaPolicy $slaPolicy): RedirectResponse
    {
        abort_unless($slaPolicy->company_id === Company::current()->id, 404);
        $slaPolicy->update($this->validated($request));

        return back()->with('status', 'SLA policy updated.');
    }

    public function destroy(SlaPolicy $slaPolicy): RedirectResponse
    {
        abort_unless($slaPolicy->company_id === Company::current()->id, 404);
        $slaPolicy->delete();

        return back()->with('status', 'SLA policy removed.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'in:'.implode(',', array_keys(\App\Models\Crm\CaseModel::PRIORITIES))],
            'response_hours' => ['required', 'integer', 'min:1'],
            'resolution_hours' => ['required', 'integer', 'min:1'],
        ]);
    }
}
