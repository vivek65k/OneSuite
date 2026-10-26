@php
    $onesuiteApps = \App\Support\AppLauncher::accessibleApps(auth()->user());
    $onesuiteCurrentApp = \App\Support\AppLauncher::currentKey();
    $onesuiteQuickLinks = collect($onesuiteApps[$onesuiteCurrentApp]['quick_links'] ?? [])
        ->filter(fn ($link) => \Illuminate\Support\Facades\Route::has($link['route']));

    // Every app's bell only ever shows that app's own notifications - a
    // leave-request alert has no business showing up while someone's
    // working in CRM. The one place everything comes together, grouped
    // by app, is the separate super-admin-only /notifications overview
    // (NotificationController::allApps()), linked below only for them.
    $onesuiteNotificationsRoute = 'admin.'.$onesuiteCurrentApp.'.notifications.index';
    $onesuiteRecentNotifications = auth()->user()->notifications()
        ->where('data->app', $onesuiteCurrentApp)
        ->latest()->take(8)->get();
    $onesuiteUnreadCount = auth()->user()->unreadNotifications
        ->filter(fn ($n) => ($n->data['app'] ?? null) === $onesuiteCurrentApp)
        ->count();
@endphp

<nav class="navbar-custom" id="navbar-custom">
    <ul class="list-unstyled topbar-nav float-end mb-0">
        <li class="dropdown">
            <a class="nav-link nav-icon border-0 bg-transparent dropdown-toggle notification-toggle" data-bs-toggle="dropdown" href="#" role="button"
                aria-haspopup="false" aria-expanded="false" title="Notifications">
                <i class="ti ti-bell"></i>
                @if ($onesuiteUnreadCount > 0)
                    <span class="notification-badge">{{ $onesuiteUnreadCount > 9 ? '9+' : $onesuiteUnreadCount }}</span>
                @endif
            </a>
            <div class="dropdown-menu dropdown-menu-end notification-menu">
                <div class="notification-menu-header">
                    <p class="notification-menu-title mb-0">
                        Notifications
                        <span class="notification-menu-scope">&middot; {{ $onesuiteApps[$onesuiteCurrentApp]['label'] ?? ucfirst($onesuiteCurrentApp) }}</span>
                    </p>
                    @if ($onesuiteUnreadCount > 0)
                        <form method="POST" action="{{ route('admin.notifications.mark-all-read') }}">
                            @csrf
                            <input type="hidden" name="app" value="{{ $onesuiteCurrentApp }}">
                            <button type="submit" class="notification-mark-all-read">Mark all read</button>
                        </form>
                    @endif
                </div>
                <div class="notification-menu-list">
                    @forelse ($onesuiteRecentNotifications as $notification)
                        <form method="POST" action="{{ route('admin.notifications.read', $notification->id) }}">
                            @csrf
                            <button type="submit" class="notification-item {{ $notification->read_at ? '' : 'is-unread' }}">
                                <span class="notification-item-icon"><i class="{{ $notification->data['icon'] ?? 'ti ti-bell' }}"></i></span>
                                <span class="notification-item-body">
                                    <span class="d-block notification-item-title">{{ $notification->data['title'] ?? 'Notification' }}</span>
                                    <span class="d-block notification-item-message">{{ $notification->data['message'] ?? '' }}</span>
                                    <span class="d-block notification-item-time">{{ $notification->created_at->diffForHumans() }}</span>
                                </span>
                            </button>
                        </form>
                    @empty
                        <p class="text-muted text-center font-12 py-4 mb-0">No notifications yet.</p>
                    @endforelse
                </div>
                @if (\Illuminate\Support\Facades\Route::has($onesuiteNotificationsRoute))
                    <a href="{{ route($onesuiteNotificationsRoute) }}" class="notification-menu-footer">View all {{ $onesuiteApps[$onesuiteCurrentApp]['label'] ?? '' }} notifications</a>
                @endif
                @if (auth()->user()->is_super_admin)
                    <a href="{{ route('admin.notifications.index') }}" class="notification-menu-footer notification-menu-footer-secondary">View all apps</a>
                @endif
            </div>
        </li>
        <li class="dropdown app-launcher">
            <a class="nav-link nav-icon border-0 bg-transparent dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button"
                aria-haspopup="false" aria-expanded="false" title="Apps">
                <i class="ti ti-grid-dots"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-end app-launcher-menu">
                <p class="app-launcher-title">Apps</p>
                <div class="app-launcher-grid">
                    @foreach ($onesuiteApps as $key => $app)
                        <a href="{{ route($app['route']) }}" class="app-launcher-tile {{ $key === $onesuiteCurrentApp ? 'active' : '' }}" data-tile-color="{{ $key }}">
                            <i class="{{ $app['icon'] }}"></i>
                            <span>{{ $app['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </li>
        <li class="dropdown">
            <a class="nav-link dropdown-toggle nav-user" data-bs-toggle="dropdown" href="#" role="button"
                aria-haspopup="false" aria-expanded="false">
                <div class="d-flex align-items-center">
                    <img src="{{ auth()->user()?->avatar ? asset('storage/'.auth()->user()->avatar) : 'https://ui-avatars.com/api/?name='.urlencode(auth()->user()->name ?? 'U') }}" alt="profile-user" class="rounded-circle me-2 thumb-sm" />
                    <div>
                        <small class="d-none d-md-block font-11">{{ auth()->user()?->is_super_admin ? 'Super admin' : 'Platform user' }}</small>
                        <span class="d-none d-md-block fw-semibold font-12">{{ auth()->user()?->name }} <i class="mdi mdi-chevron-down"></i></span>
                    </div>
                </div>
            </a>
            <div class="dropdown-menu dropdown-menu-end">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item">
                        <i class="ti ti-power font-16 me-1 align-text-bottom"></i> Logout
                    </button>
                </form>
            </div>
        </li>
    </ul>

    <ul class="list-unstyled topbar-nav mb-0">
        <li>
            <button class="nav-link nav-icon border-0 bg-transparent" id="togglemenu" type="button" title="Toggle sidebar">
                <i class="ti ti-layout-sidebar" id="togglemenu-icon"></i>
            </button>
        </li>
        @if ($onesuiteQuickLinks->isNotEmpty())
            <li class="dropdown">
                <a class="quick-actions-toggle dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button"
                    aria-haspopup="false" aria-expanded="false" title="Quick actions">
                    <i class="ti ti-plus"></i>
                    <span class="d-none d-md-inline">Quick actions</span>
                </a>
                <div class="dropdown-menu quick-actions-menu">
                    <p class="quick-actions-title">Quick actions</p>
                    @foreach ($onesuiteQuickLinks as $link)
                        <a href="{{ route($link['route']) }}" class="dropdown-item quick-actions-item">
                            <span class="quick-actions-icon"><i class="{{ $link['icon'] }}"></i></span>
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </div>
            </li>
        @endif
    </ul>
</nav>

<script>
    // The vendor template (app.js) binds its own click handler to this same
    // button for its original two-tier sidebar ("enlarge-menu"/"enlarge-menu-all"),
    // which we don't use — our single-column sidebar is a full replacement.
    // stopImmediatePropagation blocks that later-registered handler from
    // ever firing, since ours runs first (this script renders before app.js loads).
    var togglemenuIcon = document.getElementById('togglemenu-icon');

    function syncTogglemenuIcon() {
        var collapsed = document.body.classList.contains('sidebar-collapsed');
        togglemenuIcon.classList.toggle('ti-layout-sidebar', !collapsed);
        togglemenuIcon.classList.toggle('ti-menu-2', collapsed);
    }

    syncTogglemenuIcon();

    document.getElementById('togglemenu').addEventListener('click', function (e) {
        e.stopImmediatePropagation();

        if (window.innerWidth < 992) {
            document.body.classList.toggle('sidebar-mobile-open');
        } else {
            document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebar-collapsed', document.body.classList.contains('sidebar-collapsed'));
        }

        syncTogglemenuIcon();
    });

    // app.js also auto-adds enlarge-menu/enlarge-menu-all on load and on
    // window resize (unconditionally, whenever window.screen.width < 1340) —
    // .enlarge-menu-all sets .leftbar-tab-menu{display:none}, which hides our
    // sidebar outright while our own layout still reserves space for it,
    // corrupting the page. Strip those classes the instant anything adds them.
    new MutationObserver(function () {
        if (document.body.classList.contains('enlarge-menu') || document.body.classList.contains('enlarge-menu-all')) {
            document.body.classList.remove('enlarge-menu', 'enlarge-menu-all');
        }
    }).observe(document.body, {attributes: true, attributeFilter: ['class']});
</script>
