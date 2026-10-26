<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Concerns\Crm\ResolvesRelatable;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Activity;
use App\Models\Employee;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeetingController extends Controller
{
    use ResolvesRelatable;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $when = (string) $request->query('when', 'upcoming');

        $query = $this->companyQuery()->with('owner');

        if ($when === 'upcoming') {
            $query->where('start_at', '>=', now());
        } elseif ($when === 'past') {
            $query->where('start_at', '<', now());
        }

        if ($search !== '') {
            $query->where('subject', 'like', "%{$search}%");
        }

        $meetings = $query->orderBy('start_at', $when === 'past' ? 'desc' : 'asc')->paginate(25)->withQueryString();

        return view('admin.crm.meetings.index', [
            'meetings' => $meetings->through(fn ($m) => tap($m, fn ($x) => $x->relatedLabel = $this->relatedLabel($x->related_type, $x->related_id))),
            'search' => $search,
            'when' => $when,
            'total' => $this->companyQuery()->count(),
            'upcomingCount' => (clone $this->companyQuery())->where('start_at', '>=', now())->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.crm.meetings.form', ['meeting' => new Activity, 'relatable' => $this->relatableOptions(), 'employees' => $this->employeeOptions()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Activity::create($this->validated($request) + ['company_id' => Company::current()->id, 'type' => 'meeting', 'status' => 'pending']);

        return redirect()->route('admin.crm.meetings.index')->with('status', 'Meeting scheduled.');
    }

    public function edit(Activity $meeting): View
    {
        $this->ownCompany($meeting);

        return view('admin.crm.meetings.form', [
            'meeting' => $meeting,
            'relatable' => $this->relatableOptions(),
            'employees' => $this->employeeOptions(),
            'currentRelatable' => $this->relatableValue($meeting->related_type, $meeting->related_id),
        ]);
    }

    public function update(Request $request, Activity $meeting): RedirectResponse
    {
        $this->ownCompany($meeting);
        $meeting->update($this->validated($request));

        return redirect()->route('admin.crm.meetings.index')->with('status', 'Meeting updated.');
    }

    public function destroy(Activity $meeting): RedirectResponse
    {
        $this->ownCompany($meeting);
        $meeting->delete();

        return back()->with('status', 'Meeting cancelled.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after:start_at'],
            'owner_employee_id' => ['nullable', 'exists:employees,id'],
            'relatable' => ['nullable', 'string'],
        ]);

        $relatable = $data['relatable'] ?? null;
        unset($data['relatable']);
        $data['related_type'] = $relatable ? $this->relatedTypeFromKey($relatable) : null;
        $data['related_id'] = $relatable ? $this->relatedIdFromKey($relatable) : null;

        return $data;
    }

    protected function employeeOptions()
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->where('status', 'active')
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    protected function companyQuery()
    {
        return Activity::query()->where('company_id', Company::current()->id)->where('type', 'meeting');
    }

    protected function ownCompany(Activity $meeting): void
    {
        abort_unless($meeting->company_id === Company::current()->id && $meeting->type === 'meeting', 404);
    }
}
