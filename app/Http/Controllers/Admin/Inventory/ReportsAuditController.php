<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportsAuditController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;

        return view('admin.inventory.reports-audit.index', [
            'logs' => AuditLog::where('company_id', $companyId)->with('employee')
                ->when($request->filled('entity_type'), fn ($q) => $q->where('entity_type', $request->input('entity_type')))
                ->orderByDesc('created_at')->paginate(30)->withQueryString(),
            'entityTypes' => AuditLog::where('company_id', $companyId)->distinct()->orderBy('entity_type')->pluck('entity_type'),
        ]);
    }
}
