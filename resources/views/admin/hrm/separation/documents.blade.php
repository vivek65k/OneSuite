@extends('layouts.admin')

@section('title', $typeLabel)

@php
    use Illuminate\Support\Str;

    $issuedCount = $resignations->filter(fn ($r) => $r->employee->documents->isNotEmpty())->count();
    $pendingCount = $resignations->count() - $issuedCount;
@endphp

@section('content')
    <x-page-title :title="$typeLabel" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="record-header">
        <span class="list-avatar list-avatar-square record-avatar tone-{{ $pendingCount > 0 ? 'amber' : 'success' }}" aria-hidden="true"><i class="ti ti-file-text font-20"></i></span>
        <div class="record-header-text">
            <div class="record-header-title">{{ $resignations->count() }} separating {{ Str::plural('employee', $resignations->count()) }}</div>
            <div class="record-header-meta">
                <span class="status-dot status-dot-success">{{ $issuedCount }} issued</span>
                @if ($pendingCount > 0)
                    <span class="status-dot status-dot-warning">{{ $pendingCount }} pending</span>
                @endif
            </div>
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="Separating Employees" :value="$resignations->count()" icon="ti-logout" tone="indigo" />
        <x-kpi-card label="Issued" :value="$issuedCount" icon="ti-circle-check" tone="success" />
        <x-kpi-card label="Pending" :value="$pendingCount" icon="ti-clock" tone="amber" />
    </div>

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-file-text"></i> {{ $typeLabel }}</div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Employee</th>
                        <th>Last Working Day</th>
                        <th>Issued Document</th>
                        <th style="width: 320px;">Issue {{ $typeLabel }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($resignations as $resignation)
                        @php $document = $resignation->employee->documents->first(); @endphp
                        <tr>
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$resignation->employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $resignation->employee->name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $resignation->last_working_day->format('M j, Y') }}</td>
                            <td>
                                @if ($document)
                                    <a href="{{ $document->url }}" target="_blank" class="tag tone-success"><i class="ti ti-file-check"></i> {{ $document->name }}</a>
                                    @can('delete', $document)
                                        <form action="{{ route('admin.separation-documents.destroy', $document) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this document?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-icon" title="Remove"><i class="ti ti-trash font-14 text-danger"></i></button>
                                        </form>
                                    @endcan
                                @else
                                    <span class="tag tone-amber"><i class="ti ti-clock"></i> Not issued</span>
                                @endif
                            </td>
                            <td>
                                @can('create', \App\Models\EmployeeDocument::class)
                                    @unless ($document)
                                        <form method="POST" action="{{ route('admin.separation-documents.store') }}" enctype="multipart/form-data" class="d-flex gap-1">
                                            @csrf
                                            <input type="hidden" name="employee_id" value="{{ $resignation->employee_id }}">
                                            <input type="hidden" name="category" value="{{ $type }}">
                                            <input type="hidden" name="name" value="{{ $typeLabel }} - {{ $resignation->employee->name }}">
                                            <input type="file" name="file" class="form-control form-control-sm" required>
                                            <button type="submit" class="btn btn-de-primary btn-sm text-light px-3">Issue</button>
                                        </form>
                                    @endunless
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">
                                <i class="ti ti-file-text font-24 d-block mb-2"></i>
                                No separating employees found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
