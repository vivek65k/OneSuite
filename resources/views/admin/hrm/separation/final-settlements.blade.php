@extends('layouts.admin')

@section('title', 'Final Settlement')

@php
    use Illuminate\Support\Str;

    $withSettlement = $resignations->map(fn ($r) => $r->finalSettlement)->filter();
    $paidCount = $withSettlement->where('status', 'paid')->count();
    $processedCount = $withSettlement->where('status', 'processed')->count();
    $notStartedCount = $resignations->count() - $withSettlement->count();
    $totalNetPayable = $withSettlement->sum('net_payable');
    $statusTone = fn ($status) => match ($status) {
        'paid' => 'success',
        'processed' => 'sky',
        default => 'amber',
    };
@endphp

@section('content')
    <x-page-title title="Final Settlement" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="record-header">
        <span class="list-avatar list-avatar-square record-avatar tone-{{ $notStartedCount > 0 ? 'amber' : 'success' }}" aria-hidden="true"><i class="ti ti-cash font-20"></i></span>
        <div class="record-header-text">
            <div class="record-header-title">{{ $resignations->count() }} final {{ Str::plural('settlement', $resignations->count()) }}</div>
            <div class="record-header-meta">
                <span class="status-dot status-dot-success">{{ $paidCount }} paid</span>
                @if ($processedCount > 0)
                    <span class="status-dot status-dot-info">{{ $processedCount }} processed</span>
                @endif
                @if ($notStartedCount > 0)
                    <span class="status-dot status-dot-warning">{{ $notStartedCount }} not started</span>
                @endif
            </div>
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="Separating Employees" :value="$resignations->count()" icon="ti-logout" tone="indigo" />
        <x-kpi-card label="Total Net Payable" :value="number_format((float) $totalNetPayable)" icon="ti-cash-banknote" tone="violet" />
        <x-kpi-card label="Paid" :value="$paidCount" icon="ti-circle-check" tone="success" />
        <x-kpi-card label="Pending Action" :value="$notStartedCount + $processedCount" icon="ti-clock" tone="amber" />
    </div>

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-cash"></i> Settlements</div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Employee</th>
                        <th>Last Working Day</th>
                        <th class="text-end">Gross Dues</th>
                        <th style="width: 220px;">Deductions</th>
                        <th class="text-end">Net Payable</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($resignations as $resignation)
                        @php $settlement = $resignation->finalSettlement; @endphp
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
                            <td class="text-end">{{ $settlement ? number_format((float) $settlement->gross_dues, 2) : '—' }}</td>
                            <td>
                                @can('create', \App\Models\FinalSettlement::class)
                                    @if (! $settlement || $settlement->status === 'pending')
                                        <form action="{{ route('admin.final-settlements.process', $resignation) }}" method="POST" class="d-flex gap-1">
                                            @csrf
                                            <input type="number" step="0.01" min="0" name="deductions" class="form-control form-control-sm" placeholder="Deductions" value="{{ $settlement->deductions ?? 0 }}" required>
                                            <button type="submit" class="btn btn-de-primary btn-sm text-light px-2">Process</button>
                                        </form>
                                    @else
                                        {{ number_format((float) $settlement->deductions, 2) }}
                                    @endif
                                @endcan
                            </td>
                            <td class="text-end"><span class="salary-figure">{{ $settlement ? number_format((float) $settlement->net_payable, 2) : '—' }}</span></td>
                            <td>
                                @if ($settlement)
                                    <span class="tag tone-{{ $statusTone($settlement->status) }}">{{ \App\Models\FinalSettlement::STATUSES[$settlement->status] ?? $settlement->status }}</span>
                                @else
                                    <span class="tag tone-amber">Not Started</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($settlement && $settlement->status === 'processed')
                                    @can('update', $settlement)
                                        <form action="{{ route('admin.final-settlements.mark-paid', $settlement) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-light btn-sm">Mark Paid</button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="ti ti-cash-banknote font-24 d-block mb-2"></i>
                                No separating employees found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
