<ul class="nav flex-column">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.dashboard') ? 'active' : '' }}" href="{{ route('admin.inventory.dashboard') }}">
            <i class="ti ti-dashboard"></i> <span class="link-text">Dashboard</span>
            <span class="link-flyout">Dashboard</span>
        </a>
    </li>
</ul>

<x-sidebar-group title="Overview" icon="ti ti-layout-grid" id="menu-overview" color="indigo" :active="request()->routeIs('admin.inventory.stock-overview.*') || request()->routeIs('admin.inventory.warehouse-dashboard.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.dashboard') ? 'active' : '' }}" href="{{ route('admin.inventory.dashboard') }}">
            <i class="ti ti-dashboard"></i> <span class="link-text">Dashboard</span>
            <span class="link-flyout">Dashboard</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.stock-overview.*') ? 'active' : '' }}" href="{{ route('admin.inventory.stock-overview.index') }}">
            <i class="ti ti-package"></i> <span class="link-text">Stock Overview</span>
            <span class="link-flyout">Stock Overview</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.warehouse-dashboard.*') ? 'active' : '' }}" href="{{ route('admin.inventory.warehouse-dashboard.index') }}">
            <i class="ti ti-building-warehouse"></i> <span class="link-text">Warehouse Dashboard</span>
            <span class="link-flyout">Warehouse Dashboard</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="Product & Master Data" icon="ti ti-package" id="menu-product-master" color="teal" :active="request()->routeIs('admin.inventory.products.*') || request()->routeIs('admin.inventory.categories.*') || request()->routeIs('admin.inventory.uoms.*') || request()->routeIs('admin.inventory.variants.*') || request()->routeIs('admin.inventory.barcodes.*') || request()->routeIs('admin.inventory.batch-serial.*') || request()->routeIs('admin.inventory.suppliers.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.products.*') ? 'active' : '' }}" href="{{ route('admin.inventory.products.index') }}">
            <i class="ti ti-package"></i> <span class="link-text">Products / SKUs</span>
            <span class="link-flyout">Products / SKUs</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.categories.*') ? 'active' : '' }}" href="{{ route('admin.inventory.categories.index') }}">
            <i class="ti ti-hierarchy"></i> <span class="link-text">Categories &amp; Brands</span>
            <span class="link-flyout">Categories &amp; Brands</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.uoms.*') ? 'active' : '' }}" href="{{ route('admin.inventory.uoms.index') }}">
            <i class="ti ti-ruler"></i> <span class="link-text">Units of Measure</span>
            <span class="link-flyout">Units of Measure</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.variants.*') ? 'active' : '' }}" href="{{ route('admin.inventory.variants.index') }}">
            <i class="ti ti-git-fork"></i> <span class="link-text">Product Variants</span>
            <span class="link-flyout">Product Variants</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.barcodes.*') ? 'active' : '' }}" href="{{ route('admin.inventory.barcodes.index') }}">
            <i class="ti ti-barcode"></i> <span class="link-text">Barcode &amp; QR Codes</span>
            <span class="link-flyout">Barcode &amp; QR Codes</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.batch-serial.*') ? 'active' : '' }}" href="{{ route('admin.inventory.batch-serial.index') }}">
            <i class="ti ti-fingerprint"></i> <span class="link-text">Batch &amp; Serial Profiles</span>
            <span class="link-flyout">Batch &amp; Serial Profiles</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.suppliers.*') ? 'active' : '' }}" href="{{ route('admin.inventory.suppliers.index') }}">
            <i class="ti ti-truck"></i> <span class="link-text">Suppliers &amp; Product Mapping</span>
            <span class="link-flyout">Suppliers &amp; Product Mapping</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="Warehouse Management" icon="ti ti-building-warehouse" id="menu-warehouse-mgmt" color="violet" :active="request()->routeIs('admin.inventory.locations.*') || request()->routeIs('admin.inventory.putaway-rules.*') || request()->routeIs('admin.inventory.picking-strategies.*') || request()->routeIs('admin.inventory.warehouse-transfers.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.warehouse-dashboard.*') ? 'active' : '' }}" href="{{ route('admin.inventory.warehouse-dashboard.index') }}">
            <i class="ti ti-building-warehouse"></i> <span class="link-text">Warehouses</span>
            <span class="link-flyout">Warehouses</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.locations.*') ? 'active' : '' }}" href="{{ route('admin.inventory.locations.index') }}">
            <i class="ti ti-layout-grid"></i> <span class="link-text">Zones, Racks &amp; Bins</span>
            <span class="link-flyout">Zones, Racks &amp; Bins</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.putaway-rules.*') ? 'active' : '' }}" href="{{ route('admin.inventory.putaway-rules.index') }}">
            <i class="ti ti-stairs"></i> <span class="link-text">Putaway Rules</span>
            <span class="link-flyout">Putaway Rules</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.picking-strategies.*') ? 'active' : '' }}" href="{{ route('admin.inventory.picking-strategies.index') }}">
            <i class="ti ti-route"></i> <span class="link-text">Picking Strategies</span>
            <span class="link-flyout">Picking Strategies</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.warehouse-transfers.*') ? 'active' : '' }}" href="{{ route('admin.inventory.warehouse-transfers.index') }}">
            <i class="ti ti-arrows-right-left"></i> <span class="link-text">Warehouse Transfers</span>
            <span class="link-flyout">Warehouse Transfers</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="Inbound Operations" icon="ti ti-truck-delivery" id="menu-inbound-ops" color="sky" :active="request()->routeIs('admin.inventory.expected-receipts.*') || request()->routeIs('admin.inventory.grns.*') || request()->routeIs('admin.inventory.quality-inspections.*') || request()->routeIs('admin.inventory.supplier-returns.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.expected-receipts.*') ? 'active' : '' }}" href="{{ route('admin.inventory.expected-receipts.index') }}">
            <i class="ti ti-truck-delivery"></i> <span class="link-text">Expected Receipts</span>
            <span class="link-flyout">Expected Receipts</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.grns.*') ? 'active' : '' }}" href="{{ route('admin.inventory.grns.index') }}">
            <i class="ti ti-receipt"></i> <span class="link-text">Goods Receipt (GRN)</span>
            <span class="link-flyout">Goods Receipt (GRN)</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.quality-inspections.*') ? 'active' : '' }}" href="{{ route('admin.inventory.quality-inspections.index') }}">
            <i class="ti ti-flask"></i> <span class="link-text">Quality Inspection</span>
            <span class="link-flyout">Quality Inspection</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.putaway-rules.*') ? 'active' : '' }}" href="{{ route('admin.inventory.putaway-rules.index') }}">
            <i class="ti ti-stairs"></i> <span class="link-text">Putaway Tasks</span>
            <span class="link-flyout">Putaway Tasks</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.supplier-returns.*') ? 'active' : '' }}" href="{{ route('admin.inventory.supplier-returns.index') }}">
            <i class="ti ti-rotate"></i> <span class="link-text">Supplier Returns</span>
            <span class="link-flyout">Supplier Returns</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="Outbound Operations" icon="ti ti-truck" id="menu-outbound-ops" color="rose" :active="request()->routeIs('admin.inventory.stock-reservations.*') || request()->routeIs('admin.inventory.pick-lists.*') || request()->routeIs('admin.inventory.packing.*') || request()->routeIs('admin.inventory.dispatches.*') || request()->routeIs('admin.inventory.delivery-confirmations.*') || request()->routeIs('admin.inventory.customer-returns.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.stock-reservations.*') ? 'active' : '' }}" href="{{ route('admin.inventory.stock-reservations.index') }}">
            <i class="ti ti-stack-2"></i> <span class="link-text">Stock Reservations</span>
            <span class="link-flyout">Stock Reservations</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.pick-lists.*') ? 'active' : '' }}" href="{{ route('admin.inventory.pick-lists.index') }}">
            <i class="ti ti-clipboard-list"></i> <span class="link-text">Pick Lists</span>
            <span class="link-flyout">Pick Lists</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.packing.*') ? 'active' : '' }}" href="{{ route('admin.inventory.packing.index') }}">
            <i class="ti ti-box"></i> <span class="link-text">Packing</span>
            <span class="link-flyout">Packing</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.dispatches.*') ? 'active' : '' }}" href="{{ route('admin.inventory.dispatches.index') }}">
            <i class="ti ti-truck"></i> <span class="link-text">Dispatch</span>
            <span class="link-flyout">Dispatch</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.delivery-confirmations.*') ? 'active' : '' }}" href="{{ route('admin.inventory.delivery-confirmations.index') }}">
            <i class="ti ti-circle-check"></i> <span class="link-text">Delivery Confirmation</span>
            <span class="link-flyout">Delivery Confirmation</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.customer-returns.*') ? 'active' : '' }}" href="{{ route('admin.inventory.customer-returns.index') }}">
            <i class="ti ti-arrow-back-up"></i> <span class="link-text">Customer Returns</span>
            <span class="link-flyout">Customer Returns</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="Stock Operations" icon="ti ti-adjustments" id="menu-stock-ops" color="orange" :active="request()->routeIs('admin.inventory.stock-ledger.*') || request()->routeIs('admin.inventory.warehouse-transfers.*') || request()->routeIs('admin.inventory.stock-adjustments.*') || request()->routeIs('admin.inventory.replenishment.*') || request()->routeIs('admin.inventory.cycle-counting.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.stock-ledger.*') ? 'active' : '' }}" href="{{ route('admin.inventory.stock-ledger.index') }}">
            <i class="ti ti-history"></i> <span class="link-text">Stock Ledger</span>
            <span class="link-flyout">Stock Ledger</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.warehouse-transfers.*') ? 'active' : '' }}" href="{{ route('admin.inventory.warehouse-transfers.index') }}">
            <i class="ti ti-arrows-right-left"></i> <span class="link-text">Stock Transfers</span>
            <span class="link-flyout">Stock Transfers</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.stock-adjustments.*') ? 'active' : '' }}" href="{{ route('admin.inventory.stock-adjustments.index') }}">
            <i class="ti ti-adjustments"></i> <span class="link-text">Adjustments</span>
            <span class="link-flyout">Adjustments</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.replenishment.*') ? 'active' : '' }}" href="{{ route('admin.inventory.replenishment.index') }}">
            <i class="ti ti-forklift"></i> <span class="link-text">Replenishment</span>
            <span class="link-flyout">Replenishment</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.cycle-counting.*') ? 'active' : '' }}" href="{{ route('admin.inventory.cycle-counting.index') }}">
            <i class="ti ti-clipboard-check"></i> <span class="link-text">Cycle Counting</span>
            <span class="link-flyout">Cycle Counting</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.cycle-counting.*') ? 'active' : '' }}" href="{{ route('admin.inventory.cycle-counting.index') }}">
            <i class="ti ti-scan"></i> <span class="link-text">Physical Stocktaking</span>
            <span class="link-flyout">Physical Stocktaking</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="Planning & Valuation" icon="ti ti-chart-pie" id="menu-planning-valuation" color="cyan" :active="request()->routeIs('admin.inventory.reorder.*') || request()->routeIs('admin.inventory.demand-forecast.*') || request()->routeIs('admin.inventory.valuation.*') || request()->routeIs('admin.inventory.slow-dead-stock.*') || request()->routeIs('admin.inventory.expiry-management.*') || request()->routeIs('admin.inventory.abc-xyz.*') || request()->routeIs('admin.inventory.stock-availability.*') || request()->routeIs('admin.inventory.planning-workbench.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.reorder.*') ? 'active' : '' }}" href="{{ route('admin.inventory.reorder.index') }}">
            <i class="ti ti-shopping-cart"></i> <span class="link-text">Reorder Management</span>
            <span class="link-flyout">Reorder Management</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.demand-forecast.*') ? 'active' : '' }}" href="{{ route('admin.inventory.demand-forecast.index') }}">
            <i class="ti ti-chart-line"></i> <span class="link-text">Demand Forecast</span>
            <span class="link-flyout">Demand Forecast</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.valuation.*') ? 'active' : '' }}" href="{{ route('admin.inventory.valuation.index') }}">
            <i class="ti ti-calculator"></i> <span class="link-text">Inventory Valuation</span>
            <span class="link-flyout">Inventory Valuation</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.slow-dead-stock.*') ? 'active' : '' }}" href="{{ route('admin.inventory.slow-dead-stock.index') }}">
            <i class="ti ti-clock"></i> <span class="link-text">Slow / Dead Stock</span>
            <span class="link-flyout">Slow / Dead Stock</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.expiry-management.*') ? 'active' : '' }}" href="{{ route('admin.inventory.expiry-management.index') }}">
            <i class="ti ti-alert-triangle"></i> <span class="link-text">Expiry Management</span>
            <span class="link-flyout">Expiry Management</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.abc-xyz.*') ? 'active' : '' }}" href="{{ route('admin.inventory.abc-xyz.index') }}">
            <i class="ti ti-chart-pie"></i> <span class="link-text">ABC / XYZ Analysis</span>
            <span class="link-flyout">ABC / XYZ Analysis</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.stock-availability.*') ? 'active' : '' }}" href="{{ route('admin.inventory.stock-availability.index') }}">
            <i class="ti ti-chart-bar"></i> <span class="link-text">Stock Availability Planner</span>
            <span class="link-flyout">Stock Availability Planner</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.planning-workbench.*') ? 'active' : '' }}" href="{{ route('admin.inventory.planning-workbench.index') }}">
            <i class="ti ti-apps"></i> <span class="link-text">Planning Workbench</span>
            <span class="link-flyout">Planning Workbench</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="Configuration & Reports" icon="ti ti-settings" id="menu-configuration-reports" color="slate" :active="request()->routeIs('admin.inventory.policies.*') || request()->routeIs('admin.inventory.approval-rules.*') || request()->routeIs('admin.inventory.numbering-documents.*') || request()->routeIs('admin.inventory.integrations.*') || request()->routeIs('admin.inventory.reports-audit.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.policies.*') ? 'active' : '' }}" href="{{ route('admin.inventory.policies.index') }}">
            <i class="ti ti-settings"></i> <span class="link-text">Inventory Policies</span>
            <span class="link-flyout">Inventory Policies</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.approval-rules.*') ? 'active' : '' }}" href="{{ route('admin.inventory.approval-rules.index') }}">
            <i class="ti ti-shield-check"></i> <span class="link-text">Approval Rules</span>
            <span class="link-flyout">Approval Rules</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.numbering-documents.*') ? 'active' : '' }}" href="{{ route('admin.inventory.numbering-documents.index') }}">
            <i class="ti ti-hash"></i> <span class="link-text">Numbering & Documents</span>
            <span class="link-flyout">Numbering & Documents</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.integrations.*') ? 'active' : '' }}" href="{{ route('admin.inventory.integrations.index') }}">
            <i class="ti ti-plug"></i> <span class="link-text">Integrations</span>
            <span class="link-flyout">Integrations</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.reports-audit.*') ? 'active' : '' }}" href="{{ route('admin.inventory.reports-audit.index') }}">
            <i class="ti ti-file-report"></i> <span class="link-text">Reports & Audit Logs</span>
            <span class="link-flyout">Reports & Audit Logs</span>
        </a>
    </li>
</x-sidebar-group>
