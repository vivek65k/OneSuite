@props(['label', 'value', 'icon', 'tone' => 'indigo', 'hint' => null, 'href' => null])

@php $tag = $href ? 'a' : 'div'; @endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'kpi-card tone-'.$tone]) }}>
    <span class="kpi-card-icon"><i class="ti {{ $icon }}"></i></span>
    <span class="kpi-card-body">
        <span class="kpi-card-label">{{ $label }}</span>
        <span class="kpi-card-value">{{ is_numeric($value) ? number_format($value) : $value }}</span>
        @if ($hint)
            <span class="kpi-card-hint">{{ $hint }}</span>
        @endif
    </span>
</{{ $tag }}>
