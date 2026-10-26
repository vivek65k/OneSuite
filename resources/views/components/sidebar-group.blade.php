@props(['title', 'icon', 'id', 'active' => false, 'color' => 'blue'])

{{-- A group whose links were all hidden by permissions (see @navcan) isn't shown at all. --}}
@if (trim($slot) !== '')
    <div class="sidebar-group">
        <a class="menu-title sidebar-group-toggle icon-{{ $color }} {{ $active ? 'is-active-group' : 'collapsed' }}" data-bs-toggle="collapse" href="#{{ $id }}"
            role="button" aria-expanded="{{ $active ? 'true' : 'false' }}" aria-controls="{{ $id }}">
            <span class="sidebar-group-label"><i class="{{ $icon }} sidebar-group-icon"></i> <span>{{ $title }}</span></span>
            <i class="ti ti-chevron-down sidebar-group-chevron"></i>
        </a>
        <div class="collapse {{ $active ? 'show' : '' }}" id="{{ $id }}">
            <ul class="nav flex-column">
                {{ $slot }}
            </ul>
        </div>
    </div>
@endif
