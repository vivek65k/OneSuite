@props(['person' => null, 'size' => null, 'title' => null])

{{-- Employee photo from local storage, else coloured initials (never a third-party avatar service). --}}
@php
    $avatarName = (string) ($person->name ?? '');
    $avatarInitials = collect(preg_split('/\s+/', trim($avatarName)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') ?: '?';
    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
    $avatarTone = $avatarTones[crc32(($person->employee_code ?? '').$avatarName) % count($avatarTones)];
    $avatarClass = 'list-avatar'.($size ? ' list-avatar-'.$size : '');
@endphp
@if (! empty($person?->avatar))
    <img src="{{ asset('storage/'.$person->avatar) }}" alt="" {{ $attributes->merge(['class' => $avatarClass]) }} title="{{ $title ?? $avatarName }}">
@else
    <span {{ $attributes->merge(['class' => $avatarClass.' list-avatar-'.$avatarTone]) }} title="{{ $title ?? $avatarName }}" aria-hidden="true">{{ $avatarInitials }}</span>
@endif
