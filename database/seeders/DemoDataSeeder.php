<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ExpenseCategory;
use App\Models\ExpenseClaim;
use App\Models\JobPosition;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Shift;
use App\Models\User;
use App\Support\DataScope;
use App\Support\Notifier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Realistic, persistent demo data for manually testing the app - a small
 * roster across both branches, three permission tiers (HR admin, team
 * manager, plain employee) so the HR/Manager/Employee dashboard split and
 * branch/app-access scoping are all visibly exercised, plus a few
 * transactional records (pending/approved/rejected) so the approval
 * queues and notification bell aren't empty on first login.
 *
 * Run with: php artisan db:seed --class=DemoDataSeeder
 * Not wired into DatabaseSeeder - this is optional sample data, not part
 * of a fresh install.
 */
class DemoDataSeeder extends Seeder
{
    protected const PASSWORD = 'password123';

    public function run(): void
    {
        $company = Company::current();
        $headOffice = Branch::where('company_id', $company->id)->where('name', 'Head Office')->firstOrFail();
        $kolkata = Branch::where('company_id', $company->id)->where('name', 'Kolkata Branch')->firstOrFail();

        $departments = $this->departments($company, $headOffice, $kolkata);
        [$hrRole, $managerRole, $employeeRole] = $this->roles();
        $leaveTypes = $this->leaveTypes($company, $headOffice, $kolkata);
        $this->shifts($company, $headOffice, $kolkata);
        $expenseCategories = $this->expenseCategories($company, $headOffice, $kolkata);
        $this->jobPositions($company, $departments, $headOffice, $kolkata);

        $priya = $this->employee($company, $headOffice, $departments['Human Resources'], $hrRole, [
            'employee_code' => 'EMP-HR01', 'name' => 'Priya Sharma', 'email' => 'priya.sharma@onesuite.test',
            'designation' => 'HR Manager', 'gender' => 'female', 'phone' => '9810000001',
        ]);

        $arjun = $this->employee($company, $headOffice, $departments['Sales'], $managerRole, [
            'employee_code' => 'EMP-MG01', 'name' => 'Arjun Mehta', 'email' => 'arjun.mehta@onesuite.test',
            'designation' => 'Sales Manager', 'gender' => 'male', 'phone' => '9810000002',
        ]);

        $kavya = $this->employee($company, $headOffice, $departments['Sales'], $employeeRole, [
            'employee_code' => 'EMP-SL01', 'name' => 'Kavya Reddy', 'email' => 'kavya.reddy@onesuite.test',
            'designation' => 'Sales Executive', 'gender' => 'female', 'phone' => '9810000003', 'manager_id' => $arjun->id,
        ]);

        $rohan = $this->employee($company, $headOffice, $departments['IT'], $employeeRole, [
            'employee_code' => 'EMP-IT01', 'name' => 'Rohan Gupta', 'email' => 'rohan.gupta@onesuite.test',
            'designation' => 'Software Engineer', 'gender' => 'male', 'phone' => '9810000004',
        ], appAccess: ['hrm']);

        $sneha = $this->employee($company, $kolkata, $departments['Finance'], $employeeRole, [
            'employee_code' => 'EMP-FN01', 'name' => 'Sneha Iyer', 'email' => 'sneha.iyer@onesuite.test',
            'designation' => 'Finance Executive', 'gender' => 'female', 'phone' => '9810000005',
        ]);

        $this->allocateLeaveBalances([$priya, $arjun, $kavya, $rohan, $sneha], $leaveTypes, $company->id);

        $this->sampleTransactions($company, $kavya, $rohan, $sneha, $leaveTypes, $expenseCategories);

        $this->command?->info('Demo data seeded. All new users share the password: '.self::PASSWORD);
    }

    /**
     * @return array<string, Department>
     */
    protected function departments(Company $company, Branch $headOffice, Branch $kolkata): array
    {
        $names = ['IT' => $headOffice, 'Human Resources' => $headOffice, 'Sales' => $headOffice, 'Finance' => $kolkata];

        return collect($names)->mapWithKeys(function ($branch, $name) use ($company) {
            $department = Department::firstOrCreate(
                ['company_id' => $company->id, 'code' => Str::upper(Str::substr(Str::slug($name, ''), 0, 4))],
                ['name' => $name, 'branch_id' => $branch->id, 'is_active' => true]
            );

            return [$name => $department];
        })->all();
    }

    /**
     * @return array{0: Role, 1: Role, 2: Role}
     */
    protected function roles(): array
    {
        // HR Manager: everything an HR operations lead needs (view_any_employee
        // routes them to the full HR Dashboard) except platform user/role
        // administration, which stays Settings-only.
        $hrPermissionNames = Permission::where('guard_name', 'web')
            ->get()
            ->reject(fn (Permission $p) => Str::endsWith($p->name, ['_user', '_role']))
            ->pluck('name');

        $hrRole = Role::firstOrCreate(['name' => 'demo_hr_manager', 'guard_name' => 'web'], ['data_scope' => DataScope::ALL]);
        $hrRole->syncPermissions($hrPermissionNames);

        // Manager and Employee both stay permission-less on purpose - the
        // Manager dashboard and Manager Self Service screens key off having
        // direct reports (manager_id), not an admin permission, and a plain
        // Employee only ever needs their own Employee Self Service screens.
        // Their data access (team / own) is what keeps any permission added
        // to them later within the manager's chain / the employee themselves.
        $managerRole = Role::firstOrCreate(['name' => 'demo_manager', 'guard_name' => 'web'], ['data_scope' => DataScope::TEAM]);
        $employeeRole = Role::firstOrCreate(['name' => 'demo_employee', 'guard_name' => 'web'], ['data_scope' => DataScope::OWN]);

        return [$hrRole, $managerRole, $employeeRole];
    }

