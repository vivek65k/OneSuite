@extends('layouts.admin')

@section('title', $report->name)

@section('content')
    <x-page-title :title="$report->name" :breadcrumbs="['Custom Reports' => route('admin.crm.custom-reports.index')]" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($report->report_type === 'table')
        <div class="card list-view">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-table"></i> {{ \App\Models\Crm\CustomReport::SOURCES[$report->source] ?? $report->source }} <span class="list-tab-count">{{ $rows->count() }}</span></div>
                <div class="list-toolbar-actions">
                    <a href="{{ route('admin.crm.custom-reports.export', $report) }}" class="btn btn-light btn-sm"><i class="ti ti-download font-14 me-1"></i> Export CSV</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover list-table mb-0">
                    <thead class="thead-light">
                        <tr>@foreach ($report->columns as $col)<th>{{ $col }}</th>@endforeach</tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                @foreach ($report->columns as $col)
                                    <td>{{ data_get($row, $col) }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ count($report->columns) }}" class="text-center text-muted py-5">No data found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @elseif ($report->report_type === 'pivot')
        <div class="card list-view">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-layout-grid"></i> Pivot: {{ $report->config['pivot_row'] }} &times; {{ $report->config['pivot_col'] ?? 'Total' }}</div>
                <div class="list-toolbar-actions">
                    <a href="{{ route('admin.crm.custom-reports.export', $report) }}" class="btn btn-light btn-sm"><i class="ti ti-download font-14 me-1"></i> Export CSV</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover list-table mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ $report->config['pivot_row'] }}</th>
                            @foreach ($pivot['columns'] as $col)<th class="text-end">{{ $col }}</th>@endforeach
                            @if (count($pivot['columns']) > 1)<th class="text-end fw-bold">Total</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pivot['rows'] as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['label'] }}</td>
                                @foreach ($pivot['columns'] as $col)<td class="text-end">{{ number_format($row['values'][$col] ?? 0, 2) }}</td>@endforeach
                                @if (count($pivot['columns']) > 1)<td class="text-end fw-bold">{{ number_format($row['total'], 2) }}</td>@endif
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted py-5">No data found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        @php $maxVal = collect($chartData['series'])->max('value') ?: 1; @endphp
        <div class="card list-view">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-chart-bar"></i> {{ ucfirst($chartData['type']) }} chart: {{ $report->config['chart_x'] }} &rarr; {{ $report->config['chart_y'] }}</div>
                <div class="list-toolbar-actions">
                    <a href="{{ route('admin.crm.custom-reports.export', $report) }}" class="btn btn-light btn-sm"><i class="ti ti-download font-14 me-1"></i> Export CSV</a>
                </div>
            </div>
            <div class="p-3">
                @forelse ($chartData['series'] as $point)
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="font-13 text-truncate" style="width: 140px;" title="{{ $point['label'] }}">{{ $point['label'] }}</span>
                        <div class="mini-progress flex-grow-1"><span style="width: {{ $maxVal > 0 ? round($point['value'] / $maxVal * 100) : 0 }}%;" class="bg-primary"></span></div>
                        <span class="font-13 text-muted" style="width: 90px; text-align:right;">{{ number_format($point['value'], 2) }}</span>
                    </div>
                @empty
                    <p class="text-muted text-center py-4 mb-0">No data found.</p>
                @endforelse
            </div>
        </div>
    @endif
@endsection
