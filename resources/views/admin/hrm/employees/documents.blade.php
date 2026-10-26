@extends('admin.hrm.employees.layout')

@section('title', $employee->name.' - Documents')

@section('employee-content')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3">Documents</h5>
            <div class="table-responsive mb-3">
                <table class="table table-sm mb-0">
                    <thead class="thead-light"><tr><th>Name</th><th>Category</th><th>Expiry</th><th>File</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($employee->documents as $document)
                            <tr>
                                <td>{{ $document->name }}</td>
                                <td>{{ \App\Models\EmployeeDocument::TYPES[$document->category] ?? $document->category ?? '—' }}</td>
                                <td>{{ $document->expiry_date?->format('M j, Y') ?? '—' }}</td>
                                <td><a href="{{ $document->url }}" target="_blank">View</a></td>
                                <td class="text-end">
                                    @can('update', $employee)
                                        <form action="{{ route('admin.employees.documents.destroy', [$employee, $document]) }}" method="POST" onsubmit="return confirm('Remove this document?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-icon" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted text-center py-3">No documents uploaded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @can('update', $employee)
                <form action="{{ route('admin.employees.documents.store', $employee) }}" method="POST" enctype="multipart/form-data" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="">Uncategorized</option>
                            @foreach (\App\Models\EmployeeDocument::TYPES as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><label class="form-label">Expiry Date</label><input type="date" name="expiry_date" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label">File</label><input type="file" name="file" class="form-control" required></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-de-primary text-light w-100">Upload</button></div>
                </form>
            @endcan
        </div>
    </div>
@endsection
