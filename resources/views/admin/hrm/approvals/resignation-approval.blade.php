@extends('layouts.admin')

@section('title', 'Resignation Approval')

@php
    use Illuminate\Support\Str;

    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
    $earliestLwd = $resignations->min('last_working_day');
@endphp

@section('content')
    <x-page-title title="Resignation Approval" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Pending Resignations" :value="$resignations->count()" icon="ti-logout" tone="rose" hint="Waiting for your decision" />
        <x-kpi-card label="Total Notice Days" :value="$resignations->sum('notice_period_days')" icon="ti-calendar-time" tone="amber" />
        <x-kpi-card label="Earliest Last Working Day" :value="$earliestLwd ? $earliestLwd->format('M j') : '—'" icon="ti-calendar-event" tone="indigo" :hint="$earliestLwd ? $earliestLwd->diffForHumans(null, true) : null" />
    </div>

    @if ($resignations->isEmpty())
        <div class="card">
            <div class="empty-state">
                <span class="empty-state-icon tone-success"><i class="ti ti-circle-check"></i></span>
                <h5>All caught up</h5>
                <p>There are no pending resignations waiting for approval.</p>
            </div>
        </div>
    @else
        <div class="approval-list">
            @foreach ($resignations as $resignation)
                @php
                    $employee = $resignation->employee;
                    $initials = collect(preg_split('/\s+/', trim((string) $employee?->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                @endphp
                <article class="approval-card tone-rose">
                    <div class="approval-who">
                        <span class="list-avatar list-avatar-{{ $avatarTones[crc32((string) $employee?->employee_code.$employee?->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initials }}</span>
                        <div class="min-w-0">
                            <div class="list-user-name">{{ $employee?->name }}</div>
                            <div class="list-user-meta">{{ $employee?->department?->name ?? $employee?->employee_code }}</div>
                            <div class="list-subtext mt-1"><i class="ti ti-clock"></i> resigned {{ $resignation->resignation_date->diffForHumans() }}</div>
                        </div>
                    </div>

                    <div class="approval-leave">
                        <span class="tag tone-rose"><i class="ti ti-logout"></i>Resignation</span>
                        <div class="approval-dates">
                            <span class="when-block tone-rose"><span class="when-block-month">{{ $resignation->last_working_day->format('M') }}</span><span class="when-block-day">{{ $resignation->last_working_day->format('j') }}</span></span>
                            <span class="approval-days">{{ $resignation->notice_period_days }}<small>{{ Str::plural('day', $resignation->notice_period_days) }} notice</small></span>
                        </div>
                        <span class="countdown tone-slate">Last day {{ $resignation->last_working_day->format('D, M j') }}</span>
                    </div>

                    <div class="approval-context">
                        @if ($resignation->reason)
                            <blockquote class="approval-reason" title="{{ $resignation->reason }}">“{{ Str::limit($resignation->reason, 140) }}”</blockquote>
                        @else
                            <span class="text-muted font-13">No reason given.</span>
                        @endif
                    </div>

                    @can('update', $resignation)
                        <div class="approval-actions">
                            <form action="{{ route('admin.resignations.accept', $resignation) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm review-btn is-approve w-100"><i class="ti ti-check"></i> Accept</button>
                            </form>
                            <div class="dropdown">
                                <button type="button" class="btn btn-sm review-btn is-reject w-100" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                    <i class="ti ti-x"></i> Withdraw
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-3 reject-menu">
                                    <form action="{{ route('admin.resignations.withdraw', $resignation) }}" method="POST" onsubmit="return confirm('Withdraw this resignation?');">
                                        @csrf
                                        <label class="form-label font-12 fw-semibold">Reason (optional)</label>
                                        <textarea name="remarks" rows="2" class="form-control form-control-sm mb-2" maxlength="1000" placeholder="Shared with {{ $employee?->name }}"></textarea>
                                        <button type="submit" class="btn btn-sm btn-danger w-100"><i class="ti ti-x font-14 me-1"></i> Withdraw resignation</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endcan
                </article>
            @endforeach
        </div>
    @endif
@endsection
