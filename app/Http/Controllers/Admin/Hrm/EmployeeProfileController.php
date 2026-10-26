<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeAsset;
use App\Models\EmployeeDocument;
use App\Models\EmployeeEducation;
use App\Models\EmployeeExperience;
use App\Models\EmployeeFamilyMember;
use App\Models\EmployeePromotion;
use App\Models\EmployeeSalary;
use App\Models\EmployeeSkill;
use App\Models\EmployeeTransfer;
use App\Models\OnboardingTask;
use App\Models\ProbationRecord;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeProfileController extends Controller
{
    public function documents(Employee $employee): View
    {
        $this->authorize('view', $employee);

        $employee->load('documents');

        return view('admin.hrm.employees.documents', ['employee' => $employee]);
    }

    public function storeDocument(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', Rule::in(array_keys(EmployeeDocument::TYPES))],
            'expiry_date' => ['nullable', 'date'],
            'file' => EmployeeDocument::fileRules(),
        ], EmployeeDocument::fileMessages());

        $employee->documents()->create([
            'company_id' => $employee->company_id,
            'name' => $data['name'],
            'category' => $data['category'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            // Private storage: the file is only reachable through the checked download link.
            'file_path' => EmployeeDocument::storeUpload($request->file('file')),
        ]);

        return $this->redirectToSection($employee, 'documents.index', 'Document uploaded.');
    }

    public function destroyDocument(Employee $employee, EmployeeDocument $document): RedirectResponse
    {
        $this->authorize('update', $employee);

        abort_unless($document->employee_id === $employee->id, 404);
        $document->delete();

        return $this->redirectToSection($employee, 'documents.index', 'Document removed.');
    }

    public function assets(Employee $employee): View
    {
        $this->authorize('view', $employee);

        $employee->load('assets');

        return view('admin.hrm.employees.assets', ['employee' => $employee]);
    }

    public function storeAsset(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        $data = $request->validate([
            'asset_name' => ['required', 'string', 'max:255'],
            'asset_tag' => ['nullable', 'string', 'max:100'],
            'assigned_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['assigned', 'returned'])],
        ]);

        $data['company_id'] = $employee->company_id;

        $employee->assets()->create($data);

        return $this->redirectToSection($employee, 'assets.index', 'Asset added.');
    }

    public function destroyAsset(Employee $employee, EmployeeAsset $asset): RedirectResponse
    {
        $this->authorize('update', $employee);

        abort_unless($asset->employee_id === $employee->id, 404);
        $asset->delete();

        return $this->redirectToSection($employee, 'assets.index', 'Asset removed.');
    }

    public function familyMembers(Employee $employee): View
    {
        $this->authorize('view', $employee);

        $employee->load('familyMembers');

        return view('admin.hrm.employees.family', ['employee' => $employee]);
    }

    public function storeFamilyMember(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'relation' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $employee->familyMembers()->create($data);

        return $this->redirectToSection($employee, 'family-members.index', 'Family member added.');
    }

    public function destroyFamilyMember(Employee $employee, EmployeeFamilyMember $familyMember): RedirectResponse
    {
        $this->authorize('update', $employee);

        abort_unless($familyMember->employee_id === $employee->id, 404);
        $familyMember->delete();

        return $this->redirectToSection($employee, 'family-members.index', 'Family member removed.');
    }

    public function educations(Employee $employee): View
    {
        $this->authorize('view', $employee);

        $employee->load('educations');

        return view('admin.hrm.employees.education', ['employee' => $employee]);
    }

    public function storeEducation(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        $data = $request->validate([
            'qualification' => ['required', 'string', 'max:255'],
            'institution' => ['required', 'string', 'max:255'],
            'field_of_study' => ['nullable', 'string', 'max:255'],
            'start_year' => ['nullable', 'integer', 'digits:4'],
            'end_year' => ['nullable', 'integer', 'digits:4'],
            'grade' => ['nullable', 'string', 'max:50'],
        ]);

        $employee->educations()->create($data);

        return $this->redirectToSection($employee, 'educations.index', 'Education entry added.');
    }

    public function destroyEducation(Employee $employee, EmployeeEducation $education): RedirectResponse
    {
        $this->authorize('update', $employee);

        abort_unless($education->employee_id === $employee->id, 404);
        $education->delete();

        return $this->redirectToSection($employee, 'educations.index', 'Education entry removed.');
    }

    public function experiences(Employee $employee): View
    {
        $this->authorize('view', $employee);

        $employee->load('experiences');

        return view('admin.hrm.employees.experience', ['employee' => $employee]);
    }

    public function storeExperience(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'designation' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $employee->experiences()->create($data);

        return $this->redirectToSection($employee, 'experiences.index', 'Experience added.');
    }

    public function destroyExperience(Employee $employee, EmployeeExperience $experience): RedirectResponse
    {
        $this->authorize('update', $employee);

        abort_unless($experience->employee_id === $employee->id, 404);
        $experience->delete();

        return $this->redirectToSection($employee, 'experiences.index', 'Experience removed.');
    }

    public function skills(Employee $employee): View
    {
        $this->authorize('view', $employee);

        $employee->load('skills');

        return view('admin.hrm.employees.skills', ['employee' => $employee]);
    }

    public function storeSkill(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        $data = $request->validate([
            'skill_name' => ['required', 'string', 'max:255'],
            'proficiency' => ['required', Rule::in(EmployeeSkill::PROFICIENCIES)],
        ]);

        $employee->skills()->create($data);

        return $this->redirectToSection($employee, 'skills.index', 'Skill added.');
    }

    public function destroySkill(Employee $employee, EmployeeSkill $skill): RedirectResponse
    {
        $this->authorize('update', $employee);

        abort_unless($skill->employee_id === $employee->id, 404);
        $skill->delete();

        return $this->redirectToSection($employee, 'skills.index', 'Skill removed.');
    }

    public function salaries(Employee $employee): View
    {
        $this->authorize('view', $employee);

        $employee->load('salaries');

        return view('admin.hrm.employees.salary', ['employee' => $employee]);
    }

    public function storeSalary(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'effective_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['is_current'] = $request->boolean('is_current');

        $employee->salaries()->create($data);

        return $this->redirectToSection($employee, 'salaries.index', 'Salary record added.');
    }

    public function destroySalary(Employee $employee, EmployeeSalary $salary): RedirectResponse
    {
        $this->authorize('update', $employee);

        abort_unless($salary->employee_id === $employee->id, 404);
        $salary->delete();

        return $this->redirectToSection($employee, 'salaries.index', 'Salary record removed.');
    }

    public function promotions(Employee $employee): View
    {
        $this->authorize('view', $employee);

        $employee->load('promotions');

        return view('admin.hrm.employees.promotion', ['employee' => $employee]);
    }

    public function storePromotion(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        $data = $request->validate([
            'from_designation' => ['nullable', 'string', 'max:255'],
            'to_designation' => ['required', 'string', 'max:255'],
            'promotion_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $employee->promotions()->create($data);
        $employee->update(['designation' => $data['to_designation']]);

        return $this->redirectToSection($employee, 'promotions.index', 'Promotion recorded.');
    }

    public function destroyPromotion(Employee $employee, EmployeePromotion $promotion): RedirectResponse
    {
        $this->authorize('update', $employee);

        abort_unless($promotion->employee_id === $employee->id, 404);
        $promotion->delete();

        return $this->redirectToSection($employee, 'promotions.index', 'Promotion record removed.');
    }

    public function transfers(Employee $employee): View
    {
        $this->authorize('view', $employee);

        $employee->load(['transfers', 'transfers.toDepartment', 'transfers.toBranch', 'transfers.fromDepartment', 'transfers.fromBranch']);

        return view('admin.hrm.employees.transfer', ['employee' => $employee]);
    }

    public function storeTransfer(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        $companyId = $employee->company_id;

        $data = $request->validate([
            'to_department_id' => ['nullable', Rule::exists('departments', 'id')->where('company_id', $companyId)],
            'to_branch_id' => ['nullable', Rule::exists('branches', 'id')->where('company_id', $companyId)],
            'transfer_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $employee->transfers()->create(array_merge($data, [
            'from_department_id' => $employee->department_id,
            'from_branch_id' => $employee->branch_id,
        ]));

        $employee->update([
            'department_id' => $data['to_department_id'] ?? $employee->department_id,
            'branch_id' => $data['to_branch_id'] ?? $employee->branch_id,
        ]);

        return $this->redirectToSection($employee, 'transfers.index', 'Transfer recorded.');
    }

    public function destroyTransfer(Employee $employee, EmployeeTransfer $transfer): RedirectResponse
    {
        $this->authorize('update', $employee);

        abort_unless($transfer->employee_id === $employee->id, 404);
        $transfer->delete();

        return $this->redirectToSection($employee, 'transfers.index', 'Transfer record removed.');
    }

    public function onboarding(Employee $employee): View
    {
        $this->authorize('view', $employee);

        $employee->load(['onboardingTasks' => fn ($q) => $q->orderBy('is_completed')->orderBy('due_date'), 'onboardingTasks.assignedTo', 'probationRecords.reviewedBy']);

        return view('admin.hrm.employees.onboarding', [
            'employee' => $employee,
            'tasksByCategory' => $employee->onboardingTasks->groupBy('category'),
            // Only this company's own users - never other clients'.
            'users' => \App\Support\Onboarding::assignableUsers(),
        ]);
    }

    public function storeOnboardingTask(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(OnboardingTask::CATEGORIES))],
            'title' => ['required', 'string', 'max:255'],
            'assigned_role' => ['nullable', Rule::in(array_keys(OnboardingTask::ROLES))],
            'assigned_to_user_id' => ['nullable', Rule::exists('users', 'id')->where('company_id', $employee->company_id)],
            'due_date' => ['nullable', 'date'],
        ]);

        $data['company_id'] = $employee->company_id;

        $employee->onboardingTasks()->create($data);

        return $this->redirectToSection($employee, 'onboarding.index', 'Onboarding task added.');
    }

    public function storeProbation(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(array_keys(ProbationRecord::STATUSES))],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['company_id'] = $employee->company_id;

        $employee->probationRecords()->create($data);

        return $this->redirectToSection($employee, 'onboarding.index', 'Probation record added.');
    }

    protected function redirectToSection(Employee $employee, string $routeName, string $status): RedirectResponse
    {
        return redirect()->route("admin.employees.{$routeName}", $employee)->with('status', $status);
    }
}
