<ul class="nav flex-column">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.hrm.dashboard') ? 'active' : '' }}" href="{{ route('admin.hrm.dashboard') }}">
            <i class="ti ti-smart-home"></i> <span class="link-text">Dashboard</span>
            <span class="link-flyout">Dashboard</span>
        </a>
    </li>
</ul>

<x-sidebar-group title="Employees" icon="ti ti-users" id="menu-employees" color="blue" :active="request()->routeIs('admin.employees.*')">
    @navcan('admin.employees.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.employees.index') ? 'active' : '' }}" href="{{ route('admin.employees.index') }}">
                <i class="ti ti-list"></i> <span class="link-text">Employee List</span>
                <span class="link-flyout">Employee List</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.employees.org-chart')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.employees.org-chart') ? 'active' : '' }}" href="{{ route('admin.employees.org-chart') }}">
                <i class="ti ti-hierarchy"></i> <span class="link-text">Org Chart</span>
                <span class="link-flyout">Org Chart</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.employees.create')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.employees.create') ? 'active' : '' }}" href="{{ route('admin.employees.create') }}">
                <i class="ti ti-user-plus"></i> <span class="link-text">Add Employee</span>
                <span class="link-flyout">Add Employee</span>
            </a>
        </li>
    @endnavcan
</x-sidebar-group>

<x-sidebar-group title="Recruitment" icon="ti ti-briefcase" id="menu-recruitment" color="purple" :active="request()->routeIs('admin.job-positions.*') || request()->routeIs('admin.candidates.*') || request()->routeIs('admin.resume-bank.*') || request()->routeIs('admin.interviews.*') || request()->routeIs('admin.interview-feedback.*') || request()->routeIs('admin.offer-letters.*') || request()->routeIs('admin.recruitment-reports.*')">
    @navcan('admin.job-positions.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.job-positions.index') || request()->routeIs('admin.job-positions.create') || request()->routeIs('admin.job-positions.edit') ? 'active' : '' }}" href="{{ route('admin.job-positions.index') }}">
                <i class="ti ti-briefcase"></i> <span class="link-text">Job Positions</span>
                <span class="link-flyout">Job Positions</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.job-positions.open')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.job-positions.open') ? 'active' : '' }}" href="{{ route('admin.job-positions.open') }}">
                <i class="ti ti-folder"></i> <span class="link-text">Open Jobs</span>
                <span class="link-flyout">Open Jobs</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.candidates.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.candidates.index') || request()->routeIs('admin.candidates.create') || request()->routeIs('admin.candidates.edit') ? 'active' : '' }}" href="{{ route('admin.candidates.index') }}">
                <i class="ti ti-users"></i> <span class="link-text">Candidates</span>
                <span class="link-flyout">Candidates</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.resume-bank.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.resume-bank.*') ? 'active' : '' }}" href="{{ route('admin.resume-bank.index') }}">
                <i class="ti ti-file-text"></i> <span class="link-text">Resume Bank</span>
                <span class="link-flyout">Resume Bank</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.interviews.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.interviews.*') ? 'active' : '' }}" href="{{ route('admin.interviews.index') }}">
                <i class="ti ti-calendar-event"></i> <span class="link-text">Interview Schedule</span>
                <span class="link-flyout">Interview Schedule</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.interview-feedback.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.interview-feedback.*') ? 'active' : '' }}" href="{{ route('admin.interview-feedback.index') }}">
                <i class="ti ti-message-2"></i> <span class="link-text">Interview Feedback</span>
                <span class="link-flyout">Interview Feedback</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.offer-letters.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.offer-letters.*') ? 'active' : '' }}" href="{{ route('admin.offer-letters.index') }}">
                <i class="ti ti-file-certificate"></i> <span class="link-text">Offer Letter</span>
                <span class="link-flyout">Offer Letter</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.candidates.pipeline')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.candidates.pipeline') ? 'active' : '' }}" href="{{ route('admin.candidates.pipeline') }}">
                <i class="ti ti-git-branch"></i> <span class="link-text">Hiring Pipeline</span>
                <span class="link-flyout">Hiring Pipeline</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.recruitment-reports.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.recruitment-reports.*') ? 'active' : '' }}" href="{{ route('admin.recruitment-reports.index') }}">
                <i class="ti ti-chart-bar"></i> <span class="link-text">Recruitment Reports</span>
                <span class="link-flyout">Recruitment Reports</span>
            </a>
        </li>
    @endnavcan
</x-sidebar-group>

<x-sidebar-group title="Onboarding" icon="ti ti-rocket" id="menu-onboarding" color="teal" :active="request()->routeIs('admin.onboarding.*') || request()->routeIs('admin.probation-records.*')">
    @navcan('admin.onboarding.overview')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.onboarding.overview') ? 'active' : '' }}" href="{{ route('admin.onboarding.overview') }}">
                <i class="ti ti-chart-bar"></i> <span class="link-text">Overview</span>
                <span class="link-flyout">Overview</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.onboarding.category.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->route('category') === 'welcome-checklist' ? 'active' : '' }}" href="{{ route('admin.onboarding.category.index', 'welcome-checklist') }}">
                <i class="ti ti-list-check"></i> <span class="link-text">Welcome Checklist</span>
                <span class="link-flyout">Welcome Checklist</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.onboarding.category.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->route('category') === 'document-verification' ? 'active' : '' }}" href="{{ route('admin.onboarding.category.index', 'document-verification') }}">
                <i class="ti ti-file-check"></i> <span class="link-text">Document Verification</span>
                <span class="link-flyout">Document Verification</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.onboarding.category.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->route('category') === 'asset-allocation' ? 'active' : '' }}" href="{{ route('admin.onboarding.category.index', 'asset-allocation') }}">
                <i class="ti ti-devices"></i> <span class="link-text">Asset Allocation</span>
                <span class="link-flyout">Asset Allocation</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.onboarding.category.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->route('category') === 'it-account-creation' ? 'active' : '' }}" href="{{ route('admin.onboarding.category.index', 'it-account-creation') }}">
                <i class="ti ti-device-laptop"></i> <span class="link-text">IT Account Creation</span>
                <span class="link-flyout">IT Account Creation</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.onboarding.category.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->route('category') === 'orientation' ? 'active' : '' }}" href="{{ route('admin.onboarding.category.index', 'orientation') }}">
                <i class="ti ti-compass"></i> <span class="link-text">Orientation</span>
                <span class="link-flyout">Orientation</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.probation-records.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.probation-records.*') ? 'active' : '' }}" href="{{ route('admin.probation-records.index') }}">
                <i class="ti ti-clock"></i> <span class="link-text">Probation Tracking</span>
                <span class="link-flyout">Probation Tracking</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.onboarding.manager-tasks')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.onboarding.manager-tasks') ? 'active' : '' }}" href="{{ route('admin.onboarding.manager-tasks') }}">
                <i class="ti ti-user-check"></i> <span class="link-text">Manager Tasks</span>
                <span class="link-flyout">Manager Tasks</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.onboarding.hr-tasks')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.onboarding.hr-tasks') ? 'active' : '' }}" href="{{ route('admin.onboarding.hr-tasks') }}">
                <i class="ti ti-users"></i> <span class="link-text">HR Tasks</span>
                <span class="link-flyout">HR Tasks</span>
            </a>
        </li>
    @endnavcan