    /**
     * @return array<string, LeaveType>
     */
    protected function leaveTypes(Company $company, Branch $headOffice, Branch $kolkata): array
    {
        $definitions = [
            ['code' => 'CL', 'name' => 'Casual Leave', 'days_per_year' => 12, 'is_paid' => true, 'carry_forward' => false],
            ['code' => 'SL', 'name' => 'Sick Leave', 'days_per_year' => 8, 'is_paid' => true, 'carry_forward' => false],
            ['code' => 'EL', 'name' => 'Earned Leave', 'days_per_year' => 15, 'is_paid' => true, 'carry_forward' => true],
        ];

        $result = [];

        foreach ([$headOffice, $kolkata] as $branch) {
            foreach ($definitions as $def) {
                $type = LeaveType::firstOrCreate(
                    ['company_id' => $company->id, 'branch_id' => $branch->id, 'code' => $def['code']],
                    [...$def, 'is_active' => true]
                );

                $result[$branch->id.':'.$def['code']] = $type;
            }
        }

        return $result;
    }

    protected function shifts(Company $company, Branch $headOffice, Branch $kolkata): void
    {
        foreach ([$headOffice, $kolkata] as $branch) {
            Shift::firstOrCreate(
                ['company_id' => $company->id, 'branch_id' => $branch->id, 'code' => 'GEN'],
                [
                    'name' => 'General Shift', 'start_time' => '09:00:00', 'end_time' => '18:00:00',
                    'break_minutes' => 60, 'grace_minutes' => 10, 'half_day_hours' => 4, 'full_day_hours' => 8,
                    'is_night_shift' => false, 'is_flexible' => false, 'is_active' => true,
                ]
            );
        }
    }

    /**
     * @return array<string, ExpenseCategory>
     */
    protected function expenseCategories(Company $company, Branch $headOffice, Branch $kolkata): array
    {
        $names = ['Travel', 'Office Supplies', 'Client Entertainment', 'Software & Tools'];
        $result = [];

        foreach ([$headOffice, $kolkata] as $branch) {
            foreach ($names as $name) {
                $category = ExpenseCategory::firstOrCreate(
                    ['company_id' => $company->id, 'branch_id' => $branch->id, 'name' => $name],
                    ['is_active' => true]
                );

                $result[$branch->id.':'.$name] = $category;
            }
        }

        return $result;
    }

    /**
     * @param  array<string, Department>  $departments
     */
    protected function jobPositions(Company $company, array $departments, Branch $headOffice, Branch $kolkata): void
    {
        $positions = [
            ['title' => 'Senior Software Engineer', 'department' => $departments['IT'], 'branch' => $headOffice, 'headcount' => 2],
            ['title' => 'Sales Executive', 'department' => $departments['Sales'], 'branch' => $headOffice, 'headcount' => 1],
            ['title' => 'Accountant', 'department' => $departments['Finance'], 'branch' => $kolkata, 'headcount' => 1],
        ];

        foreach ($positions as $p) {
            JobPosition::firstOrCreate(
                ['company_id' => $company->id, 'branch_id' => $p['branch']->id, 'title' => $p['title']],
                [
                    'department_id' => $p['department']->id, 'employment_type' => 'full_time',
                    'headcount' => $p['headcount'], 'status' => 'open',
                ]
            );
        }
    }

    /**
     * @param  array<int, string>|null  $appAccess
     */
    protected function employee(Company $company, Branch $branch, Department $department, Role $role, array $attrs, ?array $appAccess = null): Employee
    {
        $user = User::updateOrCreate(
            ['email' => $attrs['email']],
            [
                'name' => $attrs['name'], 'password' => self::PASSWORD, 'is_active' => true, 'is_super_admin' => false,
                'app_access' => $appAccess, 'default_app' => $appAccess ? 'hrm' : null,
            ]
        );
        $user->syncRoles([$role->name]);

        return Employee::updateOrCreate(
            ['company_id' => $company->id, 'employee_code' => $attrs['employee_code']],
            [
                'user_id' => $user->id, 'branch_id' => $branch->id, 'department_id' => $department->id,
                'name' => $attrs['name'], 'email' => $attrs['email'], 'phone' => $attrs['phone'] ?? null,
                'gender' => $attrs['gender'] ?? null, 'designation' => $attrs['designation'],
                'manager_id' => $attrs['manager_id'] ?? null, 'employment_type' => 'full_time',
                'joining_date' => now()->subMonths(random_int(3, 30)), 'status' => 'active',
            ]
        );
    }

