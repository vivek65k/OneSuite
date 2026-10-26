<?php

namespace App\Support;

class PermissionGroups
{
    /**
     * Maps a permission's resource-key (the part of its name after the
     * verb prefix, e.g. "business::unit" in "create_business::unit") to a
     * human-readable group label for the Roles permission matrix.
     */
    protected const LABELS = [
        'user' => 'Platform User',
        'role' => 'Role',
        'department' => 'Department',
        'branch' => 'Branch',
        'business::unit' => 'Business Unit',
        'cost::center' => 'Cost Center',
        'holiday' => 'Holiday',
        'workflow' => 'Workflow',
        'approval_rule' => 'Approval Rule',
        'escalation_rule' => 'Escalation Rule',
        'automation_rule' => 'Automation Rule',
        'email_template' => 'Email Template',
        'sms_template' => 'SMS Template',
        'whatsapp_template' => 'WhatsApp Template',
        'notification_rule' => 'Notification Rule',
        'employee' => 'Employee',
        'job_position' => 'Job Position',
        'candidate' => 'Candidate',
        'resume_bank_entry' => 'Resume Bank Entry',
        'interview' => 'Interview',
        'offer_letter' => 'Offer Letter',
        'onboarding_task' => 'Onboarding Task',
        'probation_record' => 'Probation Record',
        'attendance_record' => 'Attendance Record',
        'regularization_request' => 'Regularization Request',
        'wfh_request' => 'WFH Request',
        'overtime_request' => 'Overtime Request',
        'leave_type' => 'Leave Type',
        'leave_balance' => 'Leave Balance',
        'leave_request' => 'Leave Request',
        'comp_off' => 'Comp Off',
        'leave_encashment' => 'Leave Encashment',
        'shift' => 'Shift',
        'shift_roster' => 'Shift Roster',
        'shift_swap_request' => 'Shift Swap Request',
        'overtime_rule' => 'Overtime Rule',
        'salary_component' => 'Salary Component',
        'salary_structure' => 'Salary Structure',
        'bonus' => 'Bonus',
        'incentive' => 'Incentive',
        'deduction' => 'Deduction',
        'loan' => 'Loan',
        'tax_declaration' => 'Tax Declaration',
        'pf_setting' => 'PF Setting',
        'esi_setting' => 'ESI Setting',
        'payroll_run' => 'Payroll Run',
        'payslip' => 'Payslip',
        'goal' => 'Goal',
        'kpi' => 'KPI',
        'employee_kpi' => 'Employee KPI',
        'appraisal' => 'Appraisal',
        'feedback_360' => '360 Feedback',
        'increment' => 'Increment',
        'trainer' => 'Trainer',
        'course' => 'Course',
        'training_session' => 'Training Session',
        'training_enrollment' => 'Training Enrollment',
        'certification' => 'Certification',
        'assessment' => 'Assessment',
        'assessment_result' => 'Assessment Result',
        'employee_asset' => 'Employee Asset',
        'travel_request' => 'Travel Request',
        'travel_booking' => 'Travel Booking',
        'travel_expense' => 'Travel Expense',
        'travel_settlement' => 'Travel Settlement',
        'expense_category' => 'Expense Category',
        'expense_claim' => 'Expense Claim',
        'employee_document' => 'Employee Document',
        'resignation' => 'Resignation',
        'separation_clearance' => 'Separation Clearance',
        'exit_interview' => 'Exit Interview',
        'final_settlement' => 'Final Settlement',
        'loan_request' => 'Loan Request',
        'promotion_request' => 'Promotion Request',
        'transfer_request' => 'Transfer Request',
        'hr_policy' => 'HR Policy',
        'attendance_rule_setting' => 'Attendance Rule Setting',
        'tax_slab' => 'Tax Slab',
        'document_template' => 'Document Template',
    ];

    /**
     * Resource-keys owned by the Settings app (the "settings" route group
     * in routes/admin.php, plus the org-structure models it manages).
     * Every other key in LABELS belongs to HRM.
     */
    protected const SETTINGS_KEYS = [
        'user', 'role', 'department', 'branch', 'business::unit', 'cost::center',
        'holiday', 'workflow', 'approval_rule', 'escalation_rule', 'automation_rule',
        'email_template', 'sms_template', 'whatsapp_template', 'notification_rule',
    ];

