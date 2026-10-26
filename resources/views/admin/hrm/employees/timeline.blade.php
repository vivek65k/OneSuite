@extends('admin.hrm.employees.layout')

@section('title', $employee->name.' - Timeline')

@section('employee-content')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3">Timeline</h5>
            @forelse ($timeline as $event)
                <div class="d-flex mb-3">
                    <div class="flex-shrink-0 me-3">
                        <span class="avatar-box avatar-md bg-soft-primary rounded-circle"><span class="avatar-title bg-transparent"><i class="ti {{ $event['icon'] }} font-18"></i></span></span>
                    </div>
                    <div>
                        <h6 class="mb-0">{{ $event['type'] }}</h6>
                        <p class="text-muted mb-0 font-13">{{ $event['description'] }}</p>
                        <small class="text-muted">{{ \Illuminate\Support\Carbon::parse($event['date'])->format('M j, Y') }}</small>
                    </div>
                </div>
            @empty
                <p class="text-muted mb-0">No events recorded yet.</p>
            @endforelse
        </div>
    </div>
@endsection