    /**
     * @param  array<int, Employee>  $employees
     * @param  array<string, LeaveType>  $leaveTypes
     */
    protected function allocateLeaveBalances(array $employees, array $leaveTypes, int $companyId): void
    {
        $year = now()->year;

        foreach ($employees as $employee) {
            foreach (['CL', 'SL', 'EL'] as $code) {
                $type = $leaveTypes[$employee->branch_id.':'.$code] ?? null;

                if ($type) {
                    LeaveBalance::adjust($employee->id, $companyId, $type->id, $year, 'allocated', (float) $type->days_per_year);
                }
            }
        }
    }

    /**
     * @param  array<string, LeaveType>  $leaveTypes
     * @param  array<string, ExpenseCategory>  $expenseCategories
     */
    protected function sampleTransactions(Company $company, Employee $kavya, Employee $rohan, Employee $sneha, array $leaveTypes, array $expenseCategories): void
    {
        // Pending leave (Kavya) - shows up in Leave Approval and notifies HR.
        $pendingLeave = LeaveRequest::create([
            'company_id' => $company->id, 'employee_id' => $kavya->id,
            'leave_type_id' => $leaveTypes[$kavya->branch_id.':CL']->id,
            'start_date' => now()->addDays(5), 'end_date' => now()->addDays(6), 'days' => 2,
            'reason' => 'Family function out of town.', 'status' => 'pending',
        ]);
        Notifier::notifyUsersWithPermission(
            'update_leave_request', 'New leave request',
            "{$kavya->name} requested {$pendingLeave->days} day(s) of leave.",
            route('admin.leave-requests.approval'), 'ti ti-calendar-event', $kavya->branch_id, 'hrm'
        );

        // Approved leave (Rohan) - already reviewed, employee already notified.
        $approvedLeave = LeaveRequest::create([
            'company_id' => $company->id, 'employee_id' => $rohan->id,
            'leave_type_id' => $leaveTypes[$rohan->branch_id.':SL']->id,
            'start_date' => now()->subDays(10), 'end_date' => now()->subDays(10), 'days' => 1,
            'reason' => 'Fever, resting at home.', 'status' => 'approved', 'reviewed_at' => now()->subDays(9),
        ]);
        LeaveBalance::adjust($rohan->id, $company->id, $approvedLeave->leave_type_id, now()->year, 'used', 1);
        Notifier::notifyUser(
            $rohan->user, 'Leave request approved',
            "Your leave request for {$approvedLeave->days} day(s) starting {$approvedLeave->start_date->format('M j, Y')} was approved.",
            route('admin.my-leave.index'), 'ti ti-circle-check', 'hrm'
        );

        // Rejected leave (Sneha).
        $rejectedLeave = LeaveRequest::create([
            'company_id' => $company->id, 'employee_id' => $sneha->id,
            'leave_type_id' => $leaveTypes[$sneha->branch_id.':CL']->id,
            'start_date' => now()->addDays(20), 'end_date' => now()->addDays(22), 'days' => 3,
            'reason' => 'Personal travel.', 'status' => 'rejected', 'reviewed_at' => now()->subDay(),
            'remarks' => 'Insufficient notice for peak month-end.',
        ]);
        Notifier::notifyUser(
            $sneha->user, 'Leave request rejected',
            "Your leave request starting {$rejectedLeave->start_date->format('M j, Y')} was rejected. Reason: {$rejectedLeave->remarks}",
            route('admin.my-leave.index'), 'ti ti-circle-x', 'hrm'
        );

        // Pending expense claim (Kavya).
        $pendingExpense = ExpenseClaim::create([
            'company_id' => $company->id, 'employee_id' => $kavya->id,
            'expense_category_id' => $expenseCategories[$kavya->branch_id.':Travel']->id,
            'amount' => 2500, 'expense_date' => now()->subDays(2),
            'description' => 'Client visit - cab fare.', 'status' => 'pending',
        ]);
        Notifier::notifyUsersWithPermission(
            'update_expense_claim', 'New expense claim',
            "{$kavya->name} submitted a claim for ".number_format((float) $pendingExpense->amount, 2).'.',
            route('admin.expense-approval.index'), 'ti ti-receipt-2', $kavya->branch_id, 'hrm'
        );

        // Approved expense claim (Rohan).
        $approvedExpense = ExpenseClaim::create([
            'company_id' => $company->id, 'employee_id' => $rohan->id,
            'expense_category_id' => $expenseCategories[$rohan->branch_id.':Software & Tools']->id,
            'amount' => 1200, 'expense_date' => now()->subDays(7),
            'description' => 'Annual IDE license renewal.', 'status' => 'approved', 'reviewed_at' => now()->subDays(6),
        ]);
        Notifier::notifyUser(
            $rohan->user, 'Expense claim approved',
            'Your claim for '.number_format((float) $approvedExpense->amount, 2).' was approved.',
            route('admin.my-expenses.index'), 'ti ti-circle-check', 'hrm'
        );
    }
}
