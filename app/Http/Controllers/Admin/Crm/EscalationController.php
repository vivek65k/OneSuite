<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\CaseModel;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EscalationController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $view = (string) $request->query('view', 'escalated');

        $query = CaseModel::where('company_id', $companyId)->with(['account', 'assignedTo', 'escalatedTo']);

        if ($view === 'overdue') {
            $query->whereNotIn('status', ['resolved', 'closed'])->whereNotNull('resolution_due_at')->where('resolution_due_at', '<', now());
        } else {
            $query->where('is_escalated', true);
        }

        $cases = $query->orderByDesc('escalated_at')->paginate(20)->withQueryString();

        return view('admin.crm.escalations.index', [
            'cases' => $cases,
            'view' => $view,
            'employees' => Employee::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'escalatedCount' => CaseModel::where('company_id', $companyId)->where('is_escalated', true)->count(),
            'overdueCount' => CaseModel::where('company_id', $companyId)->whereNotIn('status', ['resolved', 'closed'])->whereNotNull('resolution_due_at')->where('resolution_due_at', '<', now())->count(),
        ]);
    }
}
