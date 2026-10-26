<ul class="nav flex-column">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
            <i class="ti ti-smart-home"></i> <span class="link-text">Dashboard</span>
            <span class="link-flyout">Dashboard</span>
        </a>
    </li>
</ul>

@if (Route::has('admin.users.index') || Route::has('admin.roles.index'))
    <x-sidebar-group title="User Management" icon="ti ti-shield-lock" id="menu-user-management" color="blue" :active="request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*')">
        @if (Route::has('admin.users.index') && \App\Support\SidebarAccess::allows('admin.users.index'))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                    <i class="ti ti-users"></i> <span class="link-text">Users</span>
                    <span class="link-flyout">Users</span>
                </a>
            </li>
        @endif
        @if (Route::has('admin.roles.index') && \App\Support\SidebarAccess::allows('admin.roles.index'))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}" href="{{ route('admin.roles.index') }}">
                    <i class="ti ti-shield-lock"></i> <span class="link-text">Roles &amp; Permissions</span>
                    <span class="link-flyout">Roles &amp; Permissions</span>
                </a>
            </li>
        @endif
    </x-sidebar-group>
@endif

@if (Route::has('admin.general-settings.edit') || Route::has('admin.branches.index') || Route::has('admin.departments.index') || Route::has('admin.working-hours.edit') || Route::has('admin.holidays.index'))
    <x-sidebar-group title="Organization" icon="ti ti-building" id="menu-organization" color="orange" :active="request()->routeIs('admin.general-settings.*') || request()->routeIs('admin.mail-setup.*') || request()->routeIs('admin.branches.*') || request()->routeIs('admin.departments.*') || request()->routeIs('admin.working-hours.*') || request()->routeIs('admin.holidays.*')">
        @if (Route::has('admin.general-settings.edit') && \App\Support\SidebarAccess::allows('admin.general-settings.edit'))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.general-settings.*') ? 'active' : '' }}" href="{{ route('admin.general-settings.edit') }}">
                    <i class="ti ti-building-store"></i> <span class="link-text">General</span>
                    <span class="link-flyout">General</span>
                </a>
            </li>
        @endif
        @if (Route::has('admin.mail-setup.show') && \App\Support\SidebarAccess::allows('admin.mail-setup.show'))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.mail-setup.*') ? 'active' : '' }}" href="{{ route('admin.mail-setup.show') }}">
                    <i class="ti ti-mail"></i> <span class="link-text">Mail Setup</span>
                    <span class="link-flyout">Mail Setup</span>
                </a>
            </li>
        @endif
        @if (Route::has('admin.branches.index') && \App\Support\SidebarAccess::allows('admin.branches.index'))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.branches.*') ? 'active' : '' }}" href="{{ route('admin.branches.index') }}">
                    <i class="ti ti-building"></i> <span class="link-text">Branches</span>
                    <span class="link-flyout">Branches</span>
                </a>
            </li>
        @endif
        @if (Route::has('admin.departments.index') && \App\Support\SidebarAccess::allows('admin.departments.index'))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}" href="{{ route('admin.departments.index') }}">
                    <i class="ti ti-rectangle-vertical"></i> <span class="link-text">Departments</span>
                    <span class="link-flyout">Departments</span>
                </a>
            </li>
        @endif
        @if (Route::has('admin.working-hours.edit') && \App\Support\SidebarAccess::allows('admin.working-hours.edit'))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.working-hours.*') ? 'active' : '' }}" href="{{ route('admin.working-hours.edit') }}">
                    <i class="ti ti-clock"></i> <span class="link-text">Working Hours</span>
                    <span class="link-flyout">Working Hours</span>
                </a>
            </li>
        @endif
        @if (Route::has('admin.holidays.index') && \App\Support\SidebarAccess::allows('admin.holidays.index'))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.holidays.*') ? 'active' : '' }}" href="{{ route('admin.holidays.index') }}">
                    <i class="ti ti-calendar-event"></i> <span class="link-text">Holiday Calendar</span>
                    <span class="link-flyout">Holiday Calendar</span>
                </a>
            </li>
        @endif
    </x-sidebar-group>
@endif

