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

class EmailController extends Controller
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

        $emails = $query->orderByDesc('due_at')->paginate(25)->withQueryString();

        return view('admin.crm.emails.index', [
            'emails' => $emails->through(fn ($e) => tap($e, fn ($x) => $x->relatedLabel = $this->relatedLabel($x->related_type, $x->related_id))),
            'search' => $search,
            'direction' => $direction,
            'total' => $this->companyQuery()->count(),
            'sentCount' => $this->companyQuery()->where('direction', 'outbound')->count(),
            'receivedCount' => $this->companyQuery()->where('direction', 'inbound')->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.crm.emails.form', ['email' => new Activity, 'relatable' => $this->relatableOptions(), 'employees' => $this->employeeOptions()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Activity::create($this->validated($request) + ['company_id' => Company::current()->id, 'type' => 'email', 'status' => 'completed', 'completed_at' => now()]);

        return redirect()->route('admin.crm.emails.index')->with('status', 'Email logged.');
    }

    public function edit(Activity $email): View
    {
        $this->ownCompany($email);

        return view('admin.crm.emails.form', [
            'email' => $email,
            'relatable' => $this->relatableOptions(),
            'employees' => $this->employeeOptions(),
            'currentRelatable' => $this->relatableValue($email->related_type, $email->related_id),
        ]);
    }

    public function update(Request $request, Activity $email): RedirectResponse
    {
        $this->ownCompany($email);
        $email->update($this->validated($request));

        return redirect()->route('admin.crm.emails.index')->with('status', 'Email updated.');
    }

    public function destroy(Activity $email): RedirectResponse
    {
        $this->ownCompany($email);
        $email->delete();

        return back()->with('status', 'Email removed.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
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
        return Activity::query()->where('company_id', Company::current()->id)->where('type', 'email');
    }

    protected function ownCompany(Activity $email): void
    {
        abort_unless($email->company_id === Company::current()->id && $email->type === 'email', 404);
    }
}
