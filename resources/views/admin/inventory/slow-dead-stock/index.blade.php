@extends('layouts.admin')

@section('title', 'Slow & Dead Stock')

@section('content')
    <x-page-title title="Slow & Dead Stock" />

    <div class="card list-view mb-3">
        <div class="list-filter-panel">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3"><label class="form-label font-11 mb-0">Slow threshold (days)</label><input type="number" min="1" name="slow_days" value="{{ $slowDays }}" class="form-control form-control-sm"></div>
                <div class="col-md-3"><label class="form-label font-11 mb-0">Dead threshold (days)</label><input type="number" min="1" name="dead_days" value="{{ $deadDays }}" class="form-control form-control-sm"></div>
                <div class="col-md-2"><button type="submit" class="btn btn-de-primary text-light btn-sm w-100">Apply</button></div>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-clock"></i> Stock Ageing <span class="list-tab-count">{{ $rows->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Item</th><th>Warehouse</th><th>Qty on Hand</th><th>Last Movement</th><th>Days Since Movement</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php($status = $row['ageing_days'] >= $deadDays ? 'dead' : ($row['ageing_days'] >= $slowDays ? 'slow' : 'active'))
                        <tr>
                            <td class="font-13">{{ $row['stock']->item?->name }}</td>
                            <td class="text-muted font-13">{{ $row['stock']->warehouse?->name }}</td>
                            <td class="text-muted font-13">{{ $row['stock']->quantity }}</td>
                            <td class="text-muted font-13">{{ $row['last_movement'] ? \Illuminate\Support\Carbon::parse($row['last_movement'])->format('M j, Y') : 'Never' }}</td>
                            <td class="text-muted font-13">{{ $row['ageing_days'] }}</td>
                            <td>
                                @if ($status === 'dead')
                                    <span class="tag tone-danger font-11">Dead Stock</span>
                                @elseif ($status === 'slow')
                                    <span class="tag tone-amber font-11">Slow Moving</span>
                                @else
                                    <span class="tag tone-success font-11">Active</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">No stock to analyze yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
