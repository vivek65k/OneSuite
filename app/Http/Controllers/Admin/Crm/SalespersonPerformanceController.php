<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Activity;
use App\Models\Crm\Opportunity;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalespersonPerformanceController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $range = (string) $request->query('range', '30');
        $since = now()->subDays((int) $range);

        $employees = Employee::where('company_id', $companyId)->get();
        $opportunities = Opportunity::where('company_id', $companyId)->where('created_at', '>=', $since)->get();
        $activities = Activity::where('company_id', $companyId)->where('created_at', '>=', $since)->get();

        $rows = $employees->map(function ($employee) use ($opportunities, $activities) {
            $owned = $opportunities->where('owner_employee_id', $employee->id);
            $won = $owned->where('status', 'won');
            $lost = $owned->where('status', 'lost');

            return [
                'employee' => $employee,
                'open_count' => $owned->where('status', 'open')->count(),
                'won_count' => $won->count(),
                'won_amount' => (float) $won->sum('amount'),
                'win_rate' => ($won->count() + $lost->count()) > 0 ? round($won->count() / ($won->count() + $lost->count()) * 100, 1) : 0,
                'activities_count' => $activities->where('owner_employee_id', $employee->id)->count(),
            ];
        })->filter(fn ($row) => $row['open_count'] > 0 || $row['won_count'] > 0 || $row['activities_count'] > 0)
            ->sortByDesc('won_amount')->values();

        return view('admin.crm.salesperson-performance.index', [
            'range' => $range,
            'rows' => $rows,
            'totalWonAmount' => $rows->sum('won_amount'),
            'totalWonCount' => $rows->sum('won_count'),
        ]);
    }
}
