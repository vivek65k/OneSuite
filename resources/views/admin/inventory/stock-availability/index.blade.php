@extends('layouts.admin')

@section('title', 'Stock Availability Planner')

@section('content')
    <x-page-title title="Stock Availability Planner" />

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-chart-bar"></i> Availability <span class="list-tab-count">{{ $rows->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Item</th><th>Warehouse</th><th>On-hand</th><th>Reserved (Outgoing)</th><th>Quality Hold</th><th>Incoming</th>
                        <th>Available Now</th><th>Expected Availability</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td class="font-13">{{ $row['stock']->item?->name }}</td>
                            <td class="text-muted font-13">{{ $row['stock']->warehouse?->name }}</td>
                            <td class="text-muted font-13">{{ $row['stock']->quantity }}</td>
                            <td class="text-muted font-13">{{ $row['stock']->reserved_quantity }}</td>
                            <td class="text-muted font-13">{{ $row['quality_hold'] }}</td>
                            <td class="text-muted font-13">{{ $row['incoming'] }}</td>
                            <td class="font-13">{{ $row['available_now'] }}</td>
                            <td class="font-13 fw-semibold {{ $row['expected_availability'] < 0 ? 'text-danger' : 'text-success' }}">{{ $row['expected_availability'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5"><i class="ti ti-chart-bar font-24 d-block mb-2"></i>No stock to plan yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
