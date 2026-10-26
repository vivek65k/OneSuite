@extends('admin.hrm.employees.layout')

@section('title', $employee->name.' - History')

@section('employee-content')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3">History</h5>
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead class="thead-light"><tr><th>Date</th><th>Event</th><th>Details</th></tr></thead>
                    <tbody>
                        @forelse ($timeline as $event)
                            <tr>
                                <td>{{ \Illuminate\Support\Carbon::parse($event['date'])->format('M j, Y') }}</td>
                                <td><span class="badge badge-soft-primary">{{ $event['type'] }}</span></td>
                                <td>{{ $event['description'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-muted text-center py-3">No history recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
