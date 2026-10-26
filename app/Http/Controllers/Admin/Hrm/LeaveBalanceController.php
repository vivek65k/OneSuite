<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LeaveBalanceController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', LeaveBalance::class);

        $year = $this->validYear($request->query('year'));
        $state = $request->only(['search', 'department_id', 'view']);

        $employees = $this->employeesQuery()
            ->when($state['department_id'] ?? null, fn ($q, $departmentId) => $q->where('department_id', $departmentId))
            ->when($state['search'] ?? null, fn ($q, $search) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")->orWhere('employee_code', 'like', "%{$search}%");
            }))
            ->with(['department', 'leaveBalances' => fn ($q) => $q->where('year', $year)])
            ->orderBy('name')
            ->get();

        $types = $this->typesQuery()->where('is_active', true)->orderBy('name')->get();

        // Columns are leave-type NAMES: types are defined per branch, so each
        // employee's cell resolves to their own branch's type of that name.
        $columns = $types->groupBy(fn (LeaveType $t) => mb_strtolower(trim($t->name)))
            ->map(fn (Collection $group) => [
                'name' => $group->first()->name,
                'code' => $group->first()->code,
                'byBranch' => $group->keyBy(fn ($t) => (string) ($t->branch_id ?? '*')),
            ])
            ->values();

        $cells = [];
        $totals = ['allocated' => 0.0, 'used' => 0.0, 'remaining' => 0.0, 'missing' => 0, 'low' => 0];
        foreach ($employees as $employee) {
            foreach ($columns as $index => $column) {
                $type = $column['byBranch'][(string) $employee->branch_id] ?? $column['byBranch']['*'] ?? null;
                if (! $type) {
                    continue; // not offered in this employee's branch
                }
                $balance = $employee->leaveBalances->firstWhere('leave_type_id', $type->id);
                $cells[$employee->id][$index] = ['type' => $type, 'balance' => $balance];

                if ($balance) {
                    $entitled = (float) $balance->allocated + (float) $balance->carried_forward + (float) $balance->adjusted;
                    $totals['allocated'] += $entitled;
                    $totals['used'] += (float) $balance->used;
                    $totals['remaining'] += (float) $balance->remaining;
                    if ($entitled > 0 && (float) $balance->remaining <= 2) {
                        $totals['low']++;
                    }
                } else {
                    $totals['missing']++;
                }
            }
        }

        return view('admin.hrm.leave.balances', [
            'year' => $year,
            'employees' => $employees,
            'columns' => $columns,
            'cells' => $cells,
            'totals' => $totals,
            'state' => $state,
            'departments' => Department::query()
                ->where('company_id', Company::current()->id)
                ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
                ->orderBy('name')
                ->pluck('name', 'id'),
            'canEdit' => auth()->user()->can('update_leave_balance'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;

        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $companyId)->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))],
            'leave_type_id' => ['required', Rule::exists('leave_types', 'id')->where('company_id', $companyId)],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'allocated' => ['required', 'numeric', 'min:0', 'max:365'],
            'carried_forward' => ['required', 'numeric', 'min:0', 'max:365'],
            'adjusted' => ['required', 'numeric', 'min:-365', 'max:365'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        $type = LeaveType::findOrFail($data['leave_type_id']);

        // Types are per branch - an employee only holds balances for their own branch's types.
        if ($type->branch_id && (int) $type->branch_id !== (int) $employee->branch_id) {
            throw ValidationException::withMessages(['leave_type_id' => "{$type->name} isn't offered in {$employee->name}'s branch."]);
        }

        $balance = LeaveBalance::firstOrNew([
            'employee_id' => $data['employee_id'],
            'leave_type_id' => $data['leave_type_id'],
            'year' => $data['year'],
        ]);

        // Editing balances needs the update permission, not just view.
        $this->authorize('update', $balance);

        $balance->company_id = $companyId;
        $balance->allocated = $data['allocated'];
        $balance->carried_forward = $data['carried_forward'];
        $balance->adjusted = $data['adjusted'];
        $balance->save();

        return back(fallback: route('admin.leave-balances.index', ['year' => $data['year']]))
            ->with('status', "{$employee->name}'s {$type->name} balance for {$data['year']} saved - ".rtrim(rtrim(number_format((float) $balance->remaining, 2), '0'), '.').' days remaining.');
    }

    /**
     * Yearly allocation: create the missing balance rows for $year - each
     * active type's days_per_year, plus carry-forward of last year's unused
     * days (capped by the type's max) for types that allow it. Existing rows
     * are never overwritten, so it's safe to run repeatedly.
     */
    public function allocate(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('update_leave_balance'), 403);

        $year = (int) $request->validate(['year' => ['required', 'integer', 'min:2000', 'max:2100']])['year'];
        $companyId = Company::current()->id;

        $employees = $this->employeesQuery()->get(['id', 'branch_id']);
        $types = $this->typesQuery()->where('is_active', true)->get();

        $existing = LeaveBalance::query()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereIn('year', [$year, $year - 1])
            ->get()
            ->groupBy('year');
        $current = ($existing[$year] ?? collect())->keyBy(fn ($b) => $b->employee_id.'-'.$b->leave_type_id);
        $previous = ($existing[$year - 1] ?? collect())->keyBy(fn ($b) => $b->employee_id.'-'.$b->leave_type_id);

        $created = 0;
        $carried = 0.0;

        DB::transaction(function () use ($employees, $types, $current, $previous, $year, $companyId, &$created, &$carried) {
            foreach ($employees as $employee) {
                foreach ($types as $type) {
                    if ($type->branch_id && (int) $type->branch_id !== (int) $employee->branch_id) {
                        continue;
                    }
                    $key = $employee->id.'-'.$type->id;
                    if ($current->has($key)) {
                        continue;
                    }

                    $carry = 0.0;
                    if ($type->carry_forward && ($last = $previous->get($key))) {
                        $carry = max(0.0, (float) $last->remaining);
                        if ($type->max_carry_forward_days !== null) {
                            $carry = min($carry, (float) $type->max_carry_forward_days);
                        }
                    }

                    LeaveBalance::create([
                        'company_id' => $companyId,
                        'employee_id' => $employee->id,
                        'leave_type_id' => $type->id,
                        'year' => $year,
                        'allocated' => $type->days_per_year,
                        'carried_forward' => $carry,
                    ]);
                    $created++;
                    $carried += $carry;
                }
            }
        });

        $message = $created
            ? "Allocated {$created} ".($created === 1 ? 'balance' : 'balances')." for {$year}".($carried > 0 ? ', carrying forward '.rtrim(rtrim(number_format($carried, 2), '0'), '.').' days from '.($year - 1) : '').'.'
            : "Everyone already has a {$year} balance for every leave type - nothing to allocate.";

        return redirect()->route('admin.leave-balances.index', ['year' => $year])->with('status', $message);
    }

    protected function employeesQuery()
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->whereNotIn('status', ['terminated', 'inactive']);
    }

    protected function typesQuery()
    {
        return LeaveType::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }

    protected function validYear(?string $value): int
    {
        $year = (int) $value;

        return $year >= 2000 && $year <= 2100 ? $year : (int) now()->year;
    }
}
