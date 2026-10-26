@extends('layouts.admin')

@section('title', 'Renewal Management')

@section('content')
    <x-page-title title="Renewal Management" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Up for Renewal (90d)" :value="$upcoming->count()" icon="ti-refresh" tone="amber" />
        <x-kpi-card label="Auto-Renew Enabled" :value="$autoRenewCount" icon="ti-circle-check" tone="success" />
        <x-kpi-card label="Already Expired" :value="$expiredCount" icon="ti-alert-triangle" tone="rose" />
    </div>

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-refresh"></i> Contracts ending within 90 days</div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr><th>Contract</th><th>Account</th><th>End Date</th><th class="text-end">Value</th><th>Auto-Renew</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr>
                </thead>
                <tbody>
                    @forelse ($upcoming as $contract)
                        @php $daysLeft = (int) now()->startOfDay()->diffInDays($contract->end_date, false); @endphp
                        <tr>
                            <td><a href="{{ route('admin.crm.contracts.edit', $contract) }}" class="fw-semibold">{{ $contract->title }}</a></td>
                            <td>{{ $contract->account?->name ?? '—' }}</td>
                            <td>
                                {{ $contract->end_date->format('M j, Y') }}
                                @if ($daysLeft < 0)
                                    <span class="tag tone-rose">Expired {{ abs($daysLeft) }}d ago</span>
                                @elseif ($daysLeft <= 30)
                                    <span class="tag tone-amber">{{ $daysLeft }}d left</span>
                                @else
                                    <span class="tag tone-slate">{{ $daysLeft }}d left</span>
                                @endif
                            </td>
                            <td class="text-end salary-figure">{{ number_format($contract->value, 2) }}</td>
                            <td>{{ $contract->auto_renew ? 'Yes' : 'No' }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.crm.renewal-management.renew', $contract) }}" method="POST" class="d-inline" onsubmit="return confirm('Create a renewal contract starting right after this one ends?');">
                                    @csrf
                                    <button type="submit" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-refresh font-14 me-1"></i> Renew</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="ti ti-circle-check font-24 d-block mb-2"></i>
                                Nothing needs renewing in the next 90 days.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
