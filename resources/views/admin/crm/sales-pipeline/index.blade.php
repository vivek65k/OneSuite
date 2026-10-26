@extends('layouts.admin')

@section('title', 'Sales Pipeline')

@section('content')
    <x-page-title title="Sales Pipeline" />

    <div class="kpi-strip">
        <x-kpi-card label="Open Opportunities" :value="$totalCount" icon="ti-briefcase" tone="indigo" />
        <x-kpi-card label="Pipeline Value" :value="number_format($totalAmount)" icon="ti-currency-rupee" tone="sky" />
        <x-kpi-card label="Weighted Value" :value="number_format($totalWeighted)" icon="ti-chart-line" tone="violet" hint="Amount × probability" />
        <x-kpi-card label="Avg Deal Size" :value="number_format($avgDealSize)" icon="ti-stack-2" tone="success" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.sales-pipeline.index') }}">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-chart-line"></i> By Stage</div>
                <div class="list-toolbar-actions">
                    <select name="owner_employee_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Owner">
                        <option value="">All owners</option>
                        @foreach ($owners as $id => $name)
                            <option value="{{ $id }}" @selected((string) $ownerId === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    <a href="{{ route('admin.crm.pipeline-board.index') }}" class="btn btn-light btn-sm"><i class="ti ti-layout-kanban font-14 me-1"></i> Board View</a>
                    <a href="{{ route('admin.crm.sales-stages.index') }}" class="btn btn-light btn-sm"><i class="ti ti-settings font-14 me-1"></i> Manage Stages</a>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Stage</th>
                        <th class="text-center">Opportunities</th>
                        <th style="min-width: 160px">Share of pipeline</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Weighted</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($byStage as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row['stage']->name }}</td>
                            <td class="text-center"><span class="tag tone-indigo">{{ $row['count'] }}</span></td>
                            <td>
                                <div class="mini-progress tone-indigo">
                                    <div class="mini-progress-bar is-toned"><span style="width: {{ $totalAmount > 0 ? round($row['amount'] / $totalAmount * 100) : 0 }}%"></span></div>
                                    <span class="mini-progress-label">{{ $totalAmount > 0 ? round($row['amount'] / $totalAmount * 100) : 0 }}%</span>
                                </div>
                            </td>
                            <td class="text-end">{{ number_format($row['amount']) }}</td>
                            <td class="text-end fw-semibold">{{ number_format($row['weighted']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5">No sales stages configured yet. <a href="{{ route('admin.crm.sales-stages.create') }}">Add one</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
