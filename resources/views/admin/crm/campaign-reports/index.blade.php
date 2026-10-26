@extends('layouts.admin')

@section('title', 'Campaign Reports')

@section('content')
    <x-page-title title="Campaign Reports" />

    <div class="kpi-strip">
        <x-kpi-card label="Total Spend" value="{{ number_format($totalCost, 0) }}" icon="ti-wallet" tone="indigo" />
        <x-kpi-card label="Total Members" :value="$totalMembers" icon="ti-users" tone="violet" />
        <x-kpi-card label="Conversions" :value="$totalConverted" icon="ti-target" tone="success" />
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-speakerphone"></i> Campaign ROI</div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Campaign</th><th>Type</th><th class="text-end">Members</th><th class="text-end">Converted</th><th class="text-end">Cost</th><th class="text-end">Cost / Conversion</th></tr></thead>
                <tbody>
                    @forelse ($campaigns as $row)
                        <tr>
                            <td class="fw-semibold"><a href="{{ route('admin.crm.campaigns.show', $row['campaign']) }}">{{ $row['campaign']->name }}</a></td>
                            <td><span class="tag tone-indigo">{{ \App\Models\Crm\Campaign::TYPES[$row['campaign']->type] ?? $row['campaign']->type }}</span></td>
                            <td class="text-end">{{ $row['members'] }}</td>
                            <td class="text-end"><span class="tag tone-success">{{ $row['converted'] }}</span></td>
                            <td class="text-end">{{ number_format($row['cost'], 0) }}</td>
                            <td class="text-end">{{ number_format($row['cost_per_conversion'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-speakerphone font-24 d-block mb-2"></i>No campaigns yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