</x-sidebar-group>

<x-sidebar-group title="Attendance" icon="ti ti-clock" id="menu-attendance" color="orange" :active="request()->routeIs('admin.attendance.*') || request()->routeIs('admin.regularization-requests.*') || request()->routeIs('admin.wfh-requests.*') || request()->routeIs('admin.overtime-requests.*')">
    @navcan('admin.attendance.dashboard')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.attendance.dashboard') ? 'active' : '' }}" href="{{ route('admin.attendance.dashboard') }}">
                <i class="ti ti-dashboard"></i> <span class="link-text">Attendance Dashboard</span>
                <span class="link-flyout">Attendance Dashboard</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.attendance.daily')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.attendance.daily') ? 'active' : '' }}" href="{{ route('admin.attendance.daily') }}">
                <i class="ti ti-clipboard-check"></i> <span class="link-text">Daily Attendance</span>
                <span class="link-flyout">Daily Attendance</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.attendance.monthly')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.attendance.monthly') ? 'active' : '' }}" href="{{ route('admin.attendance.monthly') }}">
                <i class="ti ti-calendar"></i> <span class="link-text">Monthly Attendance</span>
                <span class="link-flyout">Monthly Attendance</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.attendance.punch-logs')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.attendance.punch-logs') ? 'active' : '' }}" href="{{ route('admin.attendance.punch-logs') }}">
                <i class="ti ti-clock"></i> <span class="link-text">Punch Logs</span>
                <span class="link-flyout">Punch Logs</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.regularization-requests.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.regularization-requests.*') ? 'active' : '' }}" href="{{ route('admin.regularization-requests.index') }}">
                <i class="ti ti-edit"></i> <span class="link-text">Regularization Requests</span>
                <span class="link-flyout">Regularization Requests</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.attendance.biometric-sync')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.attendance.biometric-sync') ? 'active' : '' }}" href="{{ route('admin.attendance.biometric-sync') }}">
                <i class="ti ti-fingerprint"></i> <span class="link-text">Biometric Sync</span>
                <span class="link-flyout">Biometric Sync</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.attendance.geo')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.attendance.geo') ? 'active' : '' }}" href="{{ route('admin.attendance.geo') }}">
                <i class="ti ti-map-pin"></i> <span class="link-text">Geo Attendance</span>
                <span class="link-flyout">Geo Attendance</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.wfh-requests.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.wfh-requests.*') ? 'active' : '' }}" href="{{ route('admin.wfh-requests.index') }}">
                <i class="ti ti-home"></i> <span class="link-text">Work From Home</span>
                <span class="link-flyout">Work From Home</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.overtime-requests.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.overtime-requests.*') ? 'active' : '' }}" href="{{ route('admin.overtime-requests.index') }}">
                <i class="ti ti-alarm"></i> <span class="link-text">Overtime</span>
                <span class="link-flyout">Overtime</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.attendance.reports')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.attendance.reports') ? 'active' : '' }}" href="{{ route('admin.attendance.reports') }}">
                <i class="ti ti-chart-bar"></i> <span class="link-text">Attendance Reports</span>
                <span class="link-flyout">Attendance Reports</span>
            </a>
        </li>
    @endnavcan
</x-sidebar-group>

<x-sidebar-group title="Leave Management" icon="ti ti-calendar-event" id="menu-leave" color="pink" :active="request()->routeIs('admin.leave-types.*') || request()->routeIs('admin.leave-balances.*') || request()->routeIs('admin.leave-requests.*') || request()->routeIs('admin.comp-offs.*') || request()->routeIs('admin.leave-encashments.*') || request()->routeIs('admin.leave-reports.*') || request()->routeIs('admin.holidays.*')">
    @navcan('admin.leave-types.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.leave-types.*') ? 'active' : '' }}" href="{{ route('admin.leave-types.index') }}">
                <i class="ti ti-list"></i> <span class="link-text">Leave Types</span>
                <span class="link-flyout">Leave Types</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.leave-balances.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.leave-balances.*') ? 'active' : '' }}" href="{{ route('admin.leave-balances.index') }}">
                <i class="ti ti-wallet"></i> <span class="link-text">Leave Balance</span>
                <span class="link-flyout">Leave Balance</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.leave-requests.apply')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.leave-requests.apply') ? 'active' : '' }}" href="{{ route('admin.leave-requests.apply') }}">
                <i class="ti ti-calendar-plus"></i> <span class="link-text">Apply Leave</span>
                <span class="link-flyout">Apply Leave</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.leave-requests.calendar')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.leave-requests.calendar') ? 'active' : '' }}" href="{{ route('admin.leave-requests.calendar') }}">
                <i class="ti ti-calendar-event"></i> <span class="link-text">Leave Calendar</span>
                <span class="link-flyout">Leave Calendar</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.leave-requests.approval')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.leave-requests.approval') ? 'active' : '' }}" href="{{ route('admin.leave-requests.approval') }}">
                <i class="ti ti-thumb-up"></i> <span class="link-text">Leave Approval</span>
                <span class="link-flyout">Leave Approval</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.holidays.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.holidays.*') ? 'active' : '' }}" href="{{ route('admin.holidays.index') }}">
                <i class="ti ti-gift"></i> <span class="link-text">Holiday Calendar</span>
                <span class="link-flyout">Holiday Calendar</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.comp-offs.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.comp-offs.*') ? 'active' : '' }}" href="{{ route('admin.comp-offs.index') }}">
                <i class="ti ti-briefcase"></i> <span class="link-text">Comp Off</span>
                <span class="link-flyout">Comp Off</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.leave-encashments.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.leave-encashments.*') ? 'active' : '' }}" href="{{ route('admin.leave-encashments.index') }}">
                <i class="ti ti-cash"></i> <span class="link-text">Leave Encashment</span>
                <span class="link-flyout">Leave Encashment</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.leave-reports.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.leave-reports.*') ? 'active' : '' }}" href="{{ route('admin.leave-reports.index') }}">
                <i class="ti ti-report-money"></i> <span class="link-text">Leave Reports</span>
                <span class="link-flyout">Leave Reports</span>
            </a>
        </li>
    @endnavcan
</x-sidebar-group>