    /**
     * Sub-headings for the Roles permission matrix, so HRM's ~60 modules
     * read as "Recruitment, Attendance, Leave, ..." rather than one long
     * alphabetical list. Order here is display order; a key not listed
     * falls into "Other" at the end rather than disappearing.
     */
    protected const CATEGORIES = [
        'Users & Access' => ['user', 'role'],
        'Organization' => ['branch', 'department', 'business::unit', 'cost::center', 'holiday'],
        'Workflow & Approvals' => ['workflow', 'approval_rule', 'escalation_rule', 'automation_rule'],
        'Notifications & Templates' => ['notification_rule', 'email_template', 'sms_template', 'whatsapp_template'],

        'Employees' => ['employee', 'employee_document', 'employee_asset', 'promotion_request', 'transfer_request', 'hr_policy', 'document_template'],
        'Recruitment & Onboarding' => ['job_position', 'candidate', 'resume_bank_entry', 'interview', 'offer_letter', 'onboarding_task', 'probation_record'],
        'Attendance & Shifts' => ['attendance_record', 'regularization_request', 'wfh_request', 'overtime_request', 'overtime_rule', 'attendance_rule_setting', 'shift', 'shift_roster', 'shift_swap_request'],
        'Leave' => ['leave_type', 'leave_balance', 'leave_request', 'comp_off', 'leave_encashment'],
        'Payroll' => ['payroll_run', 'payslip', 'salary_component', 'salary_structure', 'increment', 'bonus', 'incentive', 'deduction', 'loan', 'loan_request', 'tax_declaration', 'tax_slab', 'pf_setting', 'esi_setting'],
        'Performance' => ['goal', 'kpi', 'employee_kpi', 'appraisal', 'feedback_360'],
        'Training' => ['course', 'trainer', 'training_session', 'training_enrollment', 'certification', 'assessment', 'assessment_result'],
        'Travel & Expenses' => ['travel_request', 'travel_booking', 'travel_expense', 'travel_settlement', 'expense_category', 'expense_claim'],
        'Separation' => ['resignation', 'separation_clearance', 'exit_interview', 'final_settlement'],
    ];

    /**
     * Tabler icon + colour tone (1-8, see .perm-tone-* in
     * onesuite-theme.css) for each category heading in the Roles matrix.
     */
    protected const CATEGORY_STYLES = [
        'Users & Access' => ['ti ti-shield-lock', 1],
        'Organization' => ['ti ti-building', 2],
        'Workflow & Approvals' => ['ti ti-git-branch', 3],
        'Notifications & Templates' => ['ti ti-bell', 4],
        'Settings Pages' => ['ti ti-layout-grid', 5],
        'Employees' => ['ti ti-users', 1],
        'Recruitment & Onboarding' => ['ti ti-user-plus', 6],
        'Attendance & Shifts' => ['ti ti-clock', 2],
        'Leave' => ['ti ti-umbrella', 7],
        'Payroll' => ['ti ti-cash', 3],
        'Performance' => ['ti ti-chart-arrows', 4],
        'Training' => ['ti ti-school', 8],
        'Travel & Expenses' => ['ti ti-plane', 5],
        'Separation' => ['ti ti-logout', 6],
    ];

    /**
     * @return array{icon: string, tone: int}
     */
    public static function categoryStyle(string $category): array
    {
        [$icon, $tone] = self::CATEGORY_STYLES[$category] ?? ['ti ti-dots', 8];

        return ['icon' => $icon, 'tone' => $tone];
    }

    public static function category(string $key): string
    {
        foreach (self::CATEGORIES as $category => $keys) {
            if (in_array($key, $keys, true)) {
                return $category;
            }
        }

        return 'Other';
    }

    /**
     * Position of a key within CATEGORIES (category order, then the order
     * listed inside it) - unlisted keys sort last, alphabetically.
     */
    public static function sortPosition(string $key): string
    {
        $position = 0;

        foreach (self::CATEGORIES as $keys) {
            foreach ($keys as $listed) {
                if ($listed === $key) {
                    return sprintf('%04d', $position);
                }
                $position++;
            }
        }

        return '9999'.$key;
    }

    public static function label(string $key): string
    {
        return self::LABELS[$key] ?? \Illuminate\Support\Str::headline(str_replace('::', '_', $key));
    }

    /**
     * Which app (config/onesuite_apps.php key) a permission group belongs
     * to, or null for a key this class doesn't know yet - those stay
     * visible everywhere rather than silently disappearing from Roles.
     */
    public static function appFor(string $key): ?string
    {
        if (in_array($key, self::SETTINGS_KEYS, true)) {
            return 'settings';
        }

        return array_key_exists($key, self::LABELS) ? 'hrm' : null;
    }

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return self::LABELS;
    }
}
