@extends('layouts.admin')

@section('title', 'Next Best Action')

@section('content')
    <x-page-title title="Next Best Action" />

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-compass"></i> Recommended Actions <span class="list-tab-count">{{ $recommendations->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Priority</th><th>Type</th><th>Record</th><th>Recommended Action</th><th>Why</th></tr></thead>
                <tbody>
                    @forelse ($recommendations as $rec)
                        <tr>
                            <td><span class="tag tone-{{ $rec['priority'] === 1 ? 'rose' : ($rec['priority'] === 2 ? 'amber' : 'slate') }}">P{{ $rec['priority'] }}</span></td>
                            <td><span class="tag tone-indigo">{{ $rec['type'] }}</span></td>
                            <td class="fw-semibold">{{ $rec['subject']->name ?? $rec['subject']->subject ?? '—' }}</td>
                            <td>{{ $rec['action'] }}</td>
                            <td class="text-muted font-13">{{ $rec['reason'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5"><i class="ti ti-compass font-24 d-block mb-2"></i>Nothing needs attention right now.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
