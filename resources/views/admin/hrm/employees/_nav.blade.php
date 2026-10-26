<div class="nav flex-column nav-pills">
    <a class="nav-link {{ request()->routeIs('admin.employees.show') ? 'active' : '' }}" href="{{ route('admin.employees.show', $employee) }}"><i class="ti ti-user font-14 me-1"></i> Profile</a>
    <a class="nav-link {{ request()->routeIs('admin.employees.timeline') ? 'active' : '' }}" href="{{ route('admin.employees.timeline', $employee) }}"><i class="ti ti-clock font-14 me-1"></i> Timeline</a>
    <a class="nav-link {{ request()->routeIs('admin.employees.documents.index') ? 'active' : '' }}" href="{{ route('admin.employees.documents.index', $employee) }}"><i class="ti ti-file-text font-14 me-1"></i> Documents</a>
    <a class="nav-link {{ request()->routeIs('admin.employees.assets.index') ? 'active' : '' }}" href="{{ route('admin.employees.assets.index', $employee) }}"><i class="ti ti-devices font-14 me-1"></i> Assets</a>
    <a class="nav-link {{ request()->routeIs('admin.employees.family-members.index') ? 'active' : '' }}" href="{{ route('admin.employees.family-members.index', $employee) }}"><i class="ti ti-heart font-14 me-1"></i> Family</a>
    <a class="nav-link {{ request()->routeIs('admin.employees.educations.index') ? 'active' : '' }}" href="{{ route('admin.employees.educations.index', $employee) }}"><i class="ti ti-school font-14 me-1"></i> Education</a>
    <a class="nav-link {{ request()->routeIs('admin.employees.experiences.index') ? 'active' : '' }}" href="{{ route('admin.employees.experiences.index', $employee) }}"><i class="ti ti-briefcase font-14 me-1"></i> Experience</a>
    <a class="nav-link {{ request()->routeIs('admin.employees.skills.index') ? 'active' : '' }}" href="{{ route('admin.employees.skills.index', $employee) }}"><i class="ti ti-bulb font-14 me-1"></i> Skills</a>
    <a class="nav-link {{ request()->routeIs('admin.employees.salaries.index') ? 'active' : '' }}" href="{{ route('admin.employees.salaries.index', $employee) }}"><i class="ti ti-cash font-14 me-1"></i> Salary</a>
    <a class="nav-link {{ request()->routeIs('admin.employees.promotions.index') ? 'active' : '' }}" href="{{ route('admin.employees.promotions.index', $employee) }}"><i class="ti ti-arrow-up-circle font-14 me-1"></i> Promotion</a>
    <a class="nav-link {{ request()->routeIs('admin.employees.transfers.index') ? 'active' : '' }}" href="{{ route('admin.employees.transfers.index', $employee) }}"><i class="ti ti-exchange font-14 me-1"></i> Transfer</a>
    <a class="nav-link {{ request()->routeIs('admin.employees.onboarding.index') ? 'active' : '' }}" href="{{ route('admin.employees.onboarding.index', $employee) }}"><i class="ti ti-list-check font-14 me-1"></i> Onboarding</a>
    <a class="nav-link {{ request()->routeIs('admin.employees.history') ? 'active' : '' }}" href="{{ route('admin.employees.history', $employee) }}"><i class="ti ti-history font-14 me-1"></i> History</a>
</div>
