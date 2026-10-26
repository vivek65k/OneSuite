<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\LoanRequest;
use App\Support\BranchContext;
use App\Support\LoanRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoanRequestController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', LoanRequest::class);

        // Anything that isn't a plain value (e.g. ?search[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $id = fn (string $key) => ctype_digit($text($key)) ? (int) $text($key) : null;
        $state = [
            'status' => in_array($text('status'), array_keys(LoanRequest::STATUSES), true) ? $text('status') : '',
            'employee_id' => $id('employee_id'),
            'department_id' => $id('department_id'),
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $filtered = fn () => $this->scoped()
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['department_id'], fn ($q, $departmentId) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $departmentId)))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->where(fn ($w) => $w->where('loan_type', 'like', $like)
                    ->orWhereHas('employee', fn ($e) => $e->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('employee_code', 'like', $like))));
            });

        $requests = $filtered()
            ->when($state['status'], fn ($q, $status) => $q->where('status', $status))
            ->with(['employee.department:id,name', 'reviewedBy:id,name'])
            ->orderByRaw("status = 'pending' desc")->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $all = $this->scoped()->get(['id', 'employee_id', 'principal_amount', 'status', 'created_at', 'reviewed_at']);
        $pending = $all->where('status', 'pending');
        $decidedThisYear = $all->whereIn('status', ['approved', 'rejected'])->filter(fn (LoanRequest $r) => $r->reviewed_at?->year === today()->year);
        $approved = $decidedThisYear->where('status', 'approved');
        $byStatus = $filtered()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $canCreate = $request->user()->can('create', LoanRequest::class);
        $people = $this->employeeQuery()->with('department:id,name')->orderBy('name')->get(['id', 'name', 'employee_code', 'avatar', 'department_id', 'status', 'user_id']);
        // Affordability figures for everyone on the page and in the form.
        $ids = $people->pluck('id')->merge($requests->pluck('employee_id'))->unique()->values();
        $salaries = LoanRules::salaries($ids);
        $instalments = LoanRules::instalments($ids);
        $payable = $people->whereNotIn('status', LoanRules::INACTIVE)->values();

        return view('admin.hrm.approvals.loan-requests', [
            'requests' => $requests,
            'state' => $state,
            'employees' => $people->pluck('name', 'id'),
            'departments' => Department::query()->where('company_id', Company::current()->id)
                ->whereIn('id', $people->pluck('department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            'counts' => [
                'all' => (int) $byStatus->sum(),
                'pending' => (int) ($byStatus['pending'] ?? 0),
                'approved' => (int) ($byStatus['approved'] ?? 0),
                'rejected' => (int) ($byStatus['rejected'] ?? 0),
            ],
            'kpis' => [
                'pending' => $pending->count(),
                'pending_amount' => (float) $pending->sum('principal_amount'),
                'oldest_days' => $pending->isNotEmpty() ? (int) $pending->min('created_at')->startOfDay()->diffInDays(today()) : null,
                'approved' => $approved->count(),
                'approved_amount' => (float) $approved->sum('principal_amount'),
                'rejected' => $decidedThisYear->where('status', 'rejected')->count(),
                'rate' => $decidedThisYear->isNotEmpty() ? (int) round($approved->count() / $decidedThisYear->count() * 100) : null,
            ],
            'salaries' => $salaries,
            'instalments' => $instalments,
            'canCreate' => $canCreate,
            'formData' => $canCreate ? $payable->mapWithKeys(fn (Employee $e) => [$e->id => [
                'name' => $e->name,
                'meta' => collect([$e->employee_code, $e->department?->name])->filter()->implode(' · '),
                'salary' => $salaries->get($e->id),
                'instalments' => (float) $instalments->get($e->id, 0),
                'pending' => $pending->where('employee_id', $e->id)->count(),
            ]])->all() : [],
            'myEmployeeId' => $people->firstWhere('user_id', Auth::id())?->id,
            'canSelfApprove' => (bool) Auth::user()->is_super_admin,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', LoanRequest::class);

        $companyId = Company::current()->id;

        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $companyId)->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))],
            'loan_type' => ['required', 'string', 'max:120'],
            'principal_amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'monthly_installment' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        $principal = round((float) $data['principal_amount'], 2);
        $instalment = round((float) $data['monthly_installment'], 2);

        if ($problem = LoanRules::problem($employee, $principal, $instalment)) {
            throw ValidationException::withMessages(['monthly_installment' => $problem]);
        }

        if (LoanRequest::query()->where('employee_id', $employee->id)->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages(['employee_id' => $employee->name.' already has a loan request waiting for a decision. Decide that one first.']);
        }

        LoanRequest::create([
            'company_id' => $companyId,
            'employee_id' => $employee->id,
            'loan_type' => trim($data['loan_type']),
            'principal_amount' => $principal,
            'monthly_installment' => $instalment,
            'reason' => filled($data['reason'] ?? null) ? trim($data['reason']) : null,
            'status' => 'pending',
        ]);

        return back()->with('status', 'Loan request for '.$employee->name.' submitted. It now waits for approval.');
    }

    public function approve(Request $request, LoanRequest $loanRequest): RedirectResponse
    {
        $this->ensureOwn($loanRequest);
        $this->authorize('update', $loanRequest);
        $this->guardSelfReview($loanRequest);

        // The approver may lower the amount, change the instalment or pick the payout date; blank keeps what was asked.
        $data = $request->validate([
            'principal_amount' => ['nullable', 'numeric', 'gt:0', 'lte:'.(float) $loanRequest->principal_amount],
            'monthly_installment' => ['nullable', 'numeric', 'gt:0'],
            'disbursed_date' => ['nullable', 'date', 'after_or_equal:'.today()->subMonth()->toDateString(), 'before_or_equal:'.today()->addMonths(3)->toDateString()],
            'remarks' => ['nullable', 'string', 'max:500'],
        ], [
            'principal_amount.lte' => 'You can approve at most the '.number_format((float) $loanRequest->principal_amount, 2).' that was asked for.',
            'disbursed_date.after_or_equal' => 'The payout date can be at most one month back.',
            'disbursed_date.before_or_equal' => 'The payout date can be at most three months ahead.',
        ]);

        $employee = $loanRequest->employee;
        abort_unless($employee, 404);

        $principal = round((float) ($data['principal_amount'] ?? $loanRequest->principal_amount), 2);
        $instalment = round(min((float) ($data['monthly_installment'] ?? $loanRequest->monthly_installment), $principal), 2);

        if ($problem = LoanRules::problem($employee, $principal, $instalment)) {
            return back()->withErrors(['approve' => 'Not approved. '.$problem]);
        }

        $changed = abs($principal - (float) $loanRequest->principal_amount) > 0.005 || abs($instalment - (float) $loanRequest->monthly_installment) > 0.005;
        $note = collect([
            $changed ? 'Asked for '.number_format((float) $loanRequest->principal_amount, 2).' at '.number_format((float) $loanRequest->monthly_installment, 2).' a month.' : null,
            filled($data['remarks'] ?? null) ? trim($data['remarks']) : null,
        ])->filter()->implode(' ');

        $done = DB::transaction(function () use ($loanRequest, $employee, $principal, $instalment, $data, $note) {
            // Locked re-read: two clicks (or two approvers) must not pay the same request out twice.
            $fresh = LoanRequest::query()->whereKey($loanRequest->id)->lockForUpdate()->first();

            if (! $fresh || $fresh->status !== 'pending') {
                return false;
            }

            Loan::create([
                'company_id' => $fresh->company_id,
                'employee_id' => $employee->id,
                'loan_type' => $fresh->loan_type,
                'principal_amount' => $principal,
                'monthly_installment' => $instalment,
                'outstanding_amount' => $principal,
                'disbursed_date' => Carbon::parse($data['disbursed_date'] ?? today())->toDateString(),
                'status' => 'active',
                'remarks' => $fresh->reason,
            ]);

            $fresh->update([
                'principal_amount' => $principal,
                'monthly_installment' => $instalment,
                'status' => 'approved',
                'reviewed_by_user_id' => Auth::id(),
                'reviewed_at' => now(),
                'remarks' => $note !== '' ? $note : null,
            ]);

            return true;
        });

        if (! $done) {
            return back()->withErrors(['approve' => 'This request has already been decided.']);
        }

        return back()->with('status', 'Approved. A loan of '.number_format($principal, 2).' for '.$employee->name.' is now open, with '.number_format($instalment, 2).' recovered each payroll.');
    }

    public function reject(Request $request, LoanRequest $loanRequest): RedirectResponse
    {
        $this->ensureOwn($loanRequest);
        $this->authorize('update', $loanRequest);
        $this->guardSelfReview($loanRequest);

        $data = $request->validate(['remarks' => ['nullable', 'string', 'max:500']]);

        $done = LoanRequest::query()->whereKey($loanRequest->id)->where('status', 'pending')->update([
            'status' => 'rejected',
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at' => now(),
            'remarks' => filled($data['remarks'] ?? null) ? trim($data['remarks']) : null,
        ]);

        if (! $done) {
            return back()->withErrors(['reject' => 'This request has already been decided.']);
        }

        return back()->with('status', 'Loan request from '.($loanRequest->employee?->name ?? 'the employee').' rejected.');
    }

    /** Nobody decides their own loan. A super admin is the one exception, or a one-person company could never approve anything. */
    protected function guardSelfReview(LoanRequest $loanRequest): void
    {
        $user = Auth::user();

        abort_if(! $user->is_super_admin && $loanRequest->employee && (int) $loanRequest->employee->user_id === (int) $user->id, 403, 'You can\'t decide your own loan request.');
    }

    /** 404 (not 403) for another company's or branch's request, so the URL doesn't confirm it exists. */
    protected function ensureOwn(LoanRequest $loanRequest): void
    {
        abort_unless($this->scoped()->whereKey($loanRequest->id)->exists(), 404);
    }

    protected function scoped(): Builder
    {
        return LoanRequest::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }

    protected function employeeQuery(): Builder
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }
}
