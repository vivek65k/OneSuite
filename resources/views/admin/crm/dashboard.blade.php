@extends('layouts.admin')

@section('title', 'CRM Dashboard')

@php
    use Illuminate\Support\Str;

    $typeIcon = fn ($type) => match ($type) {
        'call' => 'ti-phone',
        'meeting' => 'ti-video',
        'email' => 'ti-mail',
        default => 'ti-list-check',
    };
@endphp

@section('content')
    <x-page-title title="CRM Dashboard" />

    <div class="record-header">
        <span class="list-avatar list-avatar-square record-avatar tone-indigo" aria-hidden="true"><i class="ti ti-target font-20"></i></span>
        <div class="record-header-text">
            <div class="record-header-title">{{ $total }} {{ Str::plural('lead', $total) }} &middot; {{ $openOpportunitiesCount }} open {{ Str::plural('deal', $openOpportunitiesCount) }}</div>
            <div class="record-header-meta">
                <span class="status-dot status-dot-warning">{{ $newLeads }} new</span>
                <span class="status-dot status-dot-info">{{ $qualified }} qualified</span>
                <span class="status-dot status-dot-success">{{ $converted }} converted</span>
            </div>
        </div>
        <div class="record-header-actions">
            <a href="{{ route('admin.crm.leads.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add Lead</a>
            <a href="{{ route('admin.crm.contacts.create') }}" class="btn btn-light btn-sm"><i class="ti ti-user-plus font-14 me-1"></i> Add Contact</a>
            <a href="{{ route('admin.crm.opportunities.create') }}" class="btn btn-light btn-sm"><i class="ti ti-briefcase font-14 me-1"></i> Create Deal</a>
            <a href="{{ route('admin.crm.calls.create') }}" class="btn btn-light btn-sm"><i class="ti ti-phone font-14 me-1"></i> Log Call</a>
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="Total Leads" :value="$total" icon="ti-target" tone="indigo" :href="route('admin.crm.leads.index')" />
        <x-kpi-card label="New Leads" :value="$newLeads" icon="ti-bulb" tone="sky" :href="route('admin.crm.leads.index', ['status' => 'new'])" />
        <x-kpi-card label="Qualified Leads" :value="$qualified" icon="ti-list-check" tone="violet" />
        <x-kpi-card label="Open Opportunities" :value="$openOpportunitiesCount" icon="ti-briefcase" tone="amber" :href="route('admin.crm.opportunities.index')" />
        <x-kpi-card label="Pipeline Value" value="{{ number_format($pipelineValue, 0) }}" icon="ti-cash" tone="teal" />
        <x-kpi-card label="Won This Month" value="{{ number_format($wonThisMonth, 0) }}" icon="ti-trophy" tone="success" />
        <x-kpi-card label="Conversion Rate" value="{{ $conversionRate }}%" icon="ti-chart-pie" tone="rose" />
        <x-kpi-card label="Follow-ups Due Today" :value="$followUpsDueToday" icon="ti-bell" tone="amber" :href="route('admin.crm.follow-up-reminders.index')" />
        <x-kpi-card label="Overdue Activities" :value="$overdueActivities" icon="ti-alert-triangle" tone="rose" />
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-history"></i> Recent Activities</div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <tbody>
                            @forelse ($recentActivities as $activity)
                                <tr>
                                    <td style="width:32px;"><i class="ti {{ $typeIcon($activity->type) }} text-muted"></i></td>
                                    <td class="fw-semibold">{{ $activity->subject }}</td>
                                    <td class="text-muted font-13">{{ $activity->owner?->name ?? '—' }}</td>
                                    <td class="text-muted font-12 text-end">{{ $activity->created_at->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr><td class="text-center text-muted py-4">No recent activity.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-target"></i> Sales Target vs Achievement</div></div>
                <div class="p-3">
                    @forelse ($targetVsAchievement as $row)
                        @php $pct = $row['target'] > 0 ? min(round($row['achieved'] / $row['target'] * 100), 100) : 0; @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between font-13 mb-1">
                                <span class="fw-semibold">{{ $row['label'] }}</span>
                                <span class="text-muted">{{ number_format($row['achieved'], 0) }} / {{ number_format($row['target'], 0) }}</span>
                            </div>
                            <div class="mini-progress"><span style="width: {{ $pct }}%;" class="bg-{{ $pct >= 100 ? 'success' : ($pct >= 50 ? 'warning' : 'danger') }}"></span></div>
                        </div>
                    @empty
                        <p class="text-muted text-center py-4 mb-0">No quotas set for this month. <a href="{{ route('admin.crm.quotas.index') }}">Set one up</a>.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="card list-view mt-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-trophy"></i> Team Performance (This Month)</div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Employee</th><th class="text-end">Deals Won</th><th class="text-end">Revenue</th></tr></thead>
                <tbody>
                    @forelse ($teamPerformance as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row['employee']->name }}</td>
                            <td class="text-end">{{ $row['count'] }}</td>
                            <td class="text-end">{{ number_format($row['amount'], 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">No deals won yet this month.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @php $max = max(1, $byStatus->max()); @endphp
    <div class="card list-view tone-indigo mt-3">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-chart-bar"></i> Leads by Status</div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <tbody>
                    @foreach (\App\Models\Crm\Lead::STATUSES as $key => $label)
                        @php $count = $byStatus[$key] ?? 0; @endphp
                        <tr>
                            <td class="fw-semibold">{{ $label }}</td>
                            <td style="min-width: 160px">
                                <div class="mini-progress tone-indigo">
                                    <div class="mini-progress-bar is-toned"><span style="width: {{ round($count / $max * 100) }}%"></span></div>
                                </div>
                            </td>
                            <td class="text-end"><span class="tag tone-indigo">{{ $count }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
