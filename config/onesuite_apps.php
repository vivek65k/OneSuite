<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OneSuite App Launcher
    |--------------------------------------------------------------------------
    |
    | Registry of the modules shown in the top-right app launcher (the
    | Google-Apps-style grid). "key" also doubles as the sidebar partial
    | name (partials.admin.sidebar.{key}). "built" modules have real
    | routes/views; modules with built => false render a "coming soon"
    | placeholder.
    |
    | Which app owns which page is decided in routes/admin.php, where every
    | route group is tagged with ['app' => '{key}'] — see AppLauncher::currentKey().
    | This registry drives the app-launcher tile, the sidebar partial name,
    | and — via "quick_links" — the topbar's app-specific shortcut row.
    |
    */

    'apps' => [
        'settings' => [
            'label' => 'Settings',
            'description' => 'Users, roles, org structure',
            'icon' => 'ti ti-settings',
            'route' => 'admin.dashboard',
            'built' => true,
            'quick_links' => [
                ['label' => 'Add User', 'icon' => 'ti ti-user-plus', 'route' => 'admin.users.create'],
                ['label' => 'Add Role', 'icon' => 'ti ti-shield-lock', 'route' => 'admin.roles.create'],
                ['label' => 'Add Holiday', 'icon' => 'ti ti-calendar-plus', 'route' => 'admin.holidays.create'],
            ],
        ],
        'hrm' => [
            'label' => 'HRM',
            'description' => 'Employees, attendance, payroll',
            'icon' => 'ti ti-users',
            'route' => 'admin.hrm.dashboard',
            'built' => true,
            'quick_links' => [
                ['label' => 'Add Employee', 'icon' => 'ti ti-user-plus', 'route' => 'admin.employees.create'],
                ['label' => 'Add Job Position', 'icon' => 'ti ti-briefcase', 'route' => 'admin.job-positions.create'],
                ['label' => 'Add Candidate', 'icon' => 'ti ti-users', 'route' => 'admin.candidates.create'],
            ],
        ],
        'crm' => [
            'label' => 'CRM',
            'description' => 'Leads, contacts, pipelines',
            'icon' => 'ti ti-target',
            'route' => 'admin.crm.dashboard',
            'built' => true,
            'quick_links' => [
                ['label' => 'Add Lead', 'icon' => 'ti ti-user-plus', 'route' => 'admin.crm.leads.create'],
            ],
        ],
        'inventory' => [
            'label' => 'Inventory & Warehouse',
            'description' => 'Items, stock, warehouses',
            'icon' => 'ti ti-building-warehouse',
            'route' => 'admin.inventory.dashboard',
            'built' => true,
            'quick_links' => [
                ['label' => 'Add Item', 'icon' => 'ti ti-package', 'route' => 'admin.inventory.stock-overview.index'],
                ['label' => 'Add Warehouse', 'icon' => 'ti ti-building-warehouse', 'route' => 'admin.inventory.warehouse-dashboard.index'],
            ],
        ],
        'sales' => [
            'label' => 'Sales',
            'description' => 'Quotes, orders, contracts',
            'icon' => 'ti ti-shopping-cart',
            'route' => 'admin.sales.dashboard',
            'built' => true,
            'quick_links' => [
                ['label' => 'Manage Customers', 'icon' => 'ti ti-building', 'route' => 'admin.sales.customers.index'],
                ['label' => 'Manage Pricing', 'icon' => 'ti ti-tag', 'route' => 'admin.sales.pricing.index'],
            ],
        ],
    ],
];
