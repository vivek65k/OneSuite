<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Resources that get the full CRUD permission set.
     */
    protected const FULL_CRUD_RESOURCES = [
        'user', 'department', 'branch', 'business::unit', 'cost::center', 'holiday',
        'workflow', 'approval_rule', 'escalation_rule', 'automation_rule',
        'email_template', 'sms_template', 'whatsapp_template', 'notification_rule',
        'employee',
        'job_position', 'candidate', 'resume_bank_entry', 'interview', 'offer_letter',
        'onboarding_task', 'probation_record',
        'attendance_record', 'regularization_request', 'wfh_request', 'overtime_request',
        'leave_type', 'leave_balance', 'leave_request', 'comp_off', 'leave_encashment',
        'shift', 'shift_roster', 'shift_swap_request', 'overtime_rule',
        'salary_component', 'salary_structure', 'bonus', 'incentive', 'deduction',
        'loan', 'tax_declaration', 'pf_setting', 'esi_setting', 'payroll_run', 'payslip',
        'goal', 'kpi', 'employee_kpi', 'appraisal', 'feedback_360', 'increment',
        'trainer', 'course', 'training_session', 'training_enrollment', 'certification', 'assessment', 'assessment_result',
        'employee_asset',
        'travel_request', 'travel_booking', 'travel_expense', 'travel_settlement',
        'expense_category', 'expense_claim', 'employee_document',
        'resignation', 'separation_clearance', 'exit_interview', 'final_settlement',
        'loan_request', 'promotion_request', 'transfer_request',
        'hr_policy', 'attendance_rule_setting', 'tax_slab', 'document_template',
    ];

    protected const FULL_CRUD_PREFIXES = [
        'view', 'view_any', 'create', 'update', 'restore', 'restore_any',
        'replicate', 'reorder', 'delete', 'delete_any', 'force_delete', 'force_delete_any',
    ];

    /**
     * Role only ever needed this subset (matches the original Shield-generated set).
     */
    protected const ROLE_PREFIXES = ['view', 'view_any', 'create', 'update', 'delete', 'delete_any'];

    public function run(): void
    {
        foreach (self::FULL_CRUD_RESOURCES as $resource) {
            foreach (self::FULL_CRUD_PREFIXES as $prefix) {
                Permission::firstOrCreate([
                    'name' => "{$prefix}_{$resource}",
                    'guard_name' => 'web',
                ]);
            }
        }

        foreach (self::ROLE_PREFIXES as $prefix) {
            Permission::firstOrCreate([
                'name' => "{$prefix}_role",
                'guard_name' => 'web',
            ]);
        }

        Permission::firstOrCreate([
            'name' => 'page_GeneralSettings',
            'guard_name' => 'web',
        ]);

        Permission::firstOrCreate([
            'name' => 'page_WorkingHours',
            'guard_name' => 'web',
        ]);
    }
}
