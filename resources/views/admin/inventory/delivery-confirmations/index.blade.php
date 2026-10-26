@extends('layouts.admin')

@section('title', 'Delivery Confirmation')

@section('content')
    <x-page-title title="Delivery Confirmation" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-truck"></i> Awaiting Confirmation <span class="list-tab-count">{{ $pending->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Dispatch #</th><th>Warehouse</th><th>Carrier</th><th>Dispatched</th><th style="min-width:360px">Confirm Delivery</th></tr></thead>
                <tbody>
                    @forelse ($pending as $dispatch)
                        <tr>
                            <td class="font-monospace font-13">{{ $dispatch->dispatch_number }}</td>
                            <td class="text-muted font-13">{{ $dispatch->warehouse?->name }}</td>
                            <td class="text-muted font-13">{{ $dispatch->carrier ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $dispatch->dispatched_at?->format('M j, Y H:i') }}</td>
                            <td>
                                <form action="{{ route('admin.inventory.delivery-confirmations.confirm', $dispatch) }}" method="POST" class="d-flex gap-2 align-items-end">
                                    @csrf
                                    <div><label class="form-label font-11 mb-0">Received By</label><input type="text" name="received_by" class="form-control form-control-sm" style="width:140px" required></div>
                                    <div><label class="form-label font-11 mb-0">Signature</label><input type="text" name="signature_name" class="form-control form-control-sm" style="width:140px"></div>
                                    <button type="submit" class="btn btn-de-primary text-light btn-sm">Confirm</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5"><i class="ti ti-circle-check font-24 d-block mb-2"></i>Nothing is awaiting delivery confirmation.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-circle-check"></i> Recently Confirmed</div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Dispatch #</th><th>Warehouse</th><th>Received By</th><th>Signature</th><th>Delivered At</th></tr></thead>
                <tbody>
                    @forelse ($confirmed as $dispatch)
                        <tr>
                            <td class="font-monospace font-13">{{ $dispatch->dispatch_number }}</td>
                            <td class="text-muted font-13">{{ $dispatch->warehouse?->name }}</td>
                            <td class="text-muted font-13">{{ $dispatch->received_by }}</td>
                            <td class="text-muted font-13">{{ $dispatch->signature_name ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $dispatch->delivered_at?->format('M j, Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5">No confirmed deliveries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
