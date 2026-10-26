@extends('layouts.admin')

@section('title', 'Audit Logs')

@section('content')
    <x-page-title title="Audit Logs" />

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-history"></i> Recent System Activity <span class="list-tab-count">{{ $events->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>When</th><th>Event</th></tr></thead>
                <tbody>
                    @forelse ($events as $event)
                        <tr>
                            <td class="text-muted font-13" style="white-space:nowrap;">{{ $event['when']->format('M j, Y g:i A') }}</td>
                            <td><i class="ti {{ $event['icon'] }} text-muted me-1"></i> {{ $event['description'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-muted py-5"><i class="ti ti-history font-24 d-block mb-2"></i>No audited activity yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
