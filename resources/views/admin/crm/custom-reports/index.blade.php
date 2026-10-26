@extends('layouts.admin')

@section('title', 'Custom Report Builder')

@section('content')
    <x-page-title title="Custom Report Builder" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-table"></i> Saved Reports <span class="list-tab-count">{{ $reports->count() }}</span></div>
            <div class="list-toolbar-actions">
                <a href="{{ route('admin.crm.custom-reports.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> New Report</a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Name</th><th>Source</th><th>Type</th><th>Owner</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($reports as $report)
                        <tr>
                            <td class="fw-semibold"><a href="{{ route('admin.crm.custom-reports.show', $report) }}">{{ $report->name }}</a></td>
                            <td><span class="tag tone-indigo">{{ \App\Models\Crm\CustomReport::SOURCES[$report->source] ?? $report->source }}</span></td>
                            <td><span class="tag tone-violet">{{ \App\Models\Crm\CustomReport::REPORT_TYPES[$report->report_type] ?? ucfirst($report->report_type) }}</span></td>
                            <td class="text-muted font-13">{{ $report->owner?->name ?? '—' }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.crm.custom-reports.destroy', $report) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this report?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5"><i class="ti ti-table font-24 d-block mb-2"></i>No custom reports yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
