<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadRecycleController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));

        $eligible = Lead::where('company_id', $companyId)->where('is_recycled', false)
            ->where('status', 'unqualified')->with(['source', 'assignedTo'])->orderByDesc('updated_at')->get();

        $recycledQuery = Lead::where('company_id', $companyId)->where('is_recycled', true)->with(['source']);
        if ($search !== '') {
            $recycledQuery->where('name', 'like', "%{$search}%");
        }
        $recycled = $recycledQuery->orderByDesc('recycled_at')->paginate(20)->withQueryString();

        return view('admin.crm.lead-recycle.index', [
            'eligible' => $eligible,
            'recycled' => $recycled,
            'search' => $search,
        ]);
    }

    public function recycle(Request $request, Lead $lead): RedirectResponse
    {
        abort_unless($lead->company_id === Company::current()->id, 404);

        $data = $request->validate(['recycle_reason' => ['nullable', 'string', 'max:500']]);

        $lead->update($data + [
            'is_recycled' => true,
            'recycled_at' => now(),
            'status' => 'new',
            'assigned_to_employee_id' => null,
        ]);

        return back()->with('status', 'Lead moved to the recycle pool for future follow-up.');
    }

    public function reactivate(Lead $lead): RedirectResponse
    {
        abort_unless($lead->company_id === Company::current()->id, 404);

        $lead->update(['is_recycled' => false, 'status' => 'new']);

        return back()->with('status', 'Lead reactivated and returned to the active pipeline.');
    }
}
