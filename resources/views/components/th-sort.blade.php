@props(['column', 'sort', 'direction', 'label', 'align' => 'start'])

@php
    $isActive = $sort === $column;
    $nextDirection = $isActive && $direction === 'asc' ? 'desc' : 'asc';
@endphp

<th class="sortable-th {{ $align === 'end' ? 'text-end' : '' }} {{ $isActive ? 'is-sorted' : '' }}">
    <a href="{{ request()->fullUrlWithQuery(['sort' => $column, 'direction' => $nextDirection]) }}" class="sortable-th-link">
        {{ $label }}
        <i class="ti {{ $isActive ? ($direction === 'asc' ? 'ti-chevron-up' : 'ti-chevron-down') : 'ti-selector' }} sort-icon"></i>
    </a>
</th>
