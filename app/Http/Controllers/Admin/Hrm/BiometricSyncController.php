<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BiometricSyncController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', AttendanceRecord::class);

        $biometric = \App\Support\Attendance::records()->where('source', 'biometric');

        return view('admin.hrm.attendance.biometric-sync', [
            'stats' => [
                'total' => (clone $biometric)->count(),
                'last_30' => (clone $biometric)->whereDate('date', '>=', now()->subDays(30)->toDateString())->count(),
                'last_sync' => (clone $biometric)->max('updated_at'),
                'employees_with_codes' => \App\Support\Attendance::employees()->whereNotNull('employee_code')->where('employee_code', '!=', '')->count(),
            ],
            'sampleCodes' => \App\Support\Attendance::employees()->whereNotNull('employee_code')->orderBy('employee_code')->limit(2)->pluck('employee_code'),
            'recentDays' => (clone $biometric)
                ->toBase()
                ->selectRaw('DATE(date) as day, COUNT(*) as aggregate')
                ->groupByRaw('DATE(date)')
                ->orderByDesc('day')
                ->limit(7)
                ->get(),
        ]);
    }

    /**
     * Import a CSV exported from a biometric device: employee_code, date
     * (YYYY-MM-DD), check_in_time, check_out_time (HH:MM, either optional).
     * Existing records for that employee/date are updated, not duplicated.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', AttendanceRecord::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $companyId = Company::current()->id;
        $employeesByCode = Employee::query()
            ->where('company_id', $companyId)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->pluck('id', 'employee_code');

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $line = array_combine($header, $row) ?: [];
            $code = trim($line['employee_code'] ?? '');
            $date = trim($line['date'] ?? '');

            if (! $code || ! $date || ! isset($employeesByCode[$code])) {
                $skipped++;

                continue;
            }

            AttendanceRecord::upsertForDate(
                $employeesByCode[$code],
                $companyId,
                $date,
                [
                    'check_in_time' => trim($line['check_in_time'] ?? '') ?: null,
                    'check_out_time' => trim($line['check_out_time'] ?? '') ?: null,
                    'status' => 'present',
                    'source' => 'biometric',
                ]
            );
            $imported++;
        }

        fclose($handle);

        return redirect()->route('admin.attendance.biometric-sync')
            ->with('status', "Imported {$imported} record(s).".($skipped ? " Skipped {$skipped} row(s) with an unrecognized employee code or missing date." : ''));
    }
}