<x-sidebar-group title="Shift Management" icon="ti ti-clock" id="menu-shift" color="slate" :active="request()->routeIs('admin.shifts.*') || request()->routeIs('admin.shift-roster.*') || request()->routeIs('admin.shift-swaps.*') || request()->routeIs('admin.overtime-rules.*')">
    @navcan('admin.shifts.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.shifts.*') ? 'active' : '' }}" href="{{ route('admin.shifts.index') }}">
                <i class="ti ti-list"></i> <span class="link-text">Shift Master</span>
                <span class="link-flyout">Shift Master</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.shift-roster.planning')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.shift-roster.planning') ? 'active' : '' }}" href="{{ route('admin.shift-roster.planning') }}">
                <i class="ti ti-layout-grid"></i> <span class="link-text">Roster Planning</span>
                <span class="link-flyout">Roster Planning</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.shift-roster.assign')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.shift-roster.assign') ? 'active' : '' }}" href="{{ route('admin.shift-roster.assign') }}">
                <i class="ti ti-calendar-plus"></i> <span class="link-text">Assign Shift</span>
                <span class="link-flyout">Assign Shift</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.shift-roster.weekly')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.shift-roster.weekly') ? 'active' : '' }}" href="{{ route('admin.shift-roster.weekly') }}">
                <i class="ti ti-calendar" ></i> <span class="link-text">Weekly Schedule</span>
                <span class="link-flyout">Weekly Schedule</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.shift-roster.night')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.shift-roster.night') ? 'active' : '' }}" href="{{ route('admin.shift-roster.night') }}">
                <i class="ti ti-moon"></i> <span class="link-text">Night Shift</span>
                <span class="link-flyout">Night Shift</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.shift-roster.flexible')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.shift-roster.flexible') ? 'active' : '' }}" href="{{ route('admin.shift-roster.flexible') }}">
                <i class="ti ti-adjustments"></i> <span class="link-text">Flexible Shift</span>
                <span class="link-flyout">Flexible Shift</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.shift-swaps.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.shift-swaps.*') ? 'active' : '' }}" href="{{ route('admin.shift-swaps.index') }}">
                <i class="ti ti-repeat"></i> <span class="link-text">Shift Swap</span>
                <span class="link-flyout">Shift Swap</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.overtime-rules.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.overtime-rules.*') ? 'active' : '' }}" href="{{ route('admin.overtime-rules.index') }}">
                <i class="ti ti-alarm"></i> <span class="link-text">Overtime Rules</span>
                <span class="link-flyout">Overtime Rules</span>
            </a>
        </li>
    @endnavcan
</x-sidebar-group>

<x-sidebar-group title="Payroll" icon="ti ti-currency-rupee" id="menu-payroll" color="green" :active="request()->routeIs('admin.payroll-dashboard.*') || request()->routeIs('admin.salary-structures.*') || request()->routeIs('admin.salary-components.*') || request()->routeIs('admin.payroll-runs.*') || request()->routeIs('admin.payslips.*') || request()->routeIs('admin.salary-revisions.*') || request()->routeIs('admin.bonuses.*') || request()->routeIs('admin.incentives.*') || request()->routeIs('admin.deductions.*') || request()->routeIs('admin.loans.*') || request()->routeIs('admin.tax-declarations.*') || request()->routeIs('admin.tax-slabs.*') || request()->routeIs('admin.pf.*') || request()->routeIs('admin.esi.*') || request()->routeIs('admin.payroll-reports.*')">
    @navcan('admin.payroll-dashboard.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.payroll-dashboard.*') ? 'active' : '' }}" href="{{ route('admin.payroll-dashboard.index') }}">
                <i class="ti ti-dashboard"></i> <span class="link-text">Payroll Dashboard</span>
                <span class="link-flyout">Payroll Dashboard</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.salary-structures.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.salary-structures.*') ? 'active' : '' }}" href="{{ route('admin.salary-structures.index') }}">
                <i class="ti ti-file-invoice"></i> <span class="link-text">Salary Structure</span>
                <span class="link-flyout">Salary Structure</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.payroll-runs.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.payroll-runs.*') ? 'active' : '' }}" href="{{ route('admin.payroll-runs.index') }}">
                <i class="ti ti-run"></i> <span class="link-text">Payroll Run</span>
                <span class="link-flyout">Payroll Run</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.payslips.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.payslips.*') ? 'active' : '' }}" href="{{ route('admin.payslips.index') }}">
                <i class="ti ti-receipt"></i> <span class="link-text">Payslips</span>
                <span class="link-flyout">Payslips</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.salary-revisions.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.salary-revisions.*') ? 'active' : '' }}" href="{{ route('admin.salary-revisions.index') }}">
                <i class="ti ti-trending-up"></i> <span class="link-text">Salary Revision</span>
                <span class="link-flyout">Salary Revision</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.bonuses.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.bonuses.*') ? 'active' : '' }}" href="{{ route('admin.bonuses.index') }}">
                <i class="ti ti-gift"></i> <span class="link-text">Bonuses</span>
                <span class="link-flyout">Bonuses</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.incentives.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.incentives.*') ? 'active' : '' }}" href="{{ route('admin.incentives.index') }}">
                <i class="ti ti-coin"></i> <span class="link-text">Incentives</span>
                <span class="link-flyout">Incentives</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.deductions.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.deductions.*') ? 'active' : '' }}" href="{{ route('admin.deductions.index') }}">
                <i class="ti ti-trending-down"></i> <span class="link-text">Deductions</span>
                <span class="link-flyout">Deductions</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.loans.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.loans.*') ? 'active' : '' }}" href="{{ route('admin.loans.index') }}">
                <i class="ti ti-credit-card"></i> <span class="link-text">Loans</span>
                <span class="link-flyout">Loans</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.tax-declarations.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.tax-declarations.*') || request()->routeIs('admin.tax-slabs.*') ? 'active' : '' }}" href="{{ route('admin.tax-declarations.index') }}">
                <i class="ti ti-receipt-tax font-16"></i> <span class="link-text">Tax</span>
                <span class="link-flyout">Tax</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.pf.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.pf.*') ? 'active' : '' }}" href="{{ route('admin.pf.index') }}">
                <i class="ti ti-shield-check"></i> <span class="link-text">PF</span>
                <span class="link-flyout">PF</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.esi.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.esi.*') ? 'active' : '' }}" href="{{ route('admin.esi.index') }}">
                <i class="ti ti-stethoscope"></i> <span class="link-text">ESI</span>
                <span class="link-flyout">ESI</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.payroll-reports.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.payroll-reports.*') ? 'active' : '' }}" href="{{ route('admin.payroll-reports.index') }}">
                <i class="ti ti-report"></i> <span class="link-text">Payroll Reports</span>
                <span class="link-flyout">Payroll Reports</span>
            </a>
        </li>
    @endnavcan
