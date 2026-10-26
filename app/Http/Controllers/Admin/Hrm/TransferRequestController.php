<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeTransfer;
use App\Models\TransferRequest;
use App\Support\BranchContext;
use App\Support\Notifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TransferRequestController extends Controller
{
    protected const TABS = ['pending', 'approved', 'rejected'];

    /** Employees who have left can't be transferred. */
    protected const INACTIVE = ['terminated', 'inactive'];

    /** A transfer can be entered a little after the fact, or planned up to a year ahead. */
    protected const DAYS_BACK = 30;

    protected const DAYS_AHEAD = 365;

    /** The list shows the most recent requests. */
    protected const LIST_LIMIT = 200;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', TransferRequest::class);

        // Anything that isn't a plain value (e.g. ?tab[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        // Links from the old page used ?status=.
        $tab = $text('tab') !== '' ? $text('tab') : $text('status');
        $state = [
            'tab' => in_array($tab, self::TABS, true) ? $tab : '',
            'employee_id' => ctype_digit($text('employee_id')) ? (int) $text('employee_id') : null,
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $all = $this->scoped()->with(['employee.department:id,name', 'employee.branch:id,name', 'toDepartment:id,name', 'toBranch:id,name', 'reviewedBy:id,name'])
            // Waiting ones first (oldest on top), then the decided ones, newest first.
            ->orderByRaw("status = 'pending' desc")->orderByRaw("case when status = 'pending' then created_at end asc")->orderByDesc('reviewed_at')->orderByDesc('id')->get();
        // What each one moved (or would move): the history row written on approval remembers where they came from.
        $history = EmployeeTransfer::withoutGlobalScopes()->whereIn('employee_id', $all->pluck('employee_id')->unique())->with(['fromDepartment:id,name', 'fromBranch:id,name'])->get();
        $all->each(function (TransferRequest $r) use ($history) {
            $done = $r->status === 'approved'
                ? $history->first(fn (EmployeeTransfer $t) => $t->employee_id === $r->employee_id && $t->transfer_date?->isSameDay($r->transfer_date)
                    && (int) $t->to_department_id === (int) $r->to_department_id && (int) $t->to_branch_id === (int) $r->to_branch_id)
                : null;
            $r->setAttribute('from_department', $r->status === 'approved' ? $done?->fromDepartment?->name : $r->employee?->department?->name);
            $r->setAttribute('from_branch', $r->status === 'approved' ? $done?->fromBranch?->name : $r->employee?->branch?->name);
            $r->setAttribute('days_to_go', (int) today()->diffInDays($r->transfer_date, false));
            $r->setAttribute('waited', $r->created_at ? (int) $r->created_at->copy()->startOfDay()->diffInDays(today()) : 0);
        });

        $needle = mb_strtolower($state['search']);
        $filtered = $all
            ->when($state['employee_id'], fn (Collection $c, $id) => $c->where('employee_id', $id))
            ->when($needle !== '', fn (Collection $c) => $c->filter(fn (TransferRequest $r) => str_contains(mb_strtolower(($r->employee?->name ?? '').' '.$r->employee?->employee_code.' '.$r->toDepartment?->name.' '.$r->toBranch?->name), $needle)))
            ->values();
        $tabbed = fn (Collection $rows, string $tab) => $tab === '' ? $rows : $rows->where('status', $tab);
        $pending = $all->where('status', 'pending');
        $user = $request->user();
        $people = $this->employees()->with(['department:id,name,branch_id', 'branch:id,name'])->orderBy('name')->get(['id', 'name', 'employee_code', 'department_id', 'branch_id', 'status', 'user_id']);
        $busy = $pending->pluck('employee_id')->flip();

        return view('admin.hrm.approvals.transfer-requests', [
            'state' => $state,
            'requests' => $tabbed($filtered, $state['tab'])->values()->take(self::LIST_LIMIT),
            'counts' => ['all' => $filtered->count()] + collect(self::TABS)->mapWithKeys(fn ($tab) => [$tab => $tabbed($filtered, $tab)->count()])->all(),
            'filterPeople' => $people->whereIn('id', $all->pluck('employee_id')->unique())->values(),
            'kpis' => [
                'pending' => $pending->count(),
                'oldest' => (int) $pending->max('waited'),
                'branch_moves' => $pending->whereNotNull('to_branch_id')->count(),
                'due' => $pending->filter(fn (TransferRequest $r) => $r->days_to_go <= 7)->count(),
                'moved_year' => $all->where('status', 'approved')->filter(fn (TransferRequest $r) => $r->reviewed_at?->year === now()->year)->count(),
                'rejected' => $all->where('status', 'rejected')->filter(fn (TransferRequest $r) => $r->reviewed_at?->gte(today()->subDays(30)))->count(),
            ],
            'canCreate' => $user->can('create', TransferRequest::class),
            'canReview' => $user->can('update', new TransferRequest),
            'canDelete' => $user->can('delete', new TransferRequest),
            // Nobody decides their own transfer (super admin excepted), same as denyOwnRequest().
            'ownEmployeeId' => $user->is_super_admin ? null : $people->firstWhere('user_id', $user->id)?->id,
            // The form: active people without a request already waiting, and where they can go.
            'people' => $people->whereNotIn('status', self::INACTIVE)->reject(fn (Employee $e) => $busy->has($e->id))->values(),
            'departments' => Department::query()->where('company_id', Company::current()->id)->orderBy('name')->get(['id', 'name', 'branch_id']),
            'branches' => Branch::query()->where('company_id', Company::current()->id)->orderBy('name')->get(['id', 'name']),
            'minDate' => today()->subDays(self::DAYS_BACK)->toDateString(),
            'maxDate' => today()->addDays(self::DAYS_AHEAD)->toDateString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', TransferRequest::class);

        $personId = $request->input('employee_id');
        // Only someone this login may manage: its own company and, when tied to a branch, that branch.
        $employee = is_scalar($personId) && ctype_digit((string) $personId) ? $this->employees()->whereKey((int) $personId)->first() : null;
        if (! $employee) {
            throw ValidationException::withMessages(['employee_id' => 'Choose an employee from the list.']);
        }

        $data = $request->validate([
            'to_department_id' => ['nullable'],
            'to_branch_id' => ['nullable'],
            'transfer_date' => ['required', 'date', 'after_or_equal:'.today()->subDays(self::DAYS_BACK)->toDateString(), 'before_or_equal:'.today()->addDays(self::DAYS_AHEAD)->toDateString()],
            'notes' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'transfer_date.after_or_equal' => 'A transfer can be entered at most '.self::DAYS_BACK.' days after it took effect.',
            'transfer_date.before_or_equal' => 'A transfer can be planned at most a year ahead.',
            'notes.*' => 'Say why the transfer is needed (5 to 1,000 characters). The approver has only this to go on.',
        ]);

        $transfer = DB::transaction(function () use ($employee, $data) {
            // One at a time per person, so two quick saves can't both pass the checks.
            $person = Employee::withoutGlobalScopes()->whereKey($employee->id)->lockForUpdate()->first();
            if (in_array($person->status, self::INACTIVE, true)) {
                throw ValidationException::withMessages(['employee_id' => $person->name.' is no longer with the company, so they can\'t be transferred.']);
            }
            [$departmentId, $branchId] = $this->destination($person, $data['to_department_id'] ?? null, $data['to_branch_id'] ?? null);

            if (TransferRequest::withoutGlobalScopes()->where('employee_id', $person->id)->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['employee_id' => $person->name.' already has a transfer waiting for approval. Decide that one first.']);
            }

            $transfer = (new TransferRequest)->forceFill([
                'company_id' => $person->company_id,
                'employee_id' => $person->id,
                'to_department_id' => $departmentId,
                'to_branch_id' => $branchId,
                'transfer_date' => Carbon::parse($data['transfer_date'])->toDateString(),
                'notes' => trim(strip_tags($data['notes'])),
                'status' => 'pending',
            ]);
            $transfer->save();

            return $transfer;
        });

        Notifier::notifyUsersWithPermission(
            'update_transfer_request',
            'Transfer to approve',
            Auth::user()->name.' proposed moving '.$employee->name.' to '.$this->describe($transfer).'.',
            route('admin.transfer-requests.index'),
            'ti ti-exchange',
            $employee->branch_id,
            employeeId: $employee->id,
        );

        return back()->with('status', 'Transfer proposed for '.$employee->name.': to '.$this->describe($transfer).'. It is waiting for approval.');
    }

    /** Approve the request and move the employee. */
    public function approve(int $transferRequest): RedirectResponse
    {
        $found = $this->locate($transferRequest);
        $this->authorize('update', $found);
        $this->denyOwnRequest($found->employee);

        [$name, $where, $branchMoved] = DB::transaction(function () use ($found) {
            $row = TransferRequest::withoutGlobalScopes()->whereKey($found->id)->lockForUpdate()->first();
            // Decided a moment ago by someone else, or the button was pressed twice.
            if ($row->status !== 'pending') {
                throw ValidationException::withMessages(['transfer' => 'This transfer has already been '.$row->status.'.']);
            }
            $person = Employee::withoutGlobalScopes()->whereKey($row->employee_id)->lockForUpdate()->first();
            if (! $person || in_array($person->status, self::INACTIVE, true)) {
                throw ValidationException::withMessages(['transfer' => ($person?->name ?? 'This employee').' is no longer with the company, so the transfer can\'t be applied. Reject it instead.']);
            }
            // Checked again now: the department, the branch or the person's own position may have changed since it was proposed.
            [$departmentId, $branchId] = $this->destination($person, $row->to_department_id, $row->to_branch_id, 'transfer');

            // The history keeps where they came from.
            (new EmployeeTransfer)->forceFill([
                'employee_id' => $person->id,
                'from_department_id' => $person->department_id,
                'to_department_id' => $row->to_department_id,
                'from_branch_id' => $person->branch_id,
                'to_branch_id' => $row->to_branch_id,
                'transfer_date' => $row->transfer_date->toDateString(),
                'notes' => $row->notes,
            ])->save();

            DB::table('employees')->where('id', $person->id)->update([
                'department_id' => $departmentId ?? $person->department_id,
                'branch_id' => $branchId ?? $person->branch_id,
                'updated_at' => now(),
            ]);
            $row->forceFill(['status' => 'approved', 'reviewed_by_user_id' => Auth::id(), 'reviewed_at' => now()])->save();

            return [$person->name, $this->describe($row), $branchId !== null];
        });

        Notifier::notifyUser(
            Employee::withoutGlobalScopes()->with('user')->find($found->employee_id)?->user,
            'You have been transferred',
            'Your transfer to '.$where.' was approved, effective '.$found->transfer_date->format('M j, Y').'.',
            route('admin.my-profile.index'),
            'ti ti-exchange',
        );

        return back()->with('status', $name.' has been moved to '.$where.'.'.($branchMoved ? ' Leave types, shifts and expense categories are set per branch, so check their leave balances and roster in the new branch.' : ''));
    }

    public function reject(Request $request, int $transferRequest): RedirectResponse
    {
        $found = $this->locate($transferRequest);
        $this->authorize('update', $found);
        $this->denyOwnRequest($found->employee);

        $data = $request->validate(['remarks' => ['required', 'string', 'min:3', 'max:500']], ['remarks.*' => 'Say why the transfer is rejected (3 to 500 characters).']);
        $reason = trim(strip_tags($data['remarks']));

        DB::transaction(function () use ($found, $reason) {
            $row = TransferRequest::withoutGlobalScopes()->whereKey($found->id)->lockForUpdate()->first();
            if ($row->status !== 'pending') {
                throw ValidationException::withMessages(['transfer' => 'This transfer has already been '.$row->status.'.']);
            }
            $row->forceFill(['status' => 'rejected', 'reviewed_by_user_id' => Auth::id(), 'reviewed_at' => now(), 'remarks' => $reason])->save();
        });

        return back()->with('status', 'Transfer of '.($found->employee?->name ?? 'the employee').' rejected. They stay where they are.');
    }

    /** Take back a proposal that hasn't been decided. */
    public function destroy(int $transferRequest): RedirectResponse
    {
        $found = $this->locate($transferRequest);
        $this->authorize('delete', $found);

        // The delete is conditional, so a withdrawal racing an approval can't erase the record of a move that happened.
        if (! TransferRequest::withoutGlobalScopes()->whereKey($found->id)->where('status', 'pending')->delete()) {
            return back()->withErrors(['transfer' => 'This transfer has already been decided, so it stays on record.']);
        }

        return back()->with('status', 'Transfer proposal for '.($found->employee?->name ?? 'the employee').' withdrawn.');
    }

    /**
     * Where the person would end up, as [department id or null, branch id or null]: null means "stays as it is".
     * Refuses a move to nowhere, to where they already are, or to a department that belongs to another branch.
     *
     * @return array{0: ?int, 1: ?int}
     */
    protected function destination(Employee $person, mixed $toDepartment, mixed $toBranch, string $errorKey = ''): array
    {
        $fail = fn (string $field, string $message) => throw ValidationException::withMessages([$errorKey ?: $field => $message]);
        $id = fn (mixed $value) => filled($value) ? (is_scalar($value) && ctype_digit((string) $value) ? (int) $value : 0) : null;
        $companyId = (int) $person->company_id;

        $branchId = $id($toBranch);
        $branch = $branchId === null ? null : Branch::query()->where('company_id', $companyId)->whereKey($branchId)->first();
        if ($branchId !== null && ! $branch) {
            $fail('to_branch_id', 'Choose a branch from the list.');
        }
        $departmentId = $id($toDepartment);
        $department = $departmentId === null ? null : Department::query()->where('company_id', $companyId)->whereKey($departmentId)->first();
        if ($departmentId !== null && ! $department) {
            $fail('to_department_id', 'Choose a department from the list.');
        }

        // Choosing the place they are already in is not a change.
        $branchId = $branch && (int) $branch->id !== (int) $person->branch_id ? (int) $branch->id : null;
        $departmentId = $department && (int) $department->id !== (int) $person->department_id ? (int) $department->id : null;
        if ($branchId === null && $departmentId === null) {
            $fail('to_department_id', $person->name.' is already in that department and branch. Choose a different department or branch.');
        }

        $endBranchId = $branchId ?? (int) $person->branch_id;
        $endBranchName = $branch?->name ?? Branch::query()->whereKey($endBranchId)->value('name') ?? 'that branch';
        // A department that belongs to one branch can only hold people of that branch.
        $endDepartment = $department ?? ($person->department_id ? Department::query()->whereKey($person->department_id)->first() : null);
        if ($endDepartment && $endDepartment->branch_id !== null && (int) $endDepartment->branch_id !== $endBranchId) {
            $fail('to_department_id', $departmentId !== null
                ? $endDepartment->name.' belongs to another branch, not '.$endBranchName.'. Choose a department of '.$endBranchName.', or change the branch as well.'
                : 'Their current department ('.$endDepartment->name.') doesn\'t exist in '.$endBranchName.'. Choose the department they join there.');
        }

        return [$departmentId, $branchId];
    }

    /** "Sales in Kolkata Branch", "Kolkata Branch", "Sales". */
    protected function describe(TransferRequest $transfer): string
    {
        $department = $transfer->to_department_id ? Department::query()->whereKey($transfer->to_department_id)->value('name') : null;
        $branch = $transfer->to_branch_id ? Branch::query()->whereKey($transfer->to_branch_id)->value('name') : null;

        return $department && $branch ? $department.' in '.$branch : ($department ?? $branch ?? 'a new position');
    }

    /** 404 (not 403) for another company's or branch's request, so the id doesn't confirm it exists. */
    protected function locate(int $id): TransferRequest
    {
        return $this->scoped()->whereKey($id)->with('employee')->firstOrFail();
    }

    /** Requests this login may see: its own company and, when tied to a branch, that branch's employees. */
    protected function scoped(): Builder
    {
        return TransferRequest::query()
            ->where('transfer_requests.company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }

    protected function employees(): Builder
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }
}
