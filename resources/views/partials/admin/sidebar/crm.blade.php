<ul class="nav flex-column">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.dashboard') ? 'active' : '' }}" href="{{ route('admin.crm.dashboard') }}">
            <i class="ti ti-dashboard"></i> <span class="link-text">Dashboard</span>
            <span class="link-flyout">Dashboard</span>
        </a>
    </li>
</ul>

<x-sidebar-group title="Leads & Prospects" icon="ti ti-target" id="menu-leads" color="indigo" :active="request()->routeIs('admin.crm.leads.*') || request()->routeIs('admin.crm.lead-*') || request()->routeIs('admin.crm.duplicate-management.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.leads.index') ? 'active' : '' }}" href="{{ route('admin.crm.leads.index') }}">
            <i class="ti ti-list"></i> <span class="link-text">All Leads</span>
            <span class="link-flyout">All Leads</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.leads.mine') ? 'active' : '' }}" href="{{ route('admin.crm.leads.mine') }}">
            <i class="ti ti-user"></i> <span class="link-text">My Leads</span>
            <span class="link-flyout">My Leads</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.leads.unassigned') ? 'active' : '' }}" href="{{ route('admin.crm.leads.unassigned') }}">
            <i class="ti ti-user-off"></i> <span class="link-text">Unassigned Leads</span>
            <span class="link-flyout">Unassigned Leads</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.leads.index') && request('open') === 'import' ? 'active' : '' }}" href="{{ route('admin.crm.leads.index', ['open' => 'import']) }}">
            <i class="ti ti-file-import"></i> <span class="link-text">Lead Import / Export</span>
            <span class="link-flyout">Lead Import / Export</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.lead-sources.*') ? 'active' : '' }}" href="{{ route('admin.crm.lead-sources.index') }}">
            <i class="ti ti-route"></i> <span class="link-text">Lead Sources</span>
            <span class="link-flyout">Lead Sources</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.lead-scoring.*') ? 'active' : '' }}" href="{{ route('admin.crm.lead-scoring.index') }}">
            <i class="ti ti-gauge"></i> <span class="link-text">Lead Scoring</span>
            <span class="link-flyout">Lead Scoring</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.lead-assignment.*') ? 'active' : '' }}" href="{{ route('admin.crm.lead-assignment.index') }}">
            <i class="ti ti-user-check"></i> <span class="link-text">Lead Assignment</span>
            <span class="link-flyout">Lead Assignment</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.lead-qualification.*') ? 'active' : '' }}" href="{{ route('admin.crm.lead-qualification.index') }}">
            <i class="ti ti-list-check"></i> <span class="link-text">Lead Qualification</span>
            <span class="link-flyout">Lead Qualification</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.duplicate-management.*') ? 'active' : '' }}" href="{{ route('admin.crm.duplicate-management.index') }}">
            <i class="ti ti-copy"></i> <span class="link-text">Duplicate Management</span>
            <span class="link-flyout">Duplicate Management</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.lead-conversion.*') ? 'active' : '' }}" href="{{ route('admin.crm.lead-conversion.index') }}">
            <i class="ti ti-repeat"></i> <span class="link-text">Lead Conversion</span>
            <span class="link-flyout">Lead Conversion</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.lead-recycle.*') ? 'active' : '' }}" href="{{ route('admin.crm.lead-recycle.index') }}">
            <i class="ti ti-recycle"></i> <span class="link-text">Lead Recycle</span>
            <span class="link-flyout">Lead Recycle</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="Accounts & Contacts" icon="ti ti-building" id="menu-accounts" color="teal" :active="request()->routeIs('admin.crm.accounts.*') || request()->routeIs('admin.crm.contacts.*') || request()->routeIs('admin.crm.account-hierarchy.*') || request()->routeIs('admin.crm.contact-relationships.*') || request()->routeIs('admin.crm.customer-*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.accounts.*') ? 'active' : '' }}" href="{{ route('admin.crm.accounts.index') }}">
            <i class="ti ti-building"></i> <span class="link-text">Accounts / Companies</span>
            <span class="link-flyout">Accounts / Companies</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.contacts.*') ? 'active' : '' }}" href="{{ route('admin.crm.contacts.index') }}">
            <i class="ti ti-users"></i> <span class="link-text">Contacts / People</span>
            <span class="link-flyout">Contacts / People</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.account-hierarchy.*') ? 'active' : '' }}" href="{{ route('admin.crm.account-hierarchy.index') }}">
            <i class="ti ti-sitemap"></i> <span class="link-text">Account Hierarchy</span>
            <span class="link-flyout">Account Hierarchy</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.contact-relationships.*') ? 'active' : '' }}" href="{{ route('admin.crm.contact-relationships.index') }}">
            <i class="ti ti-link"></i> <span class="link-text">Contact Relationships</span>
            <span class="link-flyout">Contact Relationships</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.customer-segments.*') ? 'active' : '' }}" href="{{ route('admin.crm.customer-segments.index') }}">
            <i class="ti ti-tag"></i> <span class="link-text">Customer Segments</span>
            <span class="link-flyout">Customer Segments</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.customer-timeline.*') ? 'active' : '' }}" href="{{ route('admin.crm.customer-timeline.index') }}">
            <i class="ti ti-clock"></i> <span class="link-text">Customer Timeline</span>
            <span class="link-flyout">Customer Timeline</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.customer-360.*') ? 'active' : '' }}" href="{{ route('admin.crm.customer-360.index') }}">
            <i class="ti ti-eye"></i> <span class="link-text">Customer 360° View</span>
            <span class="link-flyout">Customer 360° View</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="Opportunities & Pipeline" icon="ti ti-briefcase" id="menu-opportunities" color="violet" :active="request()->routeIs('admin.crm.opportunities.*') || request()->routeIs('admin.crm.sales-pipeline.*') || request()->routeIs('admin.crm.pipeline-board.*') || request()->routeIs('admin.crm.sales-stages.*') || request()->routeIs('admin.crm.opportunity-products.*') || request()->routeIs('admin.crm.competitors.*') || request()->routeIs('admin.crm.win-loss-analysis.*') || request()->routeIs('admin.crm.sales-forecast.*') || request()->routeIs('admin.crm.deal-approvals.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.opportunities.index') ? 'active' : '' }}" href="{{ route('admin.crm.opportunities.index') }}">
            <i class="ti ti-list"></i> <span class="link-text">All Opportunities</span>
            <span class="link-flyout">All Opportunities</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.opportunities.mine') ? 'active' : '' }}" href="{{ route('admin.crm.opportunities.mine') }}">
            <i class="ti ti-user"></i> <span class="link-text">My Opportunities</span>
            <span class="link-flyout">My Opportunities</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.sales-pipeline.*') ? 'active' : '' }}" href="{{ route('admin.crm.sales-pipeline.index') }}">
            <i class="ti ti-chart-line"></i> <span class="link-text">Sales Pipeline</span>
            <span class="link-flyout">Sales Pipeline</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.pipeline-board.*') ? 'active' : '' }}" href="{{ route('admin.crm.pipeline-board.index') }}">
            <i class="ti ti-layout-kanban"></i> <span class="link-text">Pipeline Board (Kanban)</span>
            <span class="link-flyout">Pipeline Board (Kanban)</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.sales-stages.*') ? 'active' : '' }}" href="{{ route('admin.crm.sales-stages.index') }}">
            <i class="ti ti-stack-2"></i> <span class="link-text">Sales Stages</span>
            <span class="link-flyout">Sales Stages</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.opportunity-products.*') ? 'active' : '' }}" href="{{ route('admin.crm.opportunity-products.index') }}">
            <i class="ti ti-box"></i> <span class="link-text">Opportunity Products</span>
            <span class="link-flyout">Opportunity Products</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.competitors.*') ? 'active' : '' }}" href="{{ route('admin.crm.competitors.index') }}">
            <i class="ti ti-shield"></i> <span class="link-text">Competitors</span>
            <span class="link-flyout">Competitors</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.win-loss-analysis.*') ? 'active' : '' }}" href="{{ route('admin.crm.win-loss-analysis.index') }}">
            <i class="ti ti-trophy"></i> <span class="link-text">Win / Loss Analysis</span>
            <span class="link-flyout">Win / Loss Analysis</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.sales-forecast.*') ? 'active' : '' }}" href="{{ route('admin.crm.sales-forecast.index') }}">
            <i class="ti ti-calendar-stats"></i> <span class="link-text">Sales Forecast</span>
            <span class="link-flyout">Sales Forecast</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.deal-approvals.*') ? 'active' : '' }}" href="{{ route('admin.crm.deal-approvals.index') }}">
            <i class="ti ti-shield-check"></i> <span class="link-text">Deal Approvals</span>
            <span class="link-flyout">Deal Approvals</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="Quotes & Sales" icon="ti ti-file-invoice" id="menu-quotes" color="amber" :active="request()->routeIs('admin.crm.quotations.*') || request()->routeIs('admin.crm.price-books.*') || request()->routeIs('admin.crm.products.*') || request()->routeIs('admin.crm.discount-approvals.*') || request()->routeIs('admin.crm.proposals.*') || request()->routeIs('admin.crm.sales-orders.*') || request()->routeIs('admin.crm.contracts.*') || request()->routeIs('admin.crm.esignatures.*') || request()->routeIs('admin.crm.renewal-management.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.quotations.*') ? 'active' : '' }}" href="{{ route('admin.crm.quotations.index') }}">
            <i class="ti ti-file-invoice"></i> <span class="link-text">Quotations</span>
            <span class="link-flyout">Quotations</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.price-books.*') ? 'active' : '' }}" href="{{ route('admin.crm.price-books.index') }}">
            <i class="ti ti-book"></i> <span class="link-text">Price Books</span>
            <span class="link-flyout">Price Books</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.products.*') ? 'active' : '' }}" href="{{ route('admin.crm.products.index') }}">
            <i class="ti ti-box"></i> <span class="link-text">Products &amp; Services</span>
            <span class="link-flyout">Products &amp; Services</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.discount-approvals.*') ? 'active' : '' }}" href="{{ route('admin.crm.discount-approvals.index') }}">
            <i class="ti ti-percentage"></i> <span class="link-text">Discount Approvals</span>
            <span class="link-flyout">Discount Approvals</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.proposals.*') ? 'active' : '' }}" href="{{ route('admin.crm.proposals.index') }}">
            <i class="ti ti-file-text"></i> <span class="link-text">Proposal Builder</span>
            <span class="link-flyout">Proposal Builder</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.sales-orders.*') ? 'active' : '' }}" href="{{ route('admin.crm.sales-orders.index') }}">
            <i class="ti ti-shopping-cart"></i> <span class="link-text">Sales Orders</span>
            <span class="link-flyout">Sales Orders</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.contracts.*') ? 'active' : '' }}" href="{{ route('admin.crm.contracts.index') }}">
            <i class="ti ti-file-invoice"></i> <span class="link-text">Contracts</span>
            <span class="link-flyout">Contracts</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.esignatures.*') ? 'active' : '' }}" href="{{ route('admin.crm.esignatures.index') }}">
            <i class="ti ti-signature"></i> <span class="link-text">E-signatures</span>
            <span class="link-flyout">E-signatures</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.renewal-management.*') ? 'active' : '' }}" href="{{ route('admin.crm.renewal-management.index') }}">
            <i class="ti ti-refresh"></i> <span class="link-text">Renewal Management</span>
            <span class="link-flyout">Renewal Management</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="Activities & Communication" icon="ti ti-calendar-event" id="menu-activities" color="sky" :active="request()->routeIs('admin.crm.tasks.*') || request()->routeIs('admin.crm.calls.*') || request()->routeIs('admin.crm.meetings.*') || request()->routeIs('admin.crm.calendar.*') || request()->routeIs('admin.crm.emails.*') || request()->routeIs('admin.crm.notes.*') || request()->routeIs('admin.crm.follow-up-reminders.*') || request()->routeIs('admin.crm.interaction-history.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.tasks.*') ? 'active' : '' }}" href="{{ route('admin.crm.tasks.index') }}">
            <i class="ti ti-list-check"></i> <span class="link-text">Tasks</span>
            <span class="link-flyout">Tasks</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.calls.*') ? 'active' : '' }}" href="{{ route('admin.crm.calls.index') }}">
            <i class="ti ti-phone"></i> <span class="link-text">Calls</span>
            <span class="link-flyout">Calls</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.meetings.*') ? 'active' : '' }}" href="{{ route('admin.crm.meetings.index') }}">
            <i class="ti ti-video"></i> <span class="link-text">Meetings</span>
            <span class="link-flyout">Meetings</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.calendar.*') ? 'active' : '' }}" href="{{ route('admin.crm.calendar.index') }}">
            <i class="ti ti-calendar-event"></i> <span class="link-text">Calendar</span>
            <span class="link-flyout">Calendar</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.emails.*') ? 'active' : '' }}" href="{{ route('admin.crm.emails.index') }}">
            <i class="ti ti-mail"></i> <span class="link-text">Emails</span>
            <span class="link-flyout">Emails</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.notes.*') ? 'active' : '' }}" href="{{ route('admin.crm.notes.index') }}">
            <i class="ti ti-notes"></i> <span class="link-text">Notes &amp; Attachments</span>
            <span class="link-flyout">Notes &amp; Attachments</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.follow-up-reminders.*') ? 'active' : '' }}" href="{{ route('admin.crm.follow-up-reminders.index') }}">
            <i class="ti ti-bell"></i> <span class="link-text">Follow-up Reminders</span>
            <span class="link-flyout">Follow-up Reminders</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.interaction-history.*') ? 'active' : '' }}" href="{{ route('admin.crm.interaction-history.index') }}">
            <i class="ti ti-history"></i> <span class="link-text">Interaction History</span>
            <span class="link-flyout">Interaction History</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="Marketing" icon="ti ti-speakerphone" id="menu-marketing" color="rose" :active="request()->routeIs('admin.crm.campaigns.*') || request()->routeIs('admin.crm.email-marketing.*') || request()->routeIs('admin.crm.sms-campaigns.*') || request()->routeIs('admin.crm.web-forms.*') || request()->routeIs('admin.crm.landing-pages.*') || request()->routeIs('admin.crm.campaign-members.*') || request()->routeIs('admin.crm.lead-attribution.*') || request()->routeIs('admin.crm.marketing-analytics.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.campaigns.*') ? 'active' : '' }}" href="{{ route('admin.crm.campaigns.index') }}">
            <i class="ti ti-speakerphone"></i> <span class="link-text">Campaigns</span>
            <span class="link-flyout">Campaigns</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.email-marketing.*') ? 'active' : '' }}" href="{{ route('admin.crm.email-marketing.index') }}">
            <i class="ti ti-mail"></i> <span class="link-text">Email Marketing</span>
            <span class="link-flyout">Email Marketing</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.sms-campaigns.*') ? 'active' : '' }}" href="{{ route('admin.crm.sms-campaigns.index') }}">
            <i class="ti ti-message-circle"></i> <span class="link-text">SMS / WhatsApp Campaigns</span>
            <span class="link-flyout">SMS / WhatsApp Campaigns</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.web-forms.*') ? 'active' : '' }}" href="{{ route('admin.crm.web-forms.index') }}">
            <i class="ti ti-forms"></i> <span class="link-text">Web Forms</span>
            <span class="link-flyout">Web Forms</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.landing-pages.*') ? 'active' : '' }}" href="{{ route('admin.crm.landing-pages.index') }}">
            <i class="ti ti-browser"></i> <span class="link-text">Landing Pages</span>
            <span class="link-flyout">Landing Pages</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.campaign-members.*') ? 'active' : '' }}" href="{{ route('admin.crm.campaign-members.index') }}">
            <i class="ti ti-users"></i> <span class="link-text">Campaign Members</span>
            <span class="link-flyout">Campaign Members</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.lead-attribution.*') ? 'active' : '' }}" href="{{ route('admin.crm.lead-attribution.index') }}">
            <i class="ti ti-affiliate"></i> <span class="link-text">Lead Attribution</span>
            <span class="link-flyout">Lead Attribution</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.marketing-analytics.*') ? 'active' : '' }}" href="{{ route('admin.crm.marketing-analytics.index') }}">
            <i class="ti ti-chart-bar"></i> <span class="link-text">Marketing Analytics</span>
            <span class="link-flyout">Marketing Analytics</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="Customer Service" icon="ti ti-headset" id="menu-service" color="sky" :active="request()->routeIs('admin.crm.cases.*') || request()->routeIs('admin.crm.complaints.*') || request()->routeIs('admin.crm.service-requests.*') || request()->routeIs('admin.crm.sla-management.*') || request()->routeIs('admin.crm.escalations.*') || request()->routeIs('admin.crm.knowledge-base.*') || request()->routeIs('admin.crm.customer-feedback.*') || request()->routeIs('admin.crm.csat.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.cases.*') ? 'active' : '' }}" href="{{ route('admin.crm.cases.index') }}">
            <i class="ti ti-ticket"></i> <span class="link-text">Customer Cases</span>
            <span class="link-flyout">Customer Cases</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.complaints.*') ? 'active' : '' }}" href="{{ route('admin.crm.complaints.index') }}">
            <i class="ti ti-message-report"></i> <span class="link-text">Complaints</span>
            <span class="link-flyout">Complaints</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.service-requests.*') ? 'active' : '' }}" href="{{ route('admin.crm.service-requests.index') }}">
            <i class="ti ti-headset"></i> <span class="link-text">Service Requests</span>
            <span class="link-flyout">Service Requests</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.sla-management.*') ? 'active' : '' }}" href="{{ route('admin.crm.sla-management.index') }}">
            <i class="ti ti-shield-check"></i> <span class="link-text">SLA Management</span>
            <span class="link-flyout">SLA Management</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.escalations.*') ? 'active' : '' }}" href="{{ route('admin.crm.escalations.index') }}">
            <i class="ti ti-arrow-up-circle"></i> <span class="link-text">Escalations</span>
            <span class="link-flyout">Escalations</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.knowledge-base.*') ? 'active' : '' }}" href="{{ route('admin.crm.knowledge-base.index') }}">
            <i class="ti ti-book"></i> <span class="link-text">Knowledge Base</span>
            <span class="link-flyout">Knowledge Base</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.customer-feedback.*') ? 'active' : '' }}" href="{{ route('admin.crm.customer-feedback.index') }}">
            <i class="ti ti-message-report"></i> <span class="link-text">Customer Feedback</span>
            <span class="link-flyout">Customer Feedback</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.csat.*') ? 'active' : '' }}" href="{{ route('admin.crm.csat.index') }}">
            <i class="ti ti-mood-smile"></i> <span class="link-text">Customer Satisfaction (CSAT)</span>
            <span class="link-flyout">Customer Satisfaction (CSAT)</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="Reports & Analytics" icon="ti ti-report" id="menu-reports" color="violet" :active="request()->routeIs('admin.crm.sales-reports.*') || request()->routeIs('admin.crm.lead-conversion-report.*') || request()->routeIs('admin.crm.pipeline-reports.*') || request()->routeIs('admin.crm.revenue-reports.*') || request()->routeIs('admin.crm.campaign-reports.*') || request()->routeIs('admin.crm.salesperson-performance.*') || request()->routeIs('admin.crm.custom-reports.*') || request()->routeIs('admin.crm.scheduled-reports.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.sales-reports.*') ? 'active' : '' }}" href="{{ route('admin.crm.sales-reports.index') }}">
            <i class="ti ti-chart-bar"></i> <span class="link-text">Sales Reports</span>
            <span class="link-flyout">Sales Reports</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.lead-conversion-report.*') ? 'active' : '' }}" href="{{ route('admin.crm.lead-conversion-report.index') }}">
            <i class="ti ti-chart-dots"></i> <span class="link-text">Lead Conversion</span>
            <span class="link-flyout">Lead Conversion</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.pipeline-reports.*') ? 'active' : '' }}" href="{{ route('admin.crm.pipeline-reports.index') }}">
            <i class="ti ti-chart-pie"></i> <span class="link-text">Pipeline Reports</span>
            <span class="link-flyout">Pipeline Reports</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.revenue-reports.*') ? 'active' : '' }}" href="{{ route('admin.crm.revenue-reports.index') }}">
            <i class="ti ti-cash"></i> <span class="link-text">Revenue Reports</span>
            <span class="link-flyout">Revenue Reports</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.campaign-reports.*') ? 'active' : '' }}" href="{{ route('admin.crm.campaign-reports.index') }}">
            <i class="ti ti-speakerphone"></i> <span class="link-text">Campaign Reports</span>
            <span class="link-flyout">Campaign Reports</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.salesperson-performance.*') ? 'active' : '' }}" href="{{ route('admin.crm.salesperson-performance.index') }}">
            <i class="ti ti-trophy"></i> <span class="link-text">Salesperson Performance</span>
            <span class="link-flyout">Salesperson Performance</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.custom-reports.*') ? 'active' : '' }}" href="{{ route('admin.crm.custom-reports.index') }}">
            <i class="ti ti-table"></i> <span class="link-text">Custom Report Builder</span>
            <span class="link-flyout">Custom Report Builder</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.scheduled-reports.*') ? 'active' : '' }}" href="{{ route('admin.crm.scheduled-reports.index') }}">
            <i class="ti ti-calendar-time"></i> <span class="link-text">Scheduled Reports</span>
            <span class="link-flyout">Scheduled Reports</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="Automation" icon="ti ti-bolt" id="menu-automation" color="amber" :active="request()->routeIs('admin.crm.workflows.*') || request()->routeIs('admin.crm.assignment-rules.*') || request()->routeIs('admin.crm.approval-rules.*') || request()->routeIs('admin.crm.email-sequences.*') || request()->routeIs('admin.crm.follow-up-automation.*') || request()->routeIs('admin.crm.escalation-rules.*') || request()->routeIs('admin.crm.trigger-actions.*') || request()->routeIs('admin.crm.scheduled-jobs.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.workflows.*') ? 'active' : '' }}" href="{{ route('admin.crm.workflows.index') }}">
            <i class="ti ti-git-branch"></i> <span class="link-text">Workflow Designer</span>
            <span class="link-flyout">Workflow Designer</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.assignment-rules.*') ? 'active' : '' }}" href="{{ route('admin.crm.assignment-rules.index') }}">
            <i class="ti ti-users"></i> <span class="link-text">Assignment Rules</span>
            <span class="link-flyout">Assignment Rules</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.approval-rules.*') ? 'active' : '' }}" href="{{ route('admin.crm.approval-rules.index') }}">
            <i class="ti ti-shield-check"></i> <span class="link-text">Approval Rules</span>
            <span class="link-flyout">Approval Rules</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.email-sequences.*') ? 'active' : '' }}" href="{{ route('admin.crm.email-sequences.index') }}">
            <i class="ti ti-mail-forward"></i> <span class="link-text">Email Sequences</span>
            <span class="link-flyout">Email Sequences</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.follow-up-automation.*') ? 'active' : '' }}" href="{{ route('admin.crm.follow-up-automation.index') }}">
            <i class="ti ti-bell"></i> <span class="link-text">Follow-up Automation</span>
            <span class="link-flyout">Follow-up Automation</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.escalation-rules.*') ? 'active' : '' }}" href="{{ route('admin.crm.escalation-rules.index') }}">
            <i class="ti ti-arrow-up-circle"></i> <span class="link-text">Escalation Rules</span>
            <span class="link-flyout">Escalation Rules</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.trigger-actions.*') ? 'active' : '' }}" href="{{ route('admin.crm.trigger-actions.index') }}">
            <i class="ti ti-bolt"></i> <span class="link-text">Triggers &amp; Actions</span>
            <span class="link-flyout">Triggers &amp; Actions</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.scheduled-jobs.*') ? 'active' : '' }}" href="{{ route('admin.crm.scheduled-jobs.index') }}">
            <i class="ti ti-calendar-time"></i> <span class="link-text">Scheduled Jobs</span>
            <span class="link-flyout">Scheduled Jobs</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="AI & Intelligence" icon="ti ti-bulb" id="menu-ai" color="violet" :active="request()->routeIs('admin.crm.ai-*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.ai-lead-scoring.*') ? 'active' : '' }}" href="{{ route('admin.crm.ai-lead-scoring.index') }}">
            <i class="ti ti-target"></i> <span class="link-text">AI Lead Scoring</span>
            <span class="link-flyout">AI Lead Scoring</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.ai-win-probability.*') ? 'active' : '' }}" href="{{ route('admin.crm.ai-win-probability.index') }}">
            <i class="ti ti-trophy"></i> <span class="link-text">Deal Win Probability</span>
            <span class="link-flyout">Deal Win Probability</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.ai-sales-forecast.*') ? 'active' : '' }}" href="{{ route('admin.crm.ai-sales-forecast.index') }}">
            <i class="ti ti-trending-up"></i> <span class="link-text">Sales Forecasting</span>
            <span class="link-flyout">Sales Forecasting</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.ai-assistant.*') ? 'active' : '' }}" href="{{ route('admin.crm.ai-assistant.index') }}">
            <i class="ti ti-wand"></i> <span class="link-text">Email &amp; Proposal Assistant</span>
            <span class="link-flyout">Email &amp; Proposal Assistant</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.ai-conversation-summary.*') ? 'active' : '' }}" href="{{ route('admin.crm.ai-conversation-summary.index') }}">
            <i class="ti ti-messages"></i> <span class="link-text">Conversation Summaries</span>
            <span class="link-flyout">Conversation Summaries</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.ai-next-best-action.*') ? 'active' : '' }}" href="{{ route('admin.crm.ai-next-best-action.index') }}">
            <i class="ti ti-compass"></i> <span class="link-text">Next Best Action</span>
            <span class="link-flyout">Next Best Action</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.ai-churn-prediction.*') ? 'active' : '' }}" href="{{ route('admin.crm.ai-churn-prediction.index') }}">
            <i class="ti ti-heart-broken"></i> <span class="link-text">Churn Prediction</span>
            <span class="link-flyout">Churn Prediction</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.ai-sales-assistant.*') ? 'active' : '' }}" href="{{ route('admin.crm.ai-sales-assistant.index') }}">
            <i class="ti ti-bulb"></i> <span class="link-text">AI Sales Assistant</span>
            <span class="link-flyout">AI Sales Assistant</span>
        </a>
    </li>
