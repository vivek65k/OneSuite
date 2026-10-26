<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Concerns\Crm\ResolvesRelatable;
use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Activity;
use App\Models\Employee;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Activities (tasks, calls, meetings, emails) live in crm_activities and
 * already have dedicated full-CRUD screens in CRM - this is a single,
 * consolidated "today's work" hub for the Sales team instead of
 * duplicating four separate screens. It also newly lets activities log
 * against Sales Orders, Quotations and Contracts (Activity::RELATABLE
 * was extended for this), not just CRM's Lead/Account/Contact/Opportunity.
 */
class ActivityController extends Controller
{
    use ResolvesCurrentEmployee, ResolvesRelatable;

    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $scope = (string) $request->query('scope', 'mine');
        $type = (string) $request->query('type', '');
        $status = (string) $request->query('status', 'pending');

        $query = Activity::where('company_id', $companyId)->with('owner');

        if ($scope === 'mine') {
            $query->where('owner_employee_id', $this->currentEmployee()?->id);
        }
        if ($type !== '') {
            $query->where('type', $type);
        }
        if ($status !== '') {
            $query->where('status', $status);
        }

        $activities = $query->orderBy('due_at')->paginate(25)->withQueryString()
            ->through(fn ($a) => tap($a, fn ($x) => $x->relatedLabel = $this->relatedLabel($x->related_type, $x->related_id)));

        $mine = Activity::where('company_id', $companyId)->where('owner_employee_id', $this->currentEmployee()?->id)->where('status', 'pending');

        return view('admin.sales.activities.index', [
            'activities' => $activities,
            'scope' => $scope,
            'type' => $type,
            'status' => $status,
            'types' => Activity::TYPES,
            'statuses' => Activity::STATUSES,
            'priorities' => Activity::PRIORITIES,
            'dueTodayCount' => (clone $mine)->whereDate('due_at', now()->toDateString())->count(),
            'overdueCount' => (clone $mine)->whereNotNull('due_at')->where('due_at', '<', now())->count(),
            'pendingCount' => (clone $mine)->count(),
            'relatable' => $this->relatableOptions(),
            'employees' => $this->employeeOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $companyId = Company::current()->id;

        $isLoggedCall = $data['type'] === 'call' || $data['type'] === 'email';
        Activity::create($data + [
            'company_id' => $companyId,
            'status' => $isLoggedCall ? 'completed' : 'pending',
            'completed_at' => $isLoggedCall ? now() : null,
        ]);

        return redirect()->route('admin.sales.activities.index')->with('status', ucfirst($data['type']).' logged.');
    }

    public function complete(Activity $activity): RedirectResponse
    {
        $this->ownCompany($activity);
        $activity->update(['status' => 'completed', 'completed_at' => now()]);

        if ($activity->is_recurring) {
            Activity::create([
                'company_id' => $activity->company_id, 'type' => $activity->type, 'subject' => $activity->subject,
                'description' => $activity->description, 'related_type' => $activity->related_type, 'related_id' => $activity->related_id,
                'due_at' => $activity->computeNextOccurrence(), 'status' => 'pending', 'priority' => $activity->priority,
                'owner_employee_id' => $activity->owner_employee_id, 'is_recurring' => true,
                'recurrence_frequency' => $activity->recurrence_frequency, 'recurrence_parent_id' => $activity->recurrence_parent_id ?? $activity->id,
            ]);
        }

        return back()->with('status', 'Marked complete.');
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $this->ownCompany($activity);
        $activity->delete();

        return back()->with('status', 'Activity deleted.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'type' => ['required', 'in:'.implode(',', array_keys(Activity::TYPES))],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_at' => ['nullable', 'date'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after:start_at'],
            'direction' => ['nullable', 'in:inbound,outbound'],
            'priority' => ['nullable', 'in:'.implode(',', array_keys(Activity::PRIORITIES))],
            'owner_employee_id' => ['nullable', 'exists:employees,id'],
            'relatable' => ['nullable', 'string'],
        ]);

        $data['priority'] = $data['priority'] ?? 'medium';
        $relatable = $data['relatable'] ?? null;
        unset($data['relatable']);
        $data['related_type'] = $relatable ? $this->relatedTypeFromKey($relatable) : null;
        $data['related_id'] = $relatable ? $this->relatedIdFromKey($relatable) : null;

        return $data;
    }

    protected function employeeOptions()
    {
        return Employee::query()->where('company_id', Company::current()->id)->where('status', 'active')
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->orderBy('name')->pluck('name', 'id');
    }

    protected function ownCompany(Activity $activity): void
    {
        abort_unless($activity->company_id === Company::current()->id, 404);
    }
}
