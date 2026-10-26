@extends('layouts.admin')

@section('title', 'AI Sales Assistant')

@section('content')
    <x-page-title title="AI Sales Assistant" />

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-bulb"></i> Hot Leads to Call</div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light"><tr><th>Lead</th><th>Score</th></tr></thead>
                        <tbody>
                            @forelse ($hotLeads as $lead)
                                <tr><td class="fw-semibold"><a href="{{ route('admin.crm.leads.edit', $lead) }}">{{ $lead->name }}</a></td><td><span class="tag tone-success">{{ $lead->score }}</span></td></tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted py-4">No hot leads right now.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-bell"></i> Follow-ups Due Today</div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light"><tr><th>Subject</th><th>Type</th></tr></thead>
                        <tbody>
                            @forelse ($followUpsDueToday as $activity)
                                <tr><td class="fw-semibold">{{ $activity->subject }}</td><td><span class="tag tone-indigo">{{ \App\Models\Crm\Activity::TYPES[$activity->type] ?? $activity->type }}</span></td></tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted py-4">Nothing due today.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-alert-triangle"></i> Stalled Deals</div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light"><tr><th>Opportunity</th><th>Amount</th></tr></thead>
                        <tbody>
                            @forelse ($stalledDeals as $deal)
                                <tr><td class="fw-semibold"><a href="{{ route('admin.crm.opportunities.edit', $deal) }}">{{ $deal->name }}</a></td><td>{{ number_format($deal->amount, 0) }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted py-4">No stalled deals.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-calendar-time"></i> Closing This Week ({{ number_format($closingSoonTotal, 0) }})</div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light"><tr><th>Opportunity</th><th>Close Date</th></tr></thead>
                        <tbody>
                            @forelse ($closingSoon as $deal)
                                <tr><td class="fw-semibold"><a href="{{ route('admin.crm.opportunities.edit', $deal) }}">{{ $deal->name }}</a></td><td class="text-muted font-13">{{ $deal->expected_close_date->format('M j, Y') }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted py-4">Nothing closing this week.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
