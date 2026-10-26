@extends('layouts.admin')

@section('title', 'Demand Forecasting')

@section('content')
    <x-page-title title="Demand Forecasting" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-filter-panel d-flex justify-content-between align-items-center">
            <p class="text-muted font-13 mb-0"><i class="ti ti-info-circle"></i> Moving-average forecast from the last 3 months of issues, transfers-out and returns-out in the stock ledger.</p>
            <form action="{{ route('admin.inventory.demand-forecast.generate') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-de-primary text-light btn-sm"><i class="ti ti-refresh font-14 me-1"></i> Regenerate Forecast</button>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-chart-line"></i> Forecast <span class="list-tab-count">{{ $forecasts->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Item</th><th>Warehouse</th><th>Historical Avg (monthly)</th><th>Forecast</th><th>Period</th><th>Method</th><th>Generated</th></tr></thead>
                <tbody>
                    @forelse ($forecasts as $forecast)
                        <tr>
                            <td class="font-13">{{ $forecast->item?->name }}</td>
                            <td class="text-muted font-13">{{ $forecast->warehouse?->name }}</td>
                            <td class="text-muted font-13">{{ $forecast->historical_avg_qty }}</td>
                            <td class="font-13 fw-semibold text-success">{{ $forecast->forecast_qty }}</td>
                            <td class="text-muted font-13">{{ $forecast->period_label }}</td>
                            <td class="text-muted font-13">{{ str($forecast->method)->headline() }}</td>
                            <td class="text-muted font-13">{{ $forecast->generated_at?->format('M j, Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5"><i class="ti ti-chart-line font-24 d-block mb-2"></i>No forecast yet - click Regenerate Forecast.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
