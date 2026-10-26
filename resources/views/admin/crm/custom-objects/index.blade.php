@extends('layouts.admin')

@section('title', 'Custom Objects')

@section('content')
    <x-page-title title="Custom Objects" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-box"></i> All Objects <span class="list-tab-count">{{ $objects->count() }}</span></div>
            <div class="list-toolbar-actions">
                <a href="{{ route('admin.crm.custom-objects.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> New Object</a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Name</th><th>Label</th><th>Fields</th><th>Status</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($objects as $object)
                        <tr>
                            <td class="font-13 text-muted">{{ $object->name }}</td>
                            <td class="fw-semibold"><a href="{{ route('admin.crm.custom-objects.edit', $object) }}">{{ $object->label }}</a></td>
                            <td><span class="tag tone-indigo">{{ count($object->fields_json ?? []) }}</span></td>
                            <td>
                                <form action="{{ route('admin.crm.custom-objects.toggle', $object) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="switch-btn {{ $object->is_active ? 'is-on' : '' }}" role="switch" aria-checked="{{ $object->is_active ? 'true' : 'false' }}">
                                        <span class="visually-hidden">Toggle {{ $object->label }}</span>
                                    </button>
                                </form>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.custom-objects.edit', $object) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                <form action="{{ route('admin.crm.custom-objects.destroy', $object) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this object?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5"><i class="ti ti-box font-24 d-block mb-2"></i>No custom objects yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
