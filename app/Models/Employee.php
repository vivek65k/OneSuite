<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use App\Models\Scopes\EmployeeDataScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory, ScopedByEmployee;

    public const STATUSES = ['active', 'on_leave', 'inactive', 'terminated'];

    protected $fillable = [
        'company_id', 'user_id', 'employee_code', 'name', 'email', 'phone', 'gender', 'date_of_birth',
        'department_id', 'branch_id', 'manager_id', 'designation', 'employment_type', 'joining_date', 'status', 'avatar',
        'address', 'city', 'state', 'country', 'postal_code',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'joining_date' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * An employee IS the record data access is measured against, so the
     * column to match is their own id.
     */
    public function dataScopeColumn(): string
    {
        return 'id';
    }

    /**
     * Never limited by data access: the manager of the top person in
     * someone's team is outside that team, but "Reports to" should still
     * show their name rather than come back empty.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id')->withoutGlobalScope(EmployeeDataScope::class);
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class)->latest();
    }

    public function assets(): HasMany
    {
        return $this->hasMany(EmployeeAsset::class)->latest('assigned_date');
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(EmployeeFamilyMember::class);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(EmployeeEducation::class)->orderByDesc('end_year');
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(EmployeeExperience::class)->orderByDesc('start_date');
    }

    public function skills(): HasMany
    {
        return $this->hasMany(EmployeeSkill::class);
    }

    public function salaries(): HasMany
    {
        return $this->hasMany(EmployeeSalary::class)->orderByDesc('effective_date');
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(EmployeePromotion::class)->orderByDesc('promotion_date');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(EmployeeTransfer::class)->orderByDesc('transfer_date');
    }

    public function onboardingTasks(): HasMany
    {
        return $this->hasMany(OnboardingTask::class)->orderBy('due_date');
    }

    public function probationRecords(): HasMany
    {
        return $this->hasMany(ProbationRecord::class)->orderByDesc('start_date');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class)->orderByDesc('date');
    }

    public function regularizationRequests(): HasMany
    {
        return $this->hasMany(RegularizationRequest::class)->orderByDesc('date');
    }

    public function wfhRequests(): HasMany
    {
        return $this->hasMany(WfhRequest::class)->orderByDesc('start_date');
    }

    public function overtimeRequests(): HasMany
    {
        return $this->hasMany(OvertimeRequest::class)->orderByDesc('date');
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class)->orderByDesc('start_date');
    }

    public function compOffs(): HasMany
    {
        return $this->hasMany(CompOff::class)->orderByDesc('worked_date');
    }

    public function leaveEncashments(): HasMany
    {
        return $this->hasMany(LeaveEncashment::class)->orderByDesc('year');
    }

    public function currentSalary(): ?EmployeeSalary
    {
        return $this->salaries->firstWhere('is_current', true) ?? $this->salaries->first();
    }

    public function shiftRosters(): HasMany
    {
        return $this->hasMany(ShiftRoster::class);
    }

    public function shiftSwapRequests(): HasMany
    {
        return $this->hasMany(ShiftSwapRequest::class)->orderByDesc('date');
    }

    public function salaryStructures(): HasMany
    {
        return $this->hasMany(SalaryStructure::class)->orderByDesc('effective_date');
    }

    public function currentSalaryStructure(): ?SalaryStructure
    {
        return $this->salaryStructures->firstWhere('is_current', true) ?? $this->salaryStructures->first();
    }

    public function bonuses(): HasMany
    {
        return $this->hasMany(Bonus::class)->orderByDesc('period_year')->orderByDesc('period_month');
    }

    public function incentives(): HasMany
    {
        return $this->hasMany(Incentive::class)->orderByDesc('period_year')->orderByDesc('period_month');
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(Deduction::class)->orderByDesc('period_year')->orderByDesc('period_month');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class)->orderByDesc('disbursed_date');
    }

    public function taxDeclarations(): HasMany
    {
        return $this->hasMany(TaxDeclaration::class)->orderByDesc('financial_year');
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class)->orderByDesc('target_date');
    }

    public function employeeKpis(): HasMany
    {
        return $this->hasMany(EmployeeKpi::class);
    }

    public function appraisals(): HasMany
    {
        return $this->hasMany(Appraisal::class);
    }

    public function increments(): HasMany
    {
        return $this->hasMany(Increment::class)->orderByDesc('effective_date');
    }

    public function trainingEnrollments(): HasMany
    {
        return $this->hasMany(TrainingEnrollment::class);
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(Certification::class)->orderByDesc('issue_date');
    }

    public function assessmentResults(): HasMany
    {
        return $this->hasMany(AssessmentResult::class);
    }

    public function travelRequests(): HasMany
    {
        return $this->hasMany(TravelRequest::class)->orderByDesc('start_date');
    }

    public function travelExpenses(): HasMany
    {
        return $this->hasMany(TravelExpense::class);
    }

    public function expenseClaims(): HasMany
    {
        return $this->hasMany(ExpenseClaim::class);
    }

    public function resignations(): HasMany
    {
        return $this->hasMany(Resignation::class)->orderByDesc('resignation_date');
    }
}
