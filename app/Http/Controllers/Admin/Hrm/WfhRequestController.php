<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\PersistsTableState;
use App\Http\Concerns\ReviewsAttendanceRequests;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\WfhRequest;
use App\Support\ApprovalFlow;
use App\Support\Attendance;
use App\Support\BranchContext;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WfhRequestController extends Controller
{
    use PersistsTableState, ReviewsAttendanceRequests;

    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', WfhRequest::class);

        $state = $this->resolveTableState($request, 'wfh-requests', [
            'per_page' => 25,
        ]);

        if ($state instanceof RedirectResponse) {
            return $state;
        }

        $perPage = $this->clampPerPage($state['per_page'] ?? 25);

        $requests = $this->filteredQuery($state)
            ->with(['employee.department', 'reviewedBy'])
            ->orderByDesc('start_date')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.hrm.attendance.wfh-requests.index', [
            'requests' => $requests,
            'statusCounts' => $this->requestStatusCounts($this->filteredQuery(array_diff_key($state, ['status' => true]))),
            'kpis' => $this->requestKpis(WfhRequest::class),
            'state' => $state,
            'employees' => $this->employeeOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', WfhRequest::class);

        $companyId = Company::current()->id;

        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $companyId)->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $data['company_id'] = $companyId;

        WfhRequest::create($data);

        return redirect()->route('admin.wfh-requests.index')->with('status', 'WFH request submitted.');
    }

    public function approve(WfhRequest $wfhRequest): RedirectResponse
    {
        $this->authorize('update', $wfhRequest);
        $this->denyOwnRequest($wfhRequest->employee);

        // Multi-level approval: stop here while a manager further up still has to approve.
        if ($waiting = ApprovalFlow::forward($wfhRequest)) {
            return back()->with('status', $waiting);
        }
        $this->ensureReviewable($wfhRequest);

        $wfhRequest->update([
            'status' => 'approved',
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        // The same outcome as an approval from Team Approvals: working days only, leave days left alone, and the employee is told.
        \App\Support\RequestOutcome::approved($wfhRequest);

        return back()->with('status', 'WFH approved and attendance updated.');
    }

    public function reject(Request $request, WfhRequest $wfhRequest): RedirectResponse
    {
        $this->authorize('update', $wfhRequest);
        $this->denyOwnRequest($wfhRequest->employee);
        ApprovalFlow::rejected($wfhRequest, $request->input('remarks'));
        $this->ensureReviewable($wfhRequest);

        $wfhRequest->update([
            'status' => 'rejected',
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at' => now(),
            'remarks' => $request->input('remarks'),
        ]);

        return back()->with('status', 'WFH request rejected.');
    }

    public function destroy(WfhRequest $wfhRequest): RedirectResponse
    {
        $this->authorize('delete', $wfhRequest);
        Attendance::ensureOwn($wfhRequest);

        $wfhRequest->delete();

        return back(fallback: route('admin.wfh-requests.index'))->with('status', 'Request removed.');
    }

    protected function filteredQuery(array $state): Builder
    {
        return $this->applyRequestFilters(Attendance::requests(WfhRequest::class), $state, 'start_date');
    }

    protected function employeeOptions()
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->orderBy('name')
            ->pluck('name', 'id');
    }
}
