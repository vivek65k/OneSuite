<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\CaseModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceRequestController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));
        $statusFilter = (string) $request->query('status', '');

        $query = CaseModel::where('company_id', $companyId)->where('type', 'service_request')->with(['account', 'contact', 'assignedTo']);

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }
        if ($search !== '') {
            $query->where('subject', 'like', "%{$search}%");
        }

        $requests = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $all = CaseModel::where('company_id', $companyId)->where('type', 'service_request')->get();

        return view('admin.crm.service-requests.index', [
            'requests' => $requests,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'statuses' => CaseModel::STATUSES,
            'openCount' => $all->whereNotIn('status', ['resolved', 'closed'])->count(),
        ]);
    }
}
