@extends('layouts.admin')

@section('title', 'Asset Return')

@php
    use Illuminate\Support\Str;

    $byCategory = $assets->countBy(fn ($a) => \App\Models\EmployeeAsset::CATEGORIES[$a->category] ?? $a->category);
@endphp

@section('content')
    <x-page-title title="Asset Return" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="record-header">
        <span class="list-avatar list-avatar-square record-avatar tone-{{ $assets->count() > 0 ? 'amber' : 'success' }}" aria-hidden="true"><i class="ti ti-devices font-20"></i></span>
        <div class="record-header-text">
            <div class="record-header-title">{{ $assets->count() }} {{ Str::plural('asset', $assets->count()) }} to collect</div>
            <div class="record-header-meta">
                <span>From {{ $assets->pluck('employee_id')->unique()->count() }} separating {{ Str::plural('employee', $assets->pluck('employee_id')->unique()->count()) }}</span>
            </div>
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="Pending Returns" :value="$assets->count()" icon="ti-package" tone="amber" />
        <x-kpi-card label="Employees Involved" :value="$assets->pluck('employee_id')->unique()->count()" icon="ti-users" tone="indigo" />
        <x-kpi-card label="Categories" :value="$byCategory->count()" icon="ti-tag" tone="violet" />
    </div>

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-arrow-back-up"></i> Assets to return</div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Employee</th>
                        <th>Category</th>
                        <th>Asset</th>
                        <th>Assigned</th>
                        <th style="width: 380px;">Return</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assets as $asset)
                        <tr>
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$asset->employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $asset->employee->name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="tag tone-indigo">{{ \App\Models\EmployeeAsset::CATEGORIES[$asset->category] ?? $asset->category }}</span></td>
                            <td class="fw-semibold">{{ $asset->asset_name }}</td>
                            <td>{{ $asset->assigned_date?->format('M j, Y') ?? '—' }}</td>
                            <td>
                                @can('update', $asset)
                                    <form method="POST" action="{{ route('admin.separation-asset-returns.update', $asset) }}" class="d-flex gap-1">
                                        @csrf @method('PUT')
                                        <select name="condition_at_return" class="form-select form-select-sm" style="width: 110px;" required>
                                            <option value="good">Good</option>
                                            <option value="damaged">Damaged</option>
                                            <option value="lost">Lost</option>
                                        </select>
                                        <input type="text" name="remarks" class="form-control form-control-sm" placeholder="Remarks (optional)">
                                        <button type="submit" class="btn btn-de-primary btn-sm text-light px-3" onclick="return confirm('Mark this asset as returned?');">
                                            <i class="ti ti-arrow-back-up font-14 me-1"></i> Return
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti ti-arrow-back-up font-24 d-block mb-2"></i>
                                No pending asset returns for separating employees.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