</x-sidebar-group>

<x-sidebar-group title="Performance Management" icon="ti ti-target" id="menu-performance" color="red" :active="request()->routeIs('admin.goals.*') || request()->routeIs('admin.kpis.*') || request()->routeIs('admin.employee-kpis.*') || request()->routeIs('admin.appraisals.*') || request()->routeIs('admin.appraisal-cycles.*') || request()->routeIs('admin.feedback-360.*') || request()->routeIs('admin.self-reviews.*') || request()->routeIs('admin.manager-reviews.*') || request()->routeIs('admin.promotions.*') || request()->routeIs('admin.increments.*') || request()->routeIs('admin.performance-reports.*')">
    @navcan('admin.goals.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.goals.*') ? 'active' : '' }}" href="{{ route('admin.goals.index') }}">
                <i class="ti ti-target"></i> <span class="link-text">Goal Management</span>
                <span class="link-flyout">Goal Management</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.kpis.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.kpis.*') || request()->routeIs('admin.employee-kpis.*') ? 'active' : '' }}" href="{{ route('admin.kpis.index') }}">
                <i class="ti ti-chart-dots"></i> <span class="link-text">KPIs</span>
                <span class="link-flyout">KPIs</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.appraisals.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.appraisals.*') || request()->routeIs('admin.appraisal-cycles.*') ? 'active' : '' }}" href="{{ route('admin.appraisals.index') }}">
                <i class="ti ti-clipboard-check"></i> <span class="link-text">Appraisals</span>
                <span class="link-flyout">Appraisals</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.feedback-360.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.feedback-360.*') ? 'active' : '' }}" href="{{ route('admin.feedback-360.index') }}">
                <i class="ti ti-messages"></i> <span class="link-text">360 Feedback</span>
                <span class="link-flyout">360 Feedback</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.manager-reviews.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.manager-reviews.*') ? 'active' : '' }}" href="{{ route('admin.manager-reviews.index') }}">
                <i class="ti ti-user-check"></i> <span class="link-text">Manager Review</span>
                <span class="link-flyout">Manager Review</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.self-reviews.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.self-reviews.*') ? 'active' : '' }}" href="{{ route('admin.self-reviews.index') }}">
                <i class="ti ti-user"></i> <span class="link-text">Self Review</span>
                <span class="link-flyout">Self Review</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.promotions.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.promotions.*') ? 'active' : '' }}" href="{{ route('admin.promotions.index') }}">
                <i class="ti ti-award"></i> <span class="link-text">Promotion</span>
                <span class="link-flyout">Promotion</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.increments.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.increments.*') ? 'active' : '' }}" href="{{ route('admin.increments.index') }}">
                <i class="ti ti-trending-up"></i> <span class="link-text">Increment</span>
                <span class="link-flyout">Increment</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.performance-reports.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.performance-reports.*') ? 'active' : '' }}" href="{{ route('admin.performance-reports.index') }}">
                <i class="ti ti-report"></i> <span class="link-text">Performance Reports</span>
                <span class="link-flyout">Performance Reports</span>
            </a>
        </li>
    @endnavcan
</x-sidebar-group>

<x-sidebar-group title="Learning & Training" icon="ti ti-school" id="menu-learning" color="teal" :active="request()->routeIs('admin.courses.*') || request()->routeIs('admin.training-sessions.*') || request()->routeIs('admin.training-enrollments.*') || request()->routeIs('admin.certifications.*') || request()->routeIs('admin.assessments.*') || request()->routeIs('admin.assessment-results.*') || request()->routeIs('admin.learning-progress.*') || request()->routeIs('admin.trainers.*') || request()->routeIs('admin.training-reports.*')">
    @navcan('admin.courses.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.courses.*') ? 'active' : '' }}" href="{{ route('admin.courses.index') }}">
                <i class="ti ti-book"></i> <span class="link-text">Courses</span>
                <span class="link-flyout">Courses</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.training-sessions.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.training-sessions.*') ? 'active' : '' }}" href="{{ route('admin.training-sessions.index') }}">
                <i class="ti ti-calendar-event"></i> <span class="link-text">Training Calendar</span>
                <span class="link-flyout">Training Calendar</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.training-enrollments.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.training-enrollments.*') ? 'active' : '' }}" href="{{ route('admin.training-enrollments.index') }}">
                <i class="ti ti-user-plus"></i> <span class="link-text">Training Assignment</span>
                <span class="link-flyout">Training Assignment</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.certifications.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.certifications.*') ? 'active' : '' }}" href="{{ route('admin.certifications.index') }}">
                <i class="ti ti-certificate"></i> <span class="link-text">Certifications</span>
                <span class="link-flyout">Certifications</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.assessments.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.assessments.*') || request()->routeIs('admin.assessment-results.*') ? 'active' : '' }}" href="{{ route('admin.assessments.index') }}">
                <i class="ti ti-clipboard-list"></i> <span class="link-text">Assessments</span>
                <span class="link-flyout">Assessments</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.learning-progress.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.learning-progress.*') ? 'active' : '' }}" href="{{ route('admin.learning-progress.index') }}">
                <i class="ti ti-chart-arrows-vertical"></i> <span class="link-text">Learning Progress</span>
                <span class="link-flyout">Learning Progress</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.trainers.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.trainers.*') ? 'active' : '' }}" href="{{ route('admin.trainers.index') }}">
                <i class="ti ti-presentation"></i> <span class="link-text">Trainer Management</span>
                <span class="link-flyout">Trainer Management</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.training-reports.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.training-reports.*') ? 'active' : '' }}" href="{{ route('admin.training-reports.index') }}">
                <i class="ti ti-report"></i> <span class="link-text">Training Reports</span>
                <span class="link-flyout">Training Reports</span>
            </a>
        </li>
    @endnavcan
</x-sidebar-group>

