<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\PersistsTableState;
use App\Http\Concerns\ReviewsAttendanceRequests;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\RegularizationRequest;
use App\Support\ApprovalFlow;
use App\Support\Attendance;
use App\Support\BranchContext;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegularizationRequestController extends Controller
{
    use PersistsTableState, ReviewsAttendanceRequests;

    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', RegularizationRequest::class);

        $state = $this->resolveTableState($request, 'regularization-requests', [
            'per_page' => 25,
        ]);

        if ($state instanceof RedirectResponse) {
            return $state;
        }

        $perPage = $this->clampPerPage($state['per_page'] ?? 25);

        $requests = $this->filteredQuery($state)
            ->with(['employee.department', 'reviewedBy'])
            ->orderByDesc('date')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.hrm.attendance.regularization-requests.index', [
            'requests' => $requests,
            'statusCounts' => $this->requestStatusCounts($this->filteredQuery(array_diff_key($state, ['status' => true]))),
            'kpis' => $this->requestKpis(RegularizationRequest::class),
            'state' => $state,
            'employees' => $this->employeeOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', RegularizationRequest::class);

        $companyId = Company::current()->id;

        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $companyId)->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))],
            'date' => ['required', 'date'],
            'requested_check_in' => ['nullable', 'date_format:H:i'],
            'requested_check_out' => ['nullable', 'date_format:H:i'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $data['company_id'] = $companyId;

        RegularizationRequest::create($data);

        return redirect()->route('admin.regularization-requests.index')->with('status', 'Regularization request submitted.');
    }

    public function approve(RegularizationRequest $regularizationRequest): RedirectResponse
    {
        $this->authorize('update', $regularizationRequest);
        $this->denyOwnRequest($regularizationRequest->employee);

        // Multi-level approval: stop here while a manager further up still has to approve.
        if ($waiting = ApprovalFlow::forward($regularizationRequest)) {
            return back()->with('status', $waiting);
        }
        $this->ensureReviewable($regularizationRequest);

        $regularizationRequest->update([
            'status' => 'approved',
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        // The same outcome as an approval from Team Approvals: only the times asked for change, and the employee is told.
        \App\Support\RequestOutcome::approved($regularizationRequest);

        return back()->with('status', 'Regularization approved and attendance updated.');
    }

    public function reject(Request $request, RegularizationRequest $regularizationRequest): RedirectResponse
    {
        $this->authorize('update', $regularizationRequest);
        $this->denyOwnRequest($regularizationRequest->employee);
        ApprovalFlow::rejected($regularizationRequest, $request->input('remarks'));
        $this->ensureReviewable($regularizationRequest);

        $regularizationRequest->update([
            'status' => 'rejected',
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at' => now(),
            'remarks' => $request->input('remarks'),
        ]);

        return back()->with('status', 'Regularization request rejected.');
    }

    public function destroy(RegularizationRequest $regularizationRequest): RedirectResponse
    {
        $this->authorize('delete', $regularizationRequest);
        Attendance::ensureOwn($regularizationRequest);

        $regularizationRequest->delete();

        return back(fallback: route('admin.regularization-requests.index'))->with('status', 'Request removed.');
    }

    protected function filteredQuery(array $state): Builder
    {
        return $this->applyRequestFilters(Attendance::requests(RegularizationRequest::class), $state, 'date');
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
