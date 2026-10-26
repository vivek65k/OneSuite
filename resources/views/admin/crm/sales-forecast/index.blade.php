@extends('layouts.admin')

@section('title', 'Sales Forecast')

@php
    $maxWeighted = max(1, $forecast->max('weighted'));
@endphp

@section('content')
    <x-page-title title="Sales Forecast" />

    <div class="kpi-strip">
        <x-kpi-card label="Open Opportunities" :value="$totalCount" icon="ti-briefcase" tone="indigo" />
        <x-kpi-card label="Committed" :value="number_format($totalCommitted)" icon="ti-circle-check" tone="success" hint="70%+ probability, full amount" />
        <x-kpi-card label="Weighted Forecast" :value="number_format($totalWeighted)" icon="ti-chart-line" tone="violet" hint="Amount × probability" />
        <x-kpi-card label="No Close Date" :value="$noDateCount" icon="ti-calendar-stats" tone="amber" :hint="$noDateCount > 0 ? 'Excluded from forecast' : null" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.sales-forecast.index') }}">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-calendar-stats"></i> Next 6 months</div>
                <div class="list-toolbar-actions">
                    <select name="owner_employee_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Owner">
                        <option value="">All owners</option>
                        @foreach ($owners as $id => $name)
                            <option value="{{ $id }}" @selected((string) $ownerId === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Month</th>
                        <th class="text-center">Opportunities</th>
                        <th style="min-width: 140px">Weighted forecast</th>
                        <th class="text-end">Committed</th>
                        <th class="text-end">Best Case</th>
                        <th class="text-end">Weighted Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($forecast as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row['month']->format('F Y') }}</td>
                            <td class="text-center"><span class="tag tone-indigo">{{ $row['count'] }}</span></td>
                            <td>
                                <div class="mini-progress tone-violet">
                                    <div class="mini-progress-bar is-toned"><span style="width: {{ round($row['weighted'] / $maxWeighted * 100) }}%"></span></div>
                                </div>
                            </td>
                            <td class="text-end"><span class="tag tone-success">{{ number_format($row['committed']) }}</span></td>
                            <td class="text-end"><span class="tag tone-amber">{{ number_format($row['bestCase']) }}</span></td>
                            <td class="text-end fw-semibold">{{ number_format($row['weighted']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">No open opportunities with an expected close date.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="report-total">
                        <td class="fw-semibold">Total</td>
                        <td class="text-center">{{ $forecast->sum('count') }}</td>
                        <td></td>
                        <td class="text-end fw-semibold">{{ number_format($totalCommitted) }}</td>
                        <td class="text-end fw-semibold">{{ number_format($forecast->sum('bestCase')) }}</td>
                        <td class="text-end fw-bold">{{ number_format($totalWeighted) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
