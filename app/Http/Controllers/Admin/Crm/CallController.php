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

class CallController extends Controller
{
    use ResolvesRelatable;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $direction = (string) $request->query('direction', '');

        $query = $this->companyQuery()->with('owner');

        if ($direction !== '') {
            $query->where('direction', $direction);
        }

        if ($search !== '') {
            $query->where('subject', 'like', "%{$search}%");
        }

        $calls = $query->orderByDesc('due_at')->paginate(25)->withQueryString();

        return view('admin.crm.calls.index', [
            'calls' => $calls->through(fn ($call) => tap($call, fn ($c) => $c->relatedLabel = $this->relatedLabel($c->related_type, $c->related_id))),
            'search' => $search,
            'direction' => $direction,
            'total' => $this->companyQuery()->count(),
            'inboundCount' => $this->companyQuery()->where('direction', 'inbound')->count(),
            'outboundCount' => $this->companyQuery()->where('direction', 'outbound')->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.crm.calls.form', ['call' => new Activity, 'relatable' => $this->relatableOptions(), 'employees' => $this->employeeOptions()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Activity::create($this->validated($request) + ['company_id' => Company::current()->id, 'type' => 'call', 'status' => 'completed', 'completed_at' => now()]);

        return redirect()->route('admin.crm.calls.index')->with('status', 'Call logged.');
    }

    public function edit(Activity $call): View
    {
        $this->ownCompany($call);

        return view('admin.crm.calls.form', [
            'call' => $call,
            'relatable' => $this->relatableOptions(),
            'employees' => $this->employeeOptions(),
            'currentRelatable' => $this->relatableValue($call->related_type, $call->related_id),
        ]);
    }

    public function update(Request $request, Activity $call): RedirectResponse
    {
        $this->ownCompany($call);
        $call->update($this->validated($request));

        return redirect()->route('admin.crm.calls.index')->with('status', 'Call updated.');
    }

    public function destroy(Activity $call): RedirectResponse
    {
        $this->ownCompany($call);
        $call->delete();

        return back()->with('status', 'Call deleted.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_at' => ['required', 'date'],
            'direction' => ['required', 'in:inbound,outbound'],
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
        return Activity::query()->where('company_id', Company::current()->id)->where('type', 'call');
    }

    protected function ownCompany(Activity $call): void
    {
        abort_unless($call->company_id === Company::current()->id && $call->type === 'call', 404);
    }
}