</x-sidebar-group>

<x-sidebar-group title="CRM Configuration" icon="ti ti-adjustments-horizontal" id="menu-crm-config" color="slate" :active="request()->routeIs('admin.crm.settings.*') || request()->routeIs('admin.crm.custom-fields.*') || request()->routeIs('admin.crm.custom-objects.*') || request()->routeIs('admin.crm.layouts.*') || request()->routeIs('admin.crm.sales-stages.*') || request()->routeIs('admin.crm.teams.*') || request()->routeIs('admin.crm.territories.*') || request()->routeIs('admin.crm.quotas.*') || request()->routeIs('admin.crm.integrations.*') || request()->routeIs('admin.crm.import-export.*') || request()->routeIs('admin.crm.audit-logs.*') || request()->routeIs('admin.crm.permissions.*') || request()->routeIs('admin.crm.deal-approval-matrix.*')">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.settings.*') ? 'active' : '' }}" href="{{ route('admin.crm.settings.index') }}">
            <i class="ti ti-settings"></i> <span class="link-text">CRM Settings</span>
            <span class="link-flyout">CRM Settings</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.deal-approval-matrix.*') ? 'active' : '' }}" href="{{ route('admin.crm.deal-approval-matrix.index') }}">
            <i class="ti ti-stairs"></i> <span class="link-text">Deal Approval Matrix</span>
            <span class="link-flyout">Deal Approval Matrix</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.custom-fields.*') ? 'active' : '' }}" href="{{ route('admin.crm.custom-fields.index') }}">
            <i class="ti ti-forms"></i> <span class="link-text">Custom Fields</span>
            <span class="link-flyout">Custom Fields</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.custom-objects.*') ? 'active' : '' }}" href="{{ route('admin.crm.custom-objects.index') }}">
            <i class="ti ti-box"></i> <span class="link-text">Custom Objects</span>
            <span class="link-flyout">Custom Objects</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.layouts.*') ? 'active' : '' }}" href="{{ route('admin.crm.layouts.index') }}">
            <i class="ti ti-layout"></i> <span class="link-text">Layout &amp; Forms</span>
            <span class="link-flyout">Layout &amp; Forms</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.sales-stages.*') ? 'active' : '' }}" href="{{ route('admin.crm.sales-stages.index') }}">
            <i class="ti ti-stairs"></i> <span class="link-text">Pipelines &amp; Stages</span>
            <span class="link-flyout">Pipelines &amp; Stages</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.teams.*') || request()->routeIs('admin.crm.territories.*') ? 'active' : '' }}" href="{{ route('admin.crm.teams.index') }}">
            <i class="ti ti-users"></i> <span class="link-text">Teams &amp; Territories</span>
            <span class="link-flyout">Teams &amp; Territories</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.quotas.*') ? 'active' : '' }}" href="{{ route('admin.crm.quotas.index') }}">
            <i class="ti ti-target"></i> <span class="link-text">Targets &amp; Quotas</span>
            <span class="link-flyout">Targets &amp; Quotas</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.integrations.*') ? 'active' : '' }}" href="{{ route('admin.crm.integrations.index') }}">
            <i class="ti ti-plug"></i> <span class="link-text">Integrations</span>
            <span class="link-flyout">Integrations</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.import-export.*') ? 'active' : '' }}" href="{{ route('admin.crm.import-export.index') }}">
            <i class="ti ti-file-import"></i> <span class="link-text">Import / Export</span>
            <span class="link-flyout">Import / Export</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.audit-logs.*') ? 'active' : '' }}" href="{{ route('admin.crm.audit-logs.index') }}">
            <i class="ti ti-history"></i> <span class="link-text">Audit Logs</span>
            <span class="link-flyout">Audit Logs</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.crm.permissions.*') ? 'active' : '' }}" href="{{ route('admin.crm.permissions.index') }}">
            <i class="ti ti-lock"></i> <span class="link-text">Permissions</span>
            <span class="link-flyout">Permissions</span>
        </a>
    </li>
</x-sidebar-group>
