<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Trainer;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TrainerController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Trainer::class);

        $trainers = Trainer::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where(function ($q2) {
                $q2->whereNull('employee_id')
                    ->orWhereHas('employee', fn ($eq) => $eq->where('branch_id', BranchContext::currentBranchId()));
            }))
            ->with('employee')
            ->orderBy('name')
            ->get();

        return view('admin.hrm.learning.trainers.index', ['trainers' => $trainers]);
    }

    public function create(): View
    {
        $this->authorize('create', Trainer::class);

        return view('admin.hrm.learning.trainers.create', [
            'trainer' => new Trainer(),
            'employees' => $this->employeeOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Trainer::class);

        $data = $this->validated($request);
        $data['company_id'] = Company::current()->id;

        Trainer::create($data);

        return redirect()->route('admin.trainers.index')->with('status', 'Trainer added.');
    }

    public function edit(Trainer $trainer): View
    {
        $this->authorize('update', $trainer);

        return view('admin.hrm.learning.trainers.edit', [
            'trainer' => $trainer,
            'employees' => $this->employeeOptions(),
        ]);
    }

    public function update(Request $request, Trainer $trainer): RedirectResponse
    {
        $this->authorize('update', $trainer);

        $trainer->update($this->validated($request));

        return redirect()->route('admin.trainers.index')->with('status', 'Trainer updated.');
    }

    public function destroy(Trainer $trainer): RedirectResponse
    {
        $this->authorize('delete', $trainer);

        $trainer->delete();

        return redirect()->route('admin.trainers.index')->with('status', 'Trainer removed.');
    }

    protected function validated(Request $request): array
    {
        $companyId = Company::current()->id;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'employee_id' => ['nullable', Rule::exists('employees', 'id')->where('company_id', $companyId)],
        ]);

        $data['is_internal'] = $request->boolean('is_internal');
        $data['is_active'] = $request->boolean('is_active');

        if (! $data['is_internal']) {
            $data['employee_id'] = null;
        }

        return $data;
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