<x-sidebar-group title="Asset Management" icon="ti ti-devices" id="menu-assets" color="slate" :active="request()->routeIs('admin.assets.*') || request()->routeIs('admin.asset-returns.*') || request()->routeIs('admin.asset-history.*')">
    @navcan('admin.assets.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.assets.index') ? 'active' : '' }}" href="{{ route('admin.assets.index') }}">
                <i class="ti ti-devices"></i> <span class="link-text">Assign Assets</span>
                <span class="link-flyout">Assign Assets</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.assets.laptops')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.assets.laptops') ? 'active' : '' }}" href="{{ route('admin.assets.laptops') }}">
                <i class="ti ti-device-laptop"></i> <span class="link-text">Laptop</span>
                <span class="link-flyout">Laptop</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.assets.mobiles')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.assets.mobiles') ? 'active' : '' }}" href="{{ route('admin.assets.mobiles') }}">
                <i class="ti ti-device-mobile"></i> <span class="link-text">Mobile</span>
                <span class="link-flyout">Mobile</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.assets.id-cards')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.assets.id-cards') ? 'active' : '' }}" href="{{ route('admin.assets.id-cards') }}">
                <i class="ti ti-id"></i> <span class="link-text">ID Card</span>
                <span class="link-flyout">ID Card</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.assets.sim-cards')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.assets.sim-cards') ? 'active' : '' }}" href="{{ route('admin.assets.sim-cards') }}">
                <i class="ti ti-credit-card"></i> <span class="link-text">SIM Card</span>
                <span class="link-flyout">SIM Card</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.assets.uniforms')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.assets.uniforms') ? 'active' : '' }}" href="{{ route('admin.assets.uniforms') }}">
                <i class="ti ti-shirt"></i> <span class="link-text">Uniform</span>
                <span class="link-flyout">Uniform</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.asset-returns.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.asset-returns.*') ? 'active' : '' }}" href="{{ route('admin.asset-returns.index') }}">
                <i class="ti ti-arrow-back-up"></i> <span class="link-text">Asset Return</span>
                <span class="link-flyout">Asset Return</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.asset-history.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.asset-history.*') ? 'active' : '' }}" href="{{ route('admin.asset-history.index') }}">
                <i class="ti ti-history"></i> <span class="link-text">Asset History</span>
                <span class="link-flyout">Asset History</span>
            </a>
        </li>
    @endnavcan
</x-sidebar-group>

<x-sidebar-group title="Travel Management" icon="ti ti-plane" id="menu-travel" color="blue" :active="request()->routeIs('admin.travel-requests.*') || request()->routeIs('admin.travel-approval.*') || request()->routeIs('admin.travel-bookings.*') || request()->routeIs('admin.travel-expenses.*') || request()->routeIs('admin.travel-settlements.*') || request()->routeIs('admin.travel-reports.*')">
    @navcan('admin.travel-requests.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.travel-requests.*') ? 'active' : '' }}" href="{{ route('admin.travel-requests.index') }}">
                <i class="ti ti-plane"></i> <span class="link-text">Travel Request</span>
                <span class="link-flyout">Travel Request</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.travel-approval.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.travel-approval.*') ? 'active' : '' }}" href="{{ route('admin.travel-approval.index') }}">
                <i class="ti ti-thumb-up"></i> <span class="link-text">Approval</span>
                <span class="link-flyout">Approval</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.travel-bookings.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.travel-bookings.*') ? 'active' : '' }}" href="{{ route('admin.travel-bookings.index') }}">
                <i class="ti ti-ticket"></i> <span class="link-text">Booking</span>
                <span class="link-flyout">Booking</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.travel-expenses.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.travel-expenses.*') ? 'active' : '' }}" href="{{ route('admin.travel-expenses.index') }}">
                <i class="ti ti-receipt"></i> <span class="link-text">Travel Expense</span>
                <span class="link-flyout">Travel Expense</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.travel-settlements.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.travel-settlements.*') ? 'active' : '' }}" href="{{ route('admin.travel-settlements.index') }}">
                <i class="ti ti-wallet"></i> <span class="link-text">Travel Settlement</span>
                <span class="link-flyout">Travel Settlement</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.travel-reports.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.travel-reports.*') ? 'active' : '' }}" href="{{ route('admin.travel-reports.index') }}">
                <i class="ti ti-report"></i> <span class="link-text">Travel Reports</span>
                <span class="link-flyout">Travel Reports</span>
            </a>
        </li>
    @endnavcan
</x-sidebar-group>

<x-sidebar-group title="Expense Management" icon="ti ti-receipt-2" id="menu-expenses" color="orange" :active="request()->routeIs('admin.expense-claims.*') || request()->routeIs('admin.expense-categories.*') || request()->routeIs('admin.expense-approval.*') || request()->routeIs('admin.expense-reimbursements.*') || request()->routeIs('admin.expense-reports.*')">
    @navcan('admin.expense-claims.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.expense-claims.*') ? 'active' : '' }}" href="{{ route('admin.expense-claims.index') }}">
                <i class="ti ti-receipt-2"></i> <span class="link-text">Expense Claim</span>
                <span class="link-flyout">Expense Claim</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.expense-categories.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.expense-categories.*') ? 'active' : '' }}" href="{{ route('admin.expense-categories.index') }}">
                <i class="ti ti-tag"></i> <span class="link-text">Expense Categories</span>
                <span class="link-flyout">Expense Categories</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.expense-approval.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.expense-approval.*') ? 'active' : '' }}" href="{{ route('admin.expense-approval.index') }}">
                <i class="ti ti-thumb-up"></i> <span class="link-text">Approvals</span>
                <span class="link-flyout">Approvals</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.expense-reimbursements.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.expense-reimbursements.*') ? 'active' : '' }}" href="{{ route('admin.expense-reimbursements.index') }}">
                <i class="ti ti-cash-banknote"></i> <span class="link-text">Reimbursements</span>
                <span class="link-flyout">Reimbursements</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.expense-reports.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.expense-reports.*') ? 'active' : '' }}" href="{{ route('admin.expense-reports.index') }}">
                <i class="ti ti-report-money"></i> <span class="link-text">Expense Reports</span>
                <span class="link-flyout">Expense Reports</span>
            </a>
        </li>
    @endnavcan
</x-sidebar-group>

