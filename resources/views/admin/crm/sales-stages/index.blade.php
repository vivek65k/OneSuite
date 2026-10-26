@extends('layouts.admin')

@section('title', 'Sales Stages')

@section('content')
    <x-page-title title="Sales Stages" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Total Stages" :value="$stages->count()" icon="ti-stack-2" tone="indigo" />
        <x-kpi-card label="Opportunities in Pipeline" :value="$totalOpportunities" icon="ti-briefcase" tone="violet" />
    </div>

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-stack-2"></i> Stages (in pipeline order)</div>
            <div class="list-toolbar-actions">
                <a href="{{ route('admin.crm.sales-stages.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add Stage</a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 90px;">Order</th>
                        <th>Name</th>
                        <th class="text-center">Win %</th>
                        <th>Type</th>
                        <th class="text-center">Opportunities</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stages as $stage)
                        <tr>
                            <td>
                                <form action="{{ route('admin.crm.sales-stages.move-up', $stage) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn-icon" title="Move up"><i class="ti ti-arrow-up font-14"></i></button>
                                </form>
                                <form action="{{ route('admin.crm.sales-stages.move-down', $stage) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn-icon" title="Move down"><i class="ti ti-arrow-down font-14"></i></button>
                                </form>
                            </td>
                            <td class="fw-semibold"><a href="{{ route('admin.crm.sales-stages.edit', $stage) }}">{{ $stage->name }}</a></td>
                            <td class="text-center">{{ $stage->win_probability }}%</td>
                            <td>
                                @if ($stage->is_won)
                                    <span class="tag tone-success">Won</span>
                                @elseif ($stage->is_lost)
                                    <span class="tag tone-rose">Lost</span>
                                @else
                                    <span class="tag tone-sky">Open</span>
                                @endif
                            </td>
                            <td class="text-center"><span class="tag tone-indigo">{{ $stage->opportunities_count }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.sales-stages.edit', $stage) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-edit font-16"></i></a>
                                <form action="{{ route('admin.crm.sales-stages.destroy', $stage) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this stage?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="ti ti-stack-2 font-24 d-block mb-2"></i>
                                No sales stages yet.
                                <div><a href="{{ route('admin.crm.sales-stages.create') }}">Add your first one</a>.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
