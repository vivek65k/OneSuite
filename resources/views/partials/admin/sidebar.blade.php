@php
    $currentApp = \App\Support\AppLauncher::currentKey();
    $currentAppConfig = \App\Support\AppLauncher::apps()[$currentApp] ?? null;
    $sidebarCompany = \App\Models\Company::current();
    $sidebarSwitchableBranches = \App\Support\BranchContext::switchableBranches();
    $sidebarCurrentBranchId = \App\Support\BranchContext::currentBranchId();
@endphp

<div class="leftbar-tab-menu" id="app-sidebar">
    <a href="{{ route('admin.dashboard') }}" class="simple-sidebar-brand text-decoration-none">
        <img src="{{ $sidebarCompany->logo ? asset('storage/'.$sidebarCompany->logo) : asset('vendor/metrica/assets/images/logo-sm.png') }}" alt="logo" height="28">
        <span class="logo-text">{{ $sidebarCompany->name }}</span>
    </a>

    @if ($sidebarSwitchableBranches->count() > 1)
        <div class="sidebar-branch-switcher dropdown">
            <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Switch branch">
                <i class="ti ti-building font-16"></i>
                <span class="branch-switcher-label">{{ $sidebarSwitchableBranches->firstWhere('id', $sidebarCurrentBranchId)?->name }}</span>
                <i class="ti ti-chevron-down font-12"></i>
            </button>
            <div class="dropdown-menu">
                @foreach ($sidebarSwitchableBranches as $branch)
                    <form method="POST" action="{{ route('admin.switch-branch') }}">
                        @csrf
                        <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                        <button type="submit" class="dropdown-item d-flex align-items-center justify-content-between {{ $branch->id === $sidebarCurrentBranchId ? 'active' : '' }}">
                            {{ $branch->name }}
                            @if ($branch->id === $sidebarCurrentBranchId)
                                <i class="ti ti-check font-14"></i>
                            @endif
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    @elseif ($sidebarSwitchableBranches->count() === 1)
        <div class="sidebar-branch-switcher">
            <span class="btn" title="Your branch">
                <i class="ti ti-building font-16"></i>
                <span class="branch-switcher-label">{{ $sidebarSwitchableBranches->first()->name }}</span>
            </span>
        </div>
    @endif

    <div class="simple-sidebar-nav" data-simplebar>
        @if ($currentAppConfig['built'] ?? false)
            @include('partials.admin.sidebar.' . $currentApp)
        @else
            @include('partials.admin.sidebar.placeholder', ['app' => $currentAppConfig])
        @endif
    </div>
</div>

<a class="sidebar-icon-tooltip" id="sidebar-icon-tooltip" href="#"></a>

