<ul class="nav flex-column">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.sales.dashboard') ? 'active' : '' }}" href="{{ route('admin.sales.dashboard') }}">
            <i class="ti ti-dashboard"></i> <span class="link-text">Dashboard</span>
            <span class="link-flyout">Dashboard</span>
        </a>
    </li>
</ul>

<x-sidebar-group title="Overview" icon="ti ti-layout-grid" id="menu-sales-overview" color="indigo" :active="request()->routeIs('admin.sales.dashboard')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.sales.dashboard') ? 'active' : '' }}" href="{{ route('admin.sales.dashboard') }}">
            <i class="ti ti-dashboard"></i> <span class="link-text">Sales Dashboard</span>
            <span class="link-flyout">Sales Dashboard</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="Customers & Accounts" icon="ti ti-building" id="menu-sales-customers" color="teal" :active="request()->routeIs('admin.sales.customers.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.sales.customers.*') ? 'active' : '' }}" href="{{ route('admin.sales.customers.index') }}">
            <i class="ti ti-building"></i> <span class="link-text">Customers & Accounts</span>
            <span class="link-flyout">Customers & Accounts</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="Products & Pricing" icon="ti ti-tag" id="menu-sales-pricing" color="violet" :active="request()->routeIs('admin.sales.pricing.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.sales.pricing.*') ? 'active' : '' }}" href="{{ route('admin.sales.pricing.index') }}">
            <i class="ti ti-tag"></i> <span class="link-text">Products & Pricing</span>
            <span class="link-flyout">Products & Pricing</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="Quotations & Orders" icon="ti ti-file-text" id="menu-sales-quotations" color="sky" :active="request()->routeIs('admin.sales.quotations.*') || request()->routeIs('admin.sales.quotation-templates.*') || request()->routeIs('admin.sales.orders.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.sales.quotations.*') ? 'active' : '' }}" href="{{ route('admin.sales.quotations.index') }}">
            <i class="ti ti-file-text"></i> <span class="link-text">Quotations</span>
            <span class="link-flyout">Quotations</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.sales.quotation-templates.*') ? 'active' : '' }}" href="{{ route('admin.sales.quotation-templates.index') }}">
            <i class="ti ti-copy"></i> <span class="link-text">Quotation Templates</span>
            <span class="link-flyout">Quotation Templates</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.sales.orders.*') ? 'active' : '' }}" href="{{ route('admin.sales.orders.index') }}">
            <i class="ti ti-shopping-cart"></i> <span class="link-text">Sales Orders</span>
            <span class="link-flyout">Sales Orders</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="Contracts & Agreements" icon="ti ti-file-text" id="menu-sales-contracts" color="amber" :active="request()->routeIs('admin.sales.contracts.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.sales.contracts.*') ? 'active' : '' }}" href="{{ route('admin.sales.contracts.index') }}">
            <i class="ti ti-file-text"></i> <span class="link-text">Contracts & Agreements</span>
            <span class="link-flyout">Contracts & Agreements</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="Fulfilment & Delivery" icon="ti ti-truck-delivery" id="menu-sales-fulfilment" color="rose" :active="request()->routeIs('admin.sales.fulfilment.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.sales.fulfilment.*') ? 'active' : '' }}" href="{{ route('admin.sales.fulfilment.index') }}">
            <i class="ti ti-truck-delivery"></i> <span class="link-text">Fulfilment & Delivery</span>
            <span class="link-flyout">Fulfilment & Delivery</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="Invoices & Payments" icon="ti ti-receipt" id="menu-sales-invoices" color="cyan" :active="request()->routeIs('admin.sales.invoices.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.sales.invoices.*') ? 'active' : '' }}" href="{{ route('admin.sales.invoices.index') }}">
            <i class="ti ti-receipt"></i> <span class="link-text">Invoices & Payments</span>
            <span class="link-flyout">Invoices & Payments</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="Sales Activities" icon="ti ti-list-check" id="menu-sales-activities" color="teal" :active="request()->routeIs('admin.sales.activities.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.sales.activities.*') ? 'active' : '' }}" href="{{ route('admin.sales.activities.index') }}">
            <i class="ti ti-list-check"></i> <span class="link-text">Sales Activities</span>
            <span class="link-flyout">Sales Activities</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="Sales Team & Targets" icon="ti ti-target" id="menu-sales-team-targets" color="indigo" :active="request()->routeIs('admin.sales.team-targets.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.sales.team-targets.*') ? 'active' : '' }}" href="{{ route('admin.sales.team-targets.index') }}">
            <i class="ti ti-target"></i> <span class="link-text">Sales Team & Targets</span>
            <span class="link-flyout">Sales Team & Targets</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="Returns & Complaints" icon="ti ti-rotate" id="menu-sales-returns" color="rose" :active="request()->routeIs('admin.sales.returns-complaints.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.sales.returns-complaints.*') ? 'active' : '' }}" href="{{ route('admin.sales.returns-complaints.index') }}">
            <i class="ti ti-rotate"></i> <span class="link-text">Returns & Complaints</span>
            <span class="link-flyout">Returns & Complaints</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="Reports & Analytics" icon="ti ti-report-analytics" id="menu-sales-reports" color="violet" :active="request()->routeIs('admin.sales.reports.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.sales.reports.*') ? 'active' : '' }}" href="{{ route('admin.sales.reports.index') }}">
            <i class="ti ti-report-analytics"></i> <span class="link-text">Reports & Analytics</span>
            <span class="link-flyout">Reports & Analytics</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="Automation & AI" icon="ti ti-settings-automation" id="menu-sales-automation" color="sky" :active="request()->routeIs('admin.sales.automation.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.sales.automation.*') ? 'active' : '' }}" href="{{ route('admin.sales.automation.index') }}">
            <i class="ti ti-settings-automation"></i> <span class="link-text">Automation & AI</span>
            <span class="link-flyout">Automation & AI</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="Settings & Configuration" icon="ti ti-settings" id="menu-sales-settings" color="slate" :active="request()->routeIs('admin.sales.settings.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.sales.settings.*') ? 'active' : '' }}" href="{{ route('admin.sales.settings.index') }}">
            <i class="ti ti-settings"></i> <span class="link-text">Settings & Configuration</span>
            <span class="link-flyout">Settings & Configuration</span>
        </a>
    </li>
</x-sidebar-group>