<x-sidebar-group title="Employee Documents" icon="ti ti-file-text" id="menu-documents" color="purple" :active="request()->routeIs('admin.documents.*') || request()->routeIs('admin.document-expiry.*')">
    @navcan('admin.documents.offer-letters')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.documents.offer-letters') ? 'active' : '' }}" href="{{ route('admin.documents.offer-letters') }}">
                <i class="ti ti-file-text"></i> <span class="link-text">Offer Letter</span>
                <span class="link-flyout">Offer Letter</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.documents.appointment-letters')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.documents.appointment-letters') ? 'active' : '' }}" href="{{ route('admin.documents.appointment-letters') }}">
                <i class="ti ti-file-certificate"></i> <span class="link-text">Appointment Letter</span>
                <span class="link-flyout">Appointment Letter</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.documents.contracts')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.documents.contracts') ? 'active' : '' }}" href="{{ route('admin.documents.contracts') }}">
                <i class="ti ti-file-text"></i> <span class="link-text">Contract</span>
                <span class="link-flyout">Contract</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.documents.pan')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.documents.pan') ? 'active' : '' }}" href="{{ route('admin.documents.pan') }}">
                <i class="ti ti-id"></i> <span class="link-text">PAN</span>
                <span class="link-flyout">PAN</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.documents.aadhar')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.documents.aadhar') ? 'active' : '' }}" href="{{ route('admin.documents.aadhar') }}">
                <i class="ti ti-id"></i> <span class="link-text">Aadhar</span>
                <span class="link-flyout">Aadhar</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.documents.passport')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.documents.passport') ? 'active' : '' }}" href="{{ route('admin.documents.passport') }}">
                <i class="ti ti-passport"></i> <span class="link-text">Passport</span>
                <span class="link-flyout">Passport</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.documents.visa')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.documents.visa') ? 'active' : '' }}" href="{{ route('admin.documents.visa') }}">
                <i class="ti ti-stamp"></i> <span class="link-text">Visa</span>
                <span class="link-flyout">Visa</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.documents.certificates')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.documents.certificates') ? 'active' : '' }}" href="{{ route('admin.documents.certificates') }}">
                <i class="ti ti-certificate"></i> <span class="link-text">Certificates</span>
                <span class="link-flyout">Certificates</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.documents.experience-letters')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.documents.experience-letters') ? 'active' : '' }}" href="{{ route('admin.documents.experience-letters') }}">
                <i class="ti ti-file-text"></i> <span class="link-text">Experience Letter</span>
                <span class="link-flyout">Experience Letter</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.documents.salary-slips')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.documents.salary-slips') ? 'active' : '' }}" href="{{ route('admin.documents.salary-slips') }}">
                <i class="ti ti-receipt"></i> <span class="link-text">Salary Slip</span>
                <span class="link-flyout">Salary Slip</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.document-expiry.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.document-expiry.*') ? 'active' : '' }}" href="{{ route('admin.document-expiry.index') }}">
                <i class="ti ti-calendar-due"></i> <span class="link-text">Document Expiry</span>
                <span class="link-flyout">Document Expiry</span>
            </a>
        </li>
    @endnavcan
</x-sidebar-group>

<x-sidebar-group title="Employee Self Service" icon="ti ti-heart" id="menu-ess" color="pink" :active="request()->routeIs('admin.my-*')">
    @navcan('admin.my-profile.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.my-profile.*') ? 'active' : '' }}" href="{{ route('admin.my-profile.index') }}">
                <i class="ti ti-user"></i> <span class="link-text">My Profile</span>
                <span class="link-flyout">My Profile</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.my-attendance.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.my-attendance.*') ? 'active' : '' }}" href="{{ route('admin.my-attendance.index') }}">
                <i class="ti ti-clock"></i> <span class="link-text">My Attendance</span>
                <span class="link-flyout">My Attendance</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.my-leave.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.my-leave.*') ? 'active' : '' }}" href="{{ route('admin.my-leave.index') }}">
                <i class="ti ti-calendar-event"></i> <span class="link-text">My Leave</span>
                <span class="link-flyout">My Leave</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.my-payslips.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.my-payslips.*') ? 'active' : '' }}" href="{{ route('admin.my-payslips.index') }}">
                <i class="ti ti-file-invoice"></i> <span class="link-text">My Payslips</span>
                <span class="link-flyout">My Payslips</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.my-documents.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.my-documents.*') ? 'active' : '' }}" href="{{ route('admin.my-documents.index') }}">
                <i class="ti ti-file-text"></i> <span class="link-text">My Documents</span>
                <span class="link-flyout">My Documents</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.my-assets.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.my-assets.*') ? 'active' : '' }}" href="{{ route('admin.my-assets.index') }}">
                <i class="ti ti-device-laptop"></i> <span class="link-text">My Assets</span>
                <span class="link-flyout">My Assets</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.my-training.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.my-training.*') ? 'active' : '' }}" href="{{ route('admin.my-training.index') }}">
                <i class="ti ti-school"></i> <span class="link-text">My Training</span>
                <span class="link-flyout">My Training</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.my-performance.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.my-performance.*') ? 'active' : '' }}" href="{{ route('admin.my-performance.index') }}">
                <i class="ti ti-chart-bar"></i> <span class="link-text">My Performance</span>
                <span class="link-flyout">My Performance</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.my-expenses.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.my-expenses.*') ? 'active' : '' }}" href="{{ route('admin.my-expenses.index') }}">
                <i class="ti ti-receipt-2"></i> <span class="link-text">My Expenses</span>
                <span class="link-flyout">My Expenses</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.my-travel.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.my-travel.*') ? 'active' : '' }}" href="{{ route('admin.my-travel.index') }}">
                <i class="ti ti-plane"></i> <span class="link-text">My Travel</span>
                <span class="link-flyout">My Travel</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.my-requests.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.my-requests.*') ? 'active' : '' }}" href="{{ route('admin.my-requests.index') }}">
                <i class="ti ti-list-check"></i> <span class="link-text">My Requests</span>
                <span class="link-flyout">My Requests</span>
            </a>
        </li>
    @endnavcan
</x-sidebar-group>

<x-sidebar-group title="Manager Self Service" icon="ti ti-users" id="menu-mss" color="green" :active="request()->routeIs('admin.team-*')">
    @navcan('admin.team-attendance.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.team-attendance.*') ? 'active' : '' }}" href="{{ route('admin.team-attendance.index') }}">
                <i class="ti ti-clock"></i> <span class="link-text">Team Attendance</span>
                <span class="link-flyout">Team Attendance</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.team-leave.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.team-leave.*') ? 'active' : '' }}" href="{{ route('admin.team-leave.index') }}">
                <i class="ti ti-calendar-event"></i> <span class="link-text">Team Leave</span>
                <span class="link-flyout">Team Leave</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.team-approvals.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.team-approvals.*') ? 'active' : '' }}" href="{{ route('admin.team-approvals.index') }}">
                <i class="ti ti-thumb-up"></i> <span class="link-text">Approvals</span>
                <span class="link-flyout">Approvals</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.team-performance.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.team-performance.*') ? 'active' : '' }}" href="{{ route('admin.team-performance.index') }}">
                <i class="ti ti-chart-bar"></i> <span class="link-text">Performance Review</span>
                <span class="link-flyout">Performance Review</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.team-goals.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.team-goals.*') ? 'active' : '' }}" href="{{ route('admin.team-goals.index') }}">
                <i class="ti ti-target"></i> <span class="link-text">Goals</span>
                <span class="link-flyout">Goals</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.team-documents.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.team-documents.*') ? 'active' : '' }}" href="{{ route('admin.team-documents.index') }}">
                <i class="ti ti-file-text"></i> <span class="link-text">Employee Documents</span>
                <span class="link-flyout">Employee Documents</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.team-reports.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.team-reports.*') ? 'active' : '' }}" href="{{ route('admin.team-reports.index') }}">
                <i class="ti ti-report"></i> <span class="link-text">Reports</span>
                <span class="link-flyout">Reports</span>
            </a>
        </li>
    @endnavcan
</x-sidebar-group>

