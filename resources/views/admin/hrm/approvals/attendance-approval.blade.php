@extends('layouts.admin')

@section('title', 'Attendance Approval')

@php
    use Illuminate\Support\Str;

    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
    $canReview = true;
@endphp

@section('content')
    <x-page-title title="Attendance Approval" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Waiting for you" :value="$items->count()" icon="ti-inbox" tone="amber" :hint="$oldest ? 'Oldest waiting '.$oldest->diffForHumans(null, true) : 'Inbox zero'" />
        <x-kpi-card label="Regularization" :value="$counts['Regularization'] ?? 0" icon="ti-clock" tone="indigo" />
        <x-kpi-card label="Overtime" :value="$counts['Overtime'] ?? 0" icon="ti-alarm" tone="amber" />
        <x-kpi-card label="Work From Home" :value="$counts['Work From Home'] ?? 0" icon="ti-home" tone="teal" />
    </div>

    @if ($items->isEmpty())
        <div class="card">
            <div class="empty-state">
                <span class="empty-state-icon tone-success"><i class="ti ti-circle-check"></i></span>
                <h5>All caught up</h5>
                <p>There are no attendance requests waiting for approval.</p>
            </div>
        </div>
    @else
        <div class="approval-list">
            @foreach ($items as $item)
                @php
                    $employee = $item['employee'];
                    $initials = collect(preg_split('/\s+/', trim((string) $employee?->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                @endphp
                <article class="approval-card tone-{{ $item['tone'] }}">
                    <div class="approval-who">
                        <span class="list-avatar list-avatar-{{ $avatarTones[crc32((string) $employee?->employee_code.$employee?->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initials }}</span>
                        <div class="min-w-0">
                            <div class="list-user-name">{{ $employee?->name }}</div>
                            <div class="list-user-meta">{{ $employee?->department?->name ?? $employee?->employee_code }}</div>
                            @if ($item['created_at'])
                                <div class="list-subtext mt-1"><i class="ti ti-clock"></i> asked {{ $item['created_at']->diffForHumans() }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="approval-leave">
                        <span class="tag tone-{{ $item['tone'] }}"><i class="ti {{ $item['icon'] }}"></i>{{ $item['type'] }}</span>
                        <div class="approval-dates">
                            <span class="when-block tone-{{ $item['tone'] }}"><span class="when-block-month">{{ $item['date']?->format('M') }}</span><span class="when-block-day">{{ $item['date']?->format('j') }}</span></span>
                            <span class="approval-days">{{ $item['detail'] }}</span>
                        </div>
                    </div>

                    <div class="approval-context">
                        @if ($item['reason'])
                            <blockquote class="approval-reason" title="{{ $item['reason'] }}">“{{ Str::limit($item['reason'], 140) }}”</blockquote>
                        @else
                            <span class="text-muted font-13">No reason given.</span>
                        @endif
                    </div>

                    @if ($canReview)
                        <div class="approval-actions">
                            <form action="{{ $item['approve_route'] }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm review-btn is-approve w-100"><i class="ti ti-check"></i> Approve</button>
                            </form>
                            <div class="dropdown">
                                <button type="button" class="btn btn-sm review-btn is-reject w-100" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                    <i class="ti ti-x"></i> Reject
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-3 reject-menu">
                                    <form action="{{ $item['reject_route'] }}" method="POST">
                                        @csrf
                                        <label class="form-label font-12 fw-semibold">Reason for rejecting (optional)</label>
                                        <textarea name="remarks" rows="2" class="form-control form-control-sm mb-2" maxlength="1000" placeholder="Shared with {{ $employee?->name }}"></textarea>
                                        <button type="submit" class="btn btn-sm btn-danger w-100"><i class="ti ti-x font-14 me-1"></i> Reject request</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
@endsection