@if (Route::has('admin.workflows.index') || Route::has('admin.approval-rules.index') || Route::has('admin.escalation-rules.index') || Route::has('admin.automation-rules.index') || Route::has('admin.notification-rules.index') || Route::has('admin.email-templates.index') || Route::has('admin.sms-templates.index') || Route::has('admin.whatsapp-templates.index'))
    <x-sidebar-group title="Workflow & Approvals" icon="ti ti-settings-automation" id="menu-workflow-approvals" color="purple" :active="request()->routeIs('admin.workflows.*') || request()->routeIs('admin.approval-rules.*') || request()->routeIs('admin.escalation-rules.*') || request()->routeIs('admin.automation-rules.*') || request()->routeIs('admin.notification-rules.*') || request()->routeIs('admin.email-templates.*') || request()->routeIs('admin.sms-templates.*') || request()->routeIs('admin.whatsapp-templates.*')">
        @if (Route::has('admin.workflows.index') && \App\Support\SidebarAccess::allows('admin.workflows.index'))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.workflows.*') ? 'active' : '' }}" href="{{ route('admin.workflows.index') }}">
                    <i class="ti ti-git-branch"></i> <span class="link-text">Workflow Designer</span>
                    <span class="link-flyout">Workflow Designer</span>
                </a>
            </li>
        @endif
        @if (Route::has('admin.approval-rules.index') && \App\Support\SidebarAccess::allows('admin.approval-rules.index'))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.approval-rules.*') ? 'active' : '' }}" href="{{ route('admin.approval-rules.index') }}">
                    <i class="ti ti-list-check"></i> <span class="link-text">Approval Matrix</span>
                    <span class="link-flyout">Approval Matrix</span>
                </a>
            </li>
        @endif
        @if (Route::has('admin.escalation-rules.index') && \App\Support\SidebarAccess::allows('admin.escalation-rules.index'))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.escalation-rules.*') ? 'active' : '' }}" href="{{ route('admin.escalation-rules.index') }}">
                    <i class="ti ti-alarm"></i> <span class="link-text">Escalation Rules</span>
                    <span class="link-flyout">Escalation Rules</span>
                </a>
            </li>
        @endif
        @if (Route::has('admin.automation-rules.index') && \App\Support\SidebarAccess::allows('admin.automation-rules.index'))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.automation-rules.*') ? 'active' : '' }}" href="{{ route('admin.automation-rules.index') }}">
                    <i class="ti ti-bolt"></i> <span class="link-text">Automation Rules</span>
                    <span class="link-flyout">Automation Rules</span>
                </a>
            </li>
        @endif
        @if (Route::has('admin.notification-rules.index') && \App\Support\SidebarAccess::allows('admin.notification-rules.index'))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.notification-rules.*') ? 'active' : '' }}" href="{{ route('admin.notification-rules.index') }}">
                    <i class="ti ti-bell"></i> <span class="link-text">Notifications</span>
                    <span class="link-flyout">Notifications</span>
                </a>
            </li>
        @endif
        @if (Route::has('admin.email-templates.index') && \App\Support\SidebarAccess::allows('admin.email-templates.index'))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.email-templates.*') ? 'active' : '' }}" href="{{ route('admin.email-templates.index') }}">
                    <i class="ti ti-mail"></i> <span class="link-text">Email Templates</span>
                    <span class="link-flyout">Email Templates</span>
                </a>
            </li>
        @endif
        @if (Route::has('admin.sms-templates.index') && \App\Support\SidebarAccess::allows('admin.sms-templates.index'))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.sms-templates.*') ? 'active' : '' }}" href="{{ route('admin.sms-templates.index') }}">
                    <i class="ti ti-message"></i> <span class="link-text">SMS Templates</span>
                    <span class="link-flyout">SMS Templates</span>
                </a>
            </li>
        @endif
        @if (Route::has('admin.whatsapp-templates.index') && \App\Support\SidebarAccess::allows('admin.whatsapp-templates.index'))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.whatsapp-templates.*') ? 'active' : '' }}" href="{{ route('admin.whatsapp-templates.index') }}">
                    <i class="ti ti-brand-whatsapp"></i> <span class="link-text">WhatsApp Templates</span>
                    <span class="link-flyout">WhatsApp Templates</span>
                </a>
            </li>
        @endif
    </x-sidebar-group>
@endif