<x-sidebar-group title="Separation" icon="ti ti-logout" id="menu-separation" color="red" :active="request()->routeIs('admin.resignations.*') || request()->routeIs('admin.notice-period.*') || request()->routeIs('admin.clearances.*') || request()->routeIs('admin.exit-interviews.*') || request()->routeIs('admin.separation-asset-returns.*') || request()->routeIs('admin.final-settlements.*') || request()->routeIs('admin.separation-documents.*')">
    @navcan('admin.resignations.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.resignations.*') ? 'active' : '' }}" href="{{ route('admin.resignations.index') }}">
                <i class="ti ti-logout"></i> <span class="link-text">Resignation</span>
                <span class="link-flyout">Resignation</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.notice-period.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.notice-period.*') ? 'active' : '' }}" href="{{ route('admin.notice-period.index') }}">
                <i class="ti ti-clock"></i> <span class="link-text">Notice Period</span>
                <span class="link-flyout">Notice Period</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.clearances.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.clearances.*') ? 'active' : '' }}" href="{{ route('admin.clearances.index') }}">
                <i class="ti ti-clipboard-check"></i> <span class="link-text">Clearance</span>
                <span class="link-flyout">Clearance</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.exit-interviews.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.exit-interviews.*') ? 'active' : '' }}" href="{{ route('admin.exit-interviews.index') }}">
                <i class="ti ti-message-circle"></i> <span class="link-text">Exit Interview</span>
                <span class="link-flyout">Exit Interview</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.separation-asset-returns.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.separation-asset-returns.*') ? 'active' : '' }}" href="{{ route('admin.separation-asset-returns.index') }}">
                <i class="ti ti-arrow-back-up"></i> <span class="link-text">Asset Return</span>
                <span class="link-flyout">Asset Return</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.final-settlements.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.final-settlements.*') ? 'active' : '' }}" href="{{ route('admin.final-settlements.index') }}">
                <i class="ti ti-cash-banknote"></i> <span class="link-text">Final Settlement</span>
                <span class="link-flyout">Final Settlement</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.separation-documents.experience-letter')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.separation-documents.experience-letter') ? 'active' : '' }}" href="{{ route('admin.separation-documents.experience-letter') }}">
                <i class="ti ti-file-text"></i> <span class="link-text">Experience Letter</span>
                <span class="link-flyout">Experience Letter</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.separation-documents.relieving-letter')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.separation-documents.relieving-letter') ? 'active' : '' }}" href="{{ route('admin.separation-documents.relieving-letter') }}">
                <i class="ti ti-file-certificate"></i> <span class="link-text">Relieving Letter</span>
                <span class="link-flyout">Relieving Letter</span>
            </a>
        </li>
    @endnavcan
</x-sidebar-group>

<x-sidebar-group title="Reports" icon="ti ti-report-analytics" id="menu-hrm-reports" color="teal" :active="request()->routeIs('admin.employee-reports.*') || request()->routeIs('admin.attendance.reports') || request()->routeIs('admin.leave-reports.*') || request()->routeIs('admin.payroll-reports.*') || request()->routeIs('admin.performance-reports.*') || request()->routeIs('admin.recruitment-reports.*') || request()->routeIs('admin.training-reports.*') || request()->routeIs('admin.attrition-reports.*') || request()->routeIs('admin.diversity-reports.*') || request()->routeIs('admin.compliance-reports.*')">
    @navcan('admin.employee-reports.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.employee-reports.*') ? 'active' : '' }}" href="{{ route('admin.employee-reports.index') }}">
                <i class="ti ti-users"></i> <span class="link-text">Employee Reports</span>
                <span class="link-flyout">Employee Reports</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.attendance.reports')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.attendance.reports') ? 'active' : '' }}" href="{{ route('admin.attendance.reports') }}">
                <i class="ti ti-clock"></i> <span class="link-text">Attendance Reports</span>
                <span class="link-flyout">Attendance Reports</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.leave-reports.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.leave-reports.*') ? 'active' : '' }}" href="{{ route('admin.leave-reports.index') }}">
                <i class="ti ti-calendar-event"></i> <span class="link-text">Leave Reports</span>
                <span class="link-flyout">Leave Reports</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.payroll-reports.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.payroll-reports.*') ? 'active' : '' }}" href="{{ route('admin.payroll-reports.index') }}">
                <i class="ti ti-cash"></i> <span class="link-text">Payroll Reports</span>
                <span class="link-flyout">Payroll Reports</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.performance-reports.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.performance-reports.*') ? 'active' : '' }}" href="{{ route('admin.performance-reports.index') }}">
                <i class="ti ti-chart-bar"></i> <span class="link-text">Performance Reports</span>
                <span class="link-flyout">Performance Reports</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.recruitment-reports.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.recruitment-reports.*') ? 'active' : '' }}" href="{{ route('admin.recruitment-reports.index') }}">
                <i class="ti ti-briefcase"></i> <span class="link-text">Recruitment Reports</span>
                <span class="link-flyout">Recruitment Reports</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.training-reports.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.training-reports.*') ? 'active' : '' }}" href="{{ route('admin.training-reports.index') }}">
                <i class="ti ti-school"></i> <span class="link-text">Training Reports</span>
                <span class="link-flyout">Training Reports</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.attrition-reports.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.attrition-reports.*') ? 'active' : '' }}" href="{{ route('admin.attrition-reports.index') }}">
                <i class="ti ti-trending-down"></i> <span class="link-text">Attrition Reports</span>
                <span class="link-flyout">Attrition Reports</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.diversity-reports.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.diversity-reports.*') ? 'active' : '' }}" href="{{ route('admin.diversity-reports.index') }}">
                <i class="ti ti-users"></i> <span class="link-text">Diversity Reports</span>
                <span class="link-flyout">Diversity Reports</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.compliance-reports.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.compliance-reports.*') ? 'active' : '' }}" href="{{ route('admin.compliance-reports.index') }}">
                <i class="ti ti-shield-check"></i> <span class="link-text">Compliance Reports</span>
                <span class="link-flyout">Compliance Reports</span>
            </a>
        </li>
    @endnavcan
</x-sidebar-group>

