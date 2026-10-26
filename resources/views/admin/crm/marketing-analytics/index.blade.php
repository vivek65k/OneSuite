@extends('layouts.admin')

@section('title', 'Marketing Analytics')

@section('content')
    <x-page-title title="Marketing Analytics" />

    <div class="kpi-strip">
        <x-kpi-card label="Campaigns" :value="$totalCampaigns" icon="ti-speakerphone" tone="indigo" />
        <x-kpi-card label="Active Now" :value="$activeCampaigns" icon="ti-bolt" tone="success" />
        <x-kpi-card label="Budget / Spend" value="{{ number_format($totalBudget, 0) }} / {{ number_format($totalSpend, 0) }}" icon="ti-wallet" tone="violet" />
        <x-kpi-card label="Sent" :value="$totalSent" icon="ti-send" tone="sky" />
        <x-kpi-card label="Opened" :value="$totalOpened" icon="ti-mail-opened" tone="amber" />
        <x-kpi-card label="Clicked" :value="$totalClicked" icon="ti-click" tone="rose" />
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card p-3 h-100">
                <div class="d-flex align-items-center gap-2 mb-2"><i class="ti ti-forms font-18 text-primary"></i><span class="fw-semibold">Web Forms</span></div>
                <div class="text-muted font-13">{{ $totalForms }} forms &middot; {{ $totalFormSubmissions }} submissions</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 h-100">
                <div class="d-flex align-items-center gap-2 mb-2"><i class="ti ti-browser font-18 text-primary"></i><span class="fw-semibold">Landing Pages</span></div>
                <div class="text-muted font-13">{{ $totalLandingPages }} pages &middot; {{ $totalLandingViews }} views &middot; {{ $totalLandingConversions }} conversions</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 h-100">
                <div class="d-flex align-items-center gap-2 mb-2"><i class="ti ti-target font-18 text-primary"></i><span class="fw-semibold">Overall Response Rate</span></div>
                <div class="text-muted font-13">{{ $totalResponded }} responses from {{ $totalSent }} sent ({{ $totalSent > 0 ? round($totalResponded / $totalSent * 100, 1) : 0 }}%)</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-chart-bar"></i> Performance by Channel</div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light"><tr><th>Channel</th><th class="text-end">Campaigns</th><th class="text-end">Sent</th><th class="text-end">Opened</th><th class="text-end">Spend</th></tr></thead>
                        <tbody>
                            @forelse ($byType as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row['label'] }}</td>
                                    <td class="text-end">{{ $row['count'] }}</td>
                                    <td class="text-end">{{ $row['sent'] }}</td>
                                    <td class="text-end">{{ $row['opened'] }}</td>
                                    <td class="text-end">{{ number_format($row['cost'], 0) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No campaigns yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-rocket"></i> Top Campaigns by Reach</div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light"><tr><th>Campaign</th><th class="text-end">Members</th><th class="text-end">Open Rate</th><th class="text-end">Click Rate</th></tr></thead>
                        <tbody>
                            @forelse ($topCampaigns as $campaign)
                                <tr>
                                    <td class="fw-semibold"><a href="{{ route('admin.crm.campaigns.show', $campaign) }}">{{ $campaign->name }}</a></td>
                                    <td class="text-end">{{ $campaign->members_count }}</td>
                                    <td class="text-end">{{ $campaign->open_rate }}%</td>
                                    <td class="text-end">{{ $campaign->click_rate }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No campaigns yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
