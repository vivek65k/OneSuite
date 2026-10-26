@extends('layouts.admin')

@section('title', 'Recruitment Reports')

@php
    use Illuminate\Support\Str;

    $hired = $candidatesByStage['hired'] ?? 0;
    $stageRows = collect(\App\Models\Candidate::STAGES)->mapWithKeys(fn ($stage) => [Str::headline($stage) => $candidatesByStage[$stage] ?? 0]);
    $offerRows = collect(\App\Models\OfferLetter::STATUSES)->mapWithKeys(fn ($status) => [Str::headline($status) => $offersByStatus[$status] ?? 0]);
@endphp

@section('content')
    <x-page-title title="Recruitment Reports" />

    <div class="record-header">
        <span class="list-avatar list-avatar-square record-avatar tone-{{ $totalCandidates > 0 ? 'violet' : 'slate' }}" aria-hidden="true"><i class="ti ti-briefcase font-20"></i></span>
        <div class="record-header-text">
            <div class="record-header-title">{{ $totalCandidates }} {{ Str::plural('candidate', $totalCandidates) }} in the pipeline</div>
            <div class="record-header-meta">
                <span class="status-dot status-dot-info">{{ $openPositions }} open {{ Str::plural('position', $openPositions) }}</span>
                @if ($upcomingInterviews > 0)
                    <span class="status-dot status-dot-warning">{{ $upcomingInterviews }} upcoming {{ Str::plural('interview', $upcomingInterviews) }}</span>
                @endif
                @if ($hired > 0)
                    <span class="status-dot status-dot-success">{{ $hired }} hired</span>
                @endif
            </div>
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="Open Positions" :value="$openPositions" icon="ti-briefcase" tone="indigo" href="{{ route('admin.job-positions.open') }}" />
        <x-kpi-card label="Total Candidates" :value="$totalCandidates" icon="ti-users" tone="violet" href="{{ route('admin.candidates.index') }}" />
        <x-kpi-card label="Upcoming Interviews" :value="$upcomingInterviews" icon="ti-calendar-event" tone="amber" href="{{ route('admin.interviews.index') }}" />
        <x-kpi-card label="Hired" :value="$hired" icon="ti-user-check" tone="success" hint="All time" />
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            @php $stageMax = max(1, $stageRows->max()); @endphp
            <div class="card list-view tone-violet mb-0 h-100">
                <div class="list-toolbar">
                    <div class="list-toolbar-title"><i class="ti ti-stairs-up"></i> Candidates by Stage</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <tbody>
                            @forelse ($stageRows as $name => $count)
                                <tr>
                                    <td class="fw-semibold">{{ $name }}</td>
                                    <td style="min-width: 140px">
                                        <div class="mini-progress tone-violet">
                                            <div class="mini-progress-bar is-toned"><span style="width: {{ round($count / $stageMax * 100) }}%"></span></div>
                                        </div>
                                    </td>
                                    <td class="text-end"><span class="tag tone-violet">{{ $count }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-5"><i class="ti ti-stairs-up font-24 d-block mb-2"></i>No candidates yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            @php $offerMax = max(1, $offerRows->max()); @endphp
            <div class="card list-view tone-teal mb-0 h-100">
                <div class="list-toolbar">
                    <div class="list-toolbar-title"><i class="ti ti-file-check"></i> Offers by Status</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <tbody>
                            @forelse ($offerRows as $name => $count)
                                <tr>
                                    <td class="fw-semibold">{{ $name }}</td>
                                    <td style="min-width: 140px">
                                        <div class="mini-progress tone-teal">
                                            <div class="mini-progress-bar is-toned"><span style="width: {{ round($count / $offerMax * 100) }}%"></span></div>
                                        </div>
                                    </td>
                                    <td class="text-end"><span class="tag tone-teal">{{ $count }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-5"><i class="ti ti-file-check font-24 d-block mb-2"></i>No offers yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
