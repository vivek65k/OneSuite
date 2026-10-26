@extends('layouts.admin')

@section('title', 'Price Books')

@section('content')
    <x-page-title title="Price Books" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Total Price Books" :value="$total" icon="ti-book" tone="indigo" />
    </div>

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-book"></i> Price Books</div>
            <div class="list-toolbar-actions">
                <a href="{{ route('admin.crm.price-books.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add Price Book</a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Name</th>
                        <th>Currency</th>
                        <th class="text-center">Products Priced</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($priceBooks as $priceBook)
                        <tr>
                            <td class="fw-semibold">
                                <a href="{{ route('admin.crm.price-books.edit', $priceBook) }}">{{ $priceBook->name }}</a>
                                @if ($priceBook->is_default)
                                    <span class="tag tone-violet">Default</span>
                                @endif
                            </td>
                            <td>{{ $priceBook->currency }}</td>
                            <td class="text-center"><span class="tag tone-indigo">{{ $priceBook->products_count }}</span></td>
                            <td><span class="tag tone-{{ $priceBook->is_active ? 'success' : 'slate' }}">{{ $priceBook->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.price-books.edit', $priceBook) }}" class="btn-icon btn-icon-accent" title="Manage"><i class="ti ti-edit font-16"></i></a>
                                <form action="{{ route('admin.crm.price-books.destroy', $priceBook) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this price book?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti ti-book font-24 d-block mb-2"></i>
                                No price books yet.
                                <div><a href="{{ route('admin.crm.price-books.create') }}">Add your first one</a>.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
