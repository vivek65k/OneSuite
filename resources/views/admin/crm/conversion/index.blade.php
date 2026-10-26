@extends('layouts.admin')

@section('title', 'Lead Conversion')

@php
    use Illuminate\Support\Str;
@endphp

@section('content')
    <x-page-title title="Lead Conversion" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Ready to Convert" :value="$qualifiedCount" icon="ti-list-check" tone="sky" />
        <x-kpi-card label="Converted" :value="$convertedCount" icon="ti-repeat" tone="success" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.lead-conversion.index') }}">
            @if ($sort !== 'score')<input type="hidden" name="sort" value="{{ $sort }}">@endif
            @if ($direction !== 'desc')<input type="hidden" name="direction" value="{{ $direction }}">@endif
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-repeat"></i> Qualified leads ready to convert</div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search name or company" aria-label="Search leads">
                    </div>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <x-th-sort column="name" :sort="$sort" :direction="$direction" label="Lead" />
                        <th>Source</th>
                        <x-th-sort column="score" :sort="$sort" :direction="$direction" label="Score" />
                        <th>Assigned To</th>
                        <th class="text-end"><span class="visually-hidden">Action</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leads as $lead)
                        <tr>
                            <td>
                                <div class="list-user-text">
                                    <div class="list-user-name">{{ $lead->name }}</div>
                                    <div class="list-user-meta">{{ $lead->company_name ?? $lead->email ?? '—' }}</div>
                                </div>
                            </td>
                            <td>{{ $lead->source?->name ?? '—' }}</td>
                            <td class="text-center"><span class="tag tone-sky">{{ $lead->score }}</span></td>
                            <td>{{ $lead->assignedTo?->name ?? '—' }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.crm.lead-conversion.convert', $lead) }}" method="POST" class="d-inline" onsubmit="return confirm('Convert this lead to a contact?');">
                                    @csrf
                                    <button type="submit" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-repeat font-14 me-1"></i> Convert to Contact</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5"><i class="ti ti-repeat font-24 d-block mb-2"></i>No qualified leads ready for conversion.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($leads->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $leads->links() }}</div></div>
        @endif
    </div>
@endsection
