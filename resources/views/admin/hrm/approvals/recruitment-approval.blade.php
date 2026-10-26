@extends('layouts.admin')

@section('title', 'Recruitment Approval')

@php
    use Illuminate\Support\Str;

    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
    $totalSalary = $offers->sum('salary_offered');
@endphp

@section('content')
    <x-page-title title="Recruitment Approval" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Draft Offers" :value="$offers->count()" icon="ti-file-text" tone="violet" hint="Waiting for sign-off" />
        <x-kpi-card label="Total Offered" :value="number_format((float) $totalSalary)" icon="ti-currency-rupee" tone="indigo" :hint="$offers->isNotEmpty() ? $offers->first()->currency : null" />
        <x-kpi-card label="Oldest Offer" :value="$offers->isNotEmpty() ? $offers->min('offer_date')?->diffForHumans(null, true) : '—'" icon="ti-clock" tone="amber" />
    </div>

    @if ($offers->isEmpty())
        <div class="card">
            <div class="empty-state">
                <span class="empty-state-icon tone-success"><i class="ti ti-circle-check"></i></span>
                <h5>All caught up</h5>
                <p>There are no draft offers waiting for approval.</p>
            </div>
        </div>
    @else
        <div class="approval-list">
            @foreach ($offers as $offer)
                @php
                    $candidate = $offer->candidate;
                    $initials = collect(preg_split('/\s+/', trim((string) $candidate?->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                @endphp
                <article class="approval-card tone-violet">
                    <div class="approval-who">
                        <span class="list-avatar list-avatar-{{ $avatarTones[crc32((string) $candidate?->id.$candidate?->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initials }}</span>
                        <div class="min-w-0">
                            <div class="list-user-name">{{ $candidate?->name }}</div>
                            <div class="list-user-meta">{{ $candidate?->email ?? $candidate?->phone }}</div>
                            @if ($offer->offer_date)
                                <div class="list-subtext mt-1"><i class="ti ti-clock"></i> offered {{ $offer->offer_date->diffForHumans() }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="approval-leave">
                        <span class="tag tone-violet"><i class="ti ti-briefcase"></i>{{ $offer->jobPosition?->title ?? 'Position TBD' }}</span>
                        <div class="approval-dates">
                            @if ($offer->joining_date)
                                <span class="when-block tone-violet"><span class="when-block-month">{{ $offer->joining_date->format('M') }}</span><span class="when-block-day">{{ $offer->joining_date->format('j') }}</span></span>
                            @endif
                            <span class="approval-days">
                                {{ $offer->salary_offered ? number_format((float) $offer->salary_offered) : '—' }}<small>{{ $offer->currency }}</small>
                            </span>
                        </div>
                        @if ($offer->joining_date)
                            <span class="countdown tone-slate">Joins {{ $offer->joining_date->format('D, M j') }}</span>
                        @endif
                    </div>

                    <div class="approval-context">
                        @if ($offer->notes)
                            <blockquote class="approval-reason" title="{{ $offer->notes }}">“{{ Str::limit($offer->notes, 140) }}”</blockquote>
                        @else
                            <span class="text-muted font-13">No notes on this offer.</span>
                        @endif
                    </div>

                    @can('update', $offer)
                        <div class="approval-actions">
                            <form action="{{ route('admin.recruitment-approval.approve', $offer) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm review-btn is-approve w-100"><i class="ti ti-send"></i> Approve &amp; Send</button>
                            </form>
                            <form action="{{ route('admin.recruitment-approval.reject', $offer) }}" method="POST" onsubmit="return confirm('Withdraw this offer?');">
                                @csrf
                                <button type="submit" class="btn btn-sm review-btn is-reject w-100"><i class="ti ti-x"></i> Withdraw</button>
                            </form>
                        </div>
                    @endcan
                </article>
            @endforeach
        </div>
    @endif
@endsection
