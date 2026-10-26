@php $moduleRoute = $app['route'] ?? null; @endphp

<p class="menu-title">{{ $app['label'] ?? 'Module' }}</p>
<ul class="nav flex-column">
    @if ($moduleRoute && Route::has($moduleRoute))
        <li class="nav-item">
            <a class="nav-link active" href="{{ route($moduleRoute) }}">
                <i class="{{ $app['icon'] ?? 'ti ti-apps' }}"></i> <span class="link-text">Dashboard</span>
                <span class="link-flyout">Dashboard</span>
            </a>
        </li>
    @endif
</ul>
<p class="px-3 font-11 text-muted">More {{ $app['label'] ?? 'module' }} menus are coming soon.</p>
