@extends('layouts.admin')

@section('title', 'Churn Prediction')

@section('content')
    <x-page-title title="Churn Prediction" />

    <div class="kpi-strip">
        <x-kpi-card label="High Risk Accounts" :value="$highRiskCount" icon="ti-heart-broken" tone="rose" />
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-heart-broken"></i> Account Risk Scores <span class="list-tab-count">{{ $rows->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Account</th><th>Risk Score</th><th>Factors</th></tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td class="fw-semibold"><a href="{{ route('admin.crm.accounts.edit', $row['account']) }}">{{ $row['account']->name }}</a></td>
                            <td><span class="tag tone-{{ $row['risk'] >= 50 ? 'rose' : ($row['risk'] >= 25 ? 'amber' : 'success') }}">{{ $row['risk'] }}</span></td>
                            <td class="font-12 text-muted">
                                @forelse ($row['factors'] as $factor)
                                    <span class="tag tone-slate me-1">{{ $factor }}</span>
                                @empty
                                    <span class="text-muted">No risk factors detected</span>
                                @endforelse
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-5">No accounts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