<x-sidebar-group title="Approvals" icon="ti ti-checkbox" id="menu-approvals" color="green" :active="request()->routeIs('admin.leave-requests.approval') || request()->routeIs('admin.attendance-approval.*') || request()->routeIs('admin.expense-approval.*') || request()->routeIs('admin.travel-approval.*') || request()->routeIs('admin.loan-requests.*') || request()->routeIs('admin.recruitment-approval.*') || request()->routeIs('admin.promotion-requests.*') || request()->routeIs('admin.transfer-requests.*') || request()->routeIs('admin.resignation-approval.*')">
    @navcan('admin.leave-requests.approval')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.leave-requests.approval') ? 'active' : '' }}" href="{{ route('admin.leave-requests.approval') }}">
                <i class="ti ti-calendar-event"></i> <span class="link-text">Leave Approval</span>
                <span class="link-flyout">Leave Approval</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.attendance-approval.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.attendance-approval.*') ? 'active' : '' }}" href="{{ route('admin.attendance-approval.index') }}">
                <i class="ti ti-clock"></i> <span class="link-text">Attendance Approval</span>
                <span class="link-flyout">Attendance Approval</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.expense-approval.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.expense-approval.*') ? 'active' : '' }}" href="{{ route('admin.expense-approval.index') }}">
                <i class="ti ti-receipt-2"></i> <span class="link-text">Expense Approval</span>
                <span class="link-flyout">Expense Approval</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.travel-approval.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.travel-approval.*') ? 'active' : '' }}" href="{{ route('admin.travel-approval.index') }}">
                <i class="ti ti-plane"></i> <span class="link-text">Travel Approval</span>
                <span class="link-flyout">Travel Approval</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.loan-requests.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.loan-requests.*') ? 'active' : '' }}" href="{{ route('admin.loan-requests.index') }}">
                <i class="ti ti-cash-banknote"></i> <span class="link-text">Loan Approval</span>
                <span class="link-flyout">Loan Approval</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.recruitment-approval.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.recruitment-approval.*') ? 'active' : '' }}" href="{{ route('admin.recruitment-approval.index') }}">
                <i class="ti ti-briefcase"></i> <span class="link-text">Recruitment Approval</span>
                <span class="link-flyout">Recruitment Approval</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.promotion-requests.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.promotion-requests.*') ? 'active' : '' }}" href="{{ route('admin.promotion-requests.index') }}">
                <i class="ti ti-arrow-up-circle"></i> <span class="link-text">Promotion Approval</span>
                <span class="link-flyout">Promotion Approval</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.transfer-requests.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.transfer-requests.*') ? 'active' : '' }}" href="{{ route('admin.transfer-requests.index') }}">
                <i class="ti ti-exchange"></i> <span class="link-text">Transfer Approval</span>
                <span class="link-flyout">Transfer Approval</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.resignation-approval.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.resignation-approval.*') ? 'active' : '' }}" href="{{ route('admin.resignation-approval.index') }}">
                <i class="ti ti-logout"></i> <span class="link-text">Resignation Approval</span>
                <span class="link-flyout">Resignation Approval</span>
            </a>
        </li>
    @endnavcan
</x-sidebar-group>

<x-sidebar-group title="Settings" icon="ti ti-settings" id="menu-hrm-settings" color="slate" :active="request()->routeIs('admin.hr-policies.*') || request()->routeIs('admin.working-hours.*') || request()->routeIs('admin.holidays.*') || request()->routeIs('admin.leave-policies.*') || request()->routeIs('admin.attendance-rules.*') || request()->routeIs('admin.salary-components.*') || request()->routeIs('admin.shifts.*') || request()->routeIs('admin.approval-rules.*') || request()->routeIs('admin.approval-levels.*') || request()->routeIs('admin.email-templates.*') || request()->routeIs('admin.sms-templates.*') || request()->routeIs('admin.document-templates.*') || request()->routeIs('admin.notification-rules.*')">
    @navcan('admin.hr-policies.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.hr-policies.*') ? 'active' : '' }}" href="{{ route('admin.hr-policies.index') }}">
                <i class="ti ti-file-text"></i> <span class="link-text">HR Policies</span>
                <span class="link-flyout">HR Policies</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.working-hours.edit')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.working-hours.*') ? 'active' : '' }}" href="{{ route('admin.working-hours.edit') }}">
                <i class="ti ti-calendar-stats"></i> <span class="link-text">Working Days</span>
                <span class="link-flyout">Working Days</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.holidays.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.holidays.*') ? 'active' : '' }}" href="{{ route('admin.holidays.index') }}">
                <i class="ti ti-calendar-star"></i> <span class="link-text">Holiday Calendar</span>
                <span class="link-flyout">Holiday Calendar</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.leave-policies.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.leave-policies.*') ? 'active' : '' }}" href="{{ route('admin.leave-policies.index') }}">
                <i class="ti ti-file-certificate"></i> <span class="link-text">Leave Policy</span>
                <span class="link-flyout">Leave Policy</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.attendance-rules.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.attendance-rules.*') ? 'active' : '' }}" href="{{ route('admin.attendance-rules.index') }}">
                <i class="ti ti-clock-check"></i> <span class="link-text">Attendance Rules</span>
                <span class="link-flyout">Attendance Rules</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.salary-components.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.salary-components.*') ? 'active' : '' }}" href="{{ route('admin.salary-components.index') }}">
                <i class="ti ti-cash"></i> <span class="link-text">Payroll Rules</span>
                <span class="link-flyout">Payroll Rules</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.shifts.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.shifts.*') ? 'active' : '' }}" href="{{ route('admin.shifts.index') }}">
                <i class="ti ti-clock"></i> <span class="link-text">Shift Rules</span>
                <span class="link-flyout">Shift Rules</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.approval-levels.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.approval-levels.*') ? 'active' : '' }}" href="{{ route('admin.approval-levels.index') }}">
                <i class="ti ti-hierarchy"></i> <span class="link-text">Approval Levels</span>
                <span class="link-flyout">Approval Levels</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.approval-rules.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.approval-rules.*') ? 'active' : '' }}" href="{{ route('admin.approval-rules.index') }}">
                <i class="ti ti-checkbox"></i> <span class="link-text">Approval Workflow</span>
                <span class="link-flyout">Approval Workflow</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.email-templates.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.email-templates.*') ? 'active' : '' }}" href="{{ route('admin.email-templates.index') }}">
                <i class="ti ti-mail"></i> <span class="link-text">Email Templates</span>
                <span class="link-flyout">Email Templates</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.sms-templates.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.sms-templates.*') ? 'active' : '' }}" href="{{ route('admin.sms-templates.index') }}">
                <i class="ti ti-message-2"></i> <span class="link-text">SMS Templates</span>
                <span class="link-flyout">SMS Templates</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.document-templates.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.document-templates.*') ? 'active' : '' }}" href="{{ route('admin.document-templates.index') }}">
                <i class="ti ti-file-text"></i> <span class="link-text">Document Templates</span>
                <span class="link-flyout">Document Templates</span>
            </a>
        </li>
    @endnavcan
    @navcan('admin.notification-rules.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.notification-rules.*') ? 'active' : '' }}" href="{{ route('admin.notification-rules.index') }}">
                <i class="ti ti-bell-cog"></i> <span class="link-text">Notification Settings</span>
                <span class="link-flyout">Notification Settings</span>
            </a>
        </li>
    @endnavcan
</x-sidebar-group>