<div class="sidebar-group-popover" id="sidebar-group-popover">
    <p class="sidebar-group-popover-title" id="sidebar-group-popover-title"></p>
    <div id="sidebar-group-popover-body"></div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var groups = Array.from(document.querySelectorAll('.sidebar-group')).map(function (group) {
            return {
                toggle: group.querySelector('.sidebar-group-toggle'),
                body: group.querySelector('.collapse'),
            };
        }).filter(function (g) {
            return g.toggle && g.body;
        });

        function close(g) {
            g.body.classList.remove('show');
            g.toggle.classList.add('collapsed');
            g.toggle.setAttribute('aria-expanded', 'false');
            localStorage.setItem('sidebar-group:' + g.body.id, 'closed');
        }

        // Restore each group's last state from localStorage.
        groups.forEach(function (g) {
            var stored = localStorage.getItem('sidebar-group:' + g.body.id);

            if (stored === 'open') {
                g.body.classList.add('show');
                g.toggle.classList.remove('collapsed');
                g.toggle.setAttribute('aria-expanded', 'true');
            } else if (stored === 'closed' && !g.toggle.classList.contains('is-active-group')) {
                close(g);
            }
        });

        // Only one group open at a time - if restoring left more than one open
        // (e.g. state saved before this behavior existed), keep just the last.
        var open = groups.filter(function (g) {
            return g.body.classList.contains('show');
        });
        open.slice(0, -1).forEach(close);

        // Persist state, and collapse every other group when one opens.
        groups.forEach(function (g) {
            g.body.addEventListener('shown.bs.collapse', function () {
                localStorage.setItem('sidebar-group:' + g.body.id, 'open');
            });
            g.body.addEventListener('hidden.bs.collapse', function () {
                localStorage.setItem('sidebar-group:' + g.body.id, 'closed');
            });
            g.body.addEventListener('show.bs.collapse', function () {
                groups.forEach(function (other) {
                    if (other.body !== g.body) {
                        bootstrap.Collapse.getOrCreateInstance(other.body, { toggle: false }).hide();
                    }
                });
            });
        });

        // Icon-rail tooltips, collapsed sidebar only. Positioned with JS
        // (getBoundingClientRect) into a body-level, position:fixed element
        // instead of a CSS :hover flyout nested in the sidebar — the sidebar's
        // scroll container (SimpleBar) clips anything that tries to escape
        // its bounds via position:absolute, no matter the z-index. Plain
        // links only — group toggles get the richer popover below instead.
        // It's a real <a href> (not inert text) so it's clickable itself,
        // same as every item in the group popover.
        var tooltip = document.getElementById('sidebar-icon-tooltip');
        var tooltipCloseTimer = null;

        function scheduleCloseTooltip() {
            clearTimeout(tooltipCloseTimer);
            tooltipCloseTimer = setTimeout(function () {
                tooltip.classList.remove('is-visible');
            }, 200);
        }

        // Links that belong to a group are reachable only through that
        // group's popover (see below), never directly on the rail — skip
        // them here, otherwise this listener (bound once at load, while
        // they're still in their original spot) keeps firing even after
        // the group popover later moves them elsewhere, showing a second,
        // redundant tooltip right on top of the already-open popover.
        document.querySelectorAll('.simple-sidebar-nav .nav-link').forEach(function (link) {
            if (link.closest('.sidebar-group')) {
                return;
            }

            var labelEl = link.querySelector('.link-text') || link.querySelector('.link-flyout');
            var label = labelEl ? labelEl.textContent.trim() : '';

            if (!label) {
                return;
            }

            link.addEventListener('mouseenter', function () {
                if (!document.body.classList.contains('sidebar-collapsed')) {
                    return;
                }

                clearTimeout(tooltipCloseTimer);

                var rect = link.getBoundingClientRect();
                tooltip.textContent = label;
                tooltip.href = link.getAttribute('href');
                tooltip.style.top = rect.top + 'px';
                tooltip.style.left = (rect.right + 8) + 'px';
                tooltip.classList.add('is-visible');
            });

            link.addEventListener('mouseleave', scheduleCloseTooltip);
        });

        tooltip.addEventListener('mouseenter', function () {
            clearTimeout(tooltipCloseTimer);
        });
        tooltip.addEventListener('mouseleave', scheduleCloseTooltip);

        // Group popover, collapsed sidebar only. Reuses the group's real
        // .collapse submenu by MOVING it (not cloning) into a body-level
        // portal on hover — same clipping-avoidance reasoning as the
        // tooltip above, and since it's the actual element (not a copy),
        // every link stays exactly as clickable/wired as it always was.
        var groupPopover = document.getElementById('sidebar-group-popover');
        var groupPopoverTitle = document.getElementById('sidebar-group-popover-title');
        var groupPopoverBody = document.getElementById('sidebar-group-popover-body');
        var closeTimer = null;
        var openGroup = null;

        function openGroupPopover(g, toggle) {
            if (!document.body.classList.contains('sidebar-collapsed')) {
                return;
            }

            clearTimeout(closeTimer);

            if (openGroup && openGroup.body !== g.body) {
                openGroup.el.appendChild(openGroup.body);
            }

            var labelEl = toggle.querySelector('.sidebar-group-label span');
            groupPopoverTitle.textContent = labelEl ? labelEl.textContent.trim() : '';
            groupPopoverBody.appendChild(g.body);
            openGroup = { el: toggle.parentElement, body: g.body };

            var rect = toggle.getBoundingClientRect();
            groupPopover.style.top = rect.top + 'px';
            groupPopover.style.left = (rect.right + 8) + 'px';
            groupPopover.classList.add('is-visible');
        }

        function scheduleCloseGroupPopover() {
            clearTimeout(closeTimer);
            closeTimer = setTimeout(function () {
                groupPopover.classList.remove('is-visible');

                if (openGroup) {
                    openGroup.el.appendChild(openGroup.body);
                    openGroup = null;
                }
            }, 200);
        }

        groups.forEach(function (g) {
            g.toggle.addEventListener('mouseenter', function () {
                openGroupPopover(g, g.toggle);
            });
            g.toggle.addEventListener('mouseleave', scheduleCloseGroupPopover);
        });

        groupPopover.addEventListener('mouseenter', function () {
            clearTimeout(closeTimer);
        });
        groupPopover.addEventListener('mouseleave', scheduleCloseGroupPopover);

        // Scroll the sidebar so the current page's menu item is already in
        // view on load, instead of leaving the visitor to hunt for it by
        // scrolling through every group above it.
        var activeTarget = document.querySelector('.simple-sidebar-nav .nav-link.active')
            || document.querySelector('.sidebar-group-toggle.is-active-group');

        if (activeTarget) {
            // Deferred: simplebar.min.js (loaded after this inline script,
            // so its own DOMContentLoaded listener fires after ours) wraps
            // this container in its own scroll element on load, which
            // resets scroll position. Waiting a tick lets that finish first.
            setTimeout(function () {
                activeTarget.scrollIntoView({ block: 'center' });
            }, 0);
        }
    });
</script>
