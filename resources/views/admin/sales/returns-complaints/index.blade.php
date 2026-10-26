@extends('layouts.admin')

@section('title', 'Returns & Complaints')

@section('content')
    <x-page-title title="Returns & Complaints" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <p class="text-muted font-13 mb-3"><i class="ti ti-info-circle"></i> Returns are processed in Inventory & Warehouse (draft → approve → receive, same stock rules) and complaints are tracked in CRM Cases (SLA, escalation) - this screen ties both back to the Sales Order they came from, and can raise a replacement order once a return is received.</p>

    <div class="kpi-strip mb-3">
        <x-kpi-card label="Open Returns" :value="$returns->whereIn('status', ['draft', 'approved'])->count()" icon="ti-rotate" />
        <x-kpi-card label="Received Returns" :value="$returns->where('status', 'received')->count()" icon="ti-circle-check" tone="success" />
        <x-kpi-card label="Open Complaints" :value="$complaints->whereNotIn('status', ['resolved', 'closed'])->count()" icon="ti-alert-triangle" tone="danger" />
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Raise a return</div></div>
                <div class="list-filter-panel">
                    @if ($returnableOrders->isEmpty())
                        <p class="text-muted font-13 mb-0">No fulfilled orders available to return against.</p>
                    @else
                        <form method="POST" action="{{ route('admin.sales.returns-complaints.returns.store') }}">
                            @csrf
                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <select name="sales_order_id" id="return-order-select" class="form-select form-select-sm" required>
                                        <option value="">Select order…</option>
                                        @foreach ($returnableOrders as $order)
                                            <option value="{{ $order->id }}">{{ $order->order_number }} — {{ $order->account?->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <select name="warehouse_id" class="form-select form-select-sm" required>
                                        @foreach ($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach
                                    </select>
                                </div>
                            </div>
                            <input type="text" name="reason" class="form-control form-control-sm mb-2" placeholder="Reason (e.g. Wrong item shipped)" required>
                            @foreach ($returnableOrders as $order)
                                <div class="return-order-lines d-none" data-order-id="{{ $order->id }}">
                                    @foreach ($order->lines as $line)
                                        <div class="row g-2 align-items-center mb-1">
                                            <div class="col-md-1"><input type="checkbox" name="order_line_id[]" value="{{ $line->id }}" class="form-check-input" @if(! $line->product?->inventory_item_id) disabled @endif></div>
                                            <div class="col-md-7 font-13">{{ $line->description }} {{ ! $line->product?->inventory_item_id ? '(no Inventory link)' : '' }}</div>
                                            <div class="col-md-4"><input type="number" step="0.01" min="0.01" max="{{ $line->quantity }}" name="quantity[]" class="form-control form-control-sm" placeholder="Qty" value="{{ $line->quantity }}"></div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                            <button type="submit" class="btn btn-de-primary text-light btn-sm mt-2">Raise Return</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Log a complaint</div></div>
                <div class="list-filter-panel">
                    @if ($returnableOrders->isEmpty())
                        <p class="text-muted font-13 mb-0">No orders available to log a complaint against.</p>
                    @else
                        <form method="POST" action="{{ route('admin.sales.returns-complaints.complaints.store') }}">
                            @csrf
                            <select name="sales_order_id" class="form-select form-select-sm mb-2" required>
                                <option value="">Select order…</option>
                                @foreach ($returnableOrders as $order)
                                    <option value="{{ $order->id }}">{{ $order->order_number }} — {{ $order->account?->name }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="subject" class="form-control form-control-sm mb-2" placeholder="Subject" required>
                            <textarea name="description" rows="2" class="form-control form-control-sm mb-2" placeholder="Details"></textarea>
                            <select name="priority" class="form-select form-select-sm mb-2">
                                @foreach (\App\Models\Crm\CaseModel::PRIORITIES as $key => $label)<option value="{{ $key }}" @selected($key === 'medium')>{{ $label }}</option>@endforeach
                            </select>
                            <button type="submit" class="btn btn-de-primary text-light btn-sm">Log Complaint</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-rotate"></i> Returns <span class="list-tab-count">{{ $returns->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Return #</th><th>Order</th><th>Account</th><th>Reason</th><th>Status</th><th class="text-end" style="min-width:220px"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($returns as $return)
                        <tr>
                            <td class="font-monospace font-13">{{ $return->return_number }}</td>
                            <td class="text-muted font-13 font-monospace">{{ $return->salesOrder?->order_number }}</td>
                            <td class="text-muted font-13">{{ $return->salesOrder?->account?->name ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $return->reason }}</td>
                            <td><span class="tag font-11 {{ $return->status === 'received' ? 'tone-success' : ($return->status === 'approved' ? 'tone-sky' : 'tone-amber') }}">{{ \App\Models\Inventory\CustomerReturn::STATUSES[$return->status] }}</span></td>
                            <td class="text-end">
                                @if ($return->status === 'draft')
                                    <form action="{{ route('admin.inventory.customer-returns.approve', $return) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-light btn-sm">Approve</button>
                                    </form>
                                @elseif ($return->status === 'approved')
                                    <form action="{{ route('admin.inventory.customer-returns.receive', $return) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-de-primary text-light btn-sm">Receive</button>
                                    </form>
                                @elseif ($return->status === 'received')
                                    @if ($return->replacementOrder)
                                        <a href="{{ route('admin.sales.orders.edit', $return->replacementOrder) }}" class="btn btn-light btn-sm">View Replacement {{ $return->replacementOrder->order_number }}</a>
                                    @else
                                        <form action="{{ route('admin.sales.returns-complaints.returns.replacement', $return) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-light btn-sm">Create Replacement Order</button>
                                        </form>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-rotate font-24 d-block mb-2"></i>No returns raised yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-alert-triangle"></i> Complaints <span class="list-tab-count">{{ $complaints->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Case #</th><th>Order</th><th>Subject</th><th>Priority</th><th>Status</th><th>Assigned</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($complaints as $complaint)
                        <tr>
                            <td class="font-monospace font-13">{{ $complaint->case_number }}</td>
                            <td class="text-muted font-13 font-monospace">{{ $complaint->salesOrder?->order_number ?? '—' }}</td>
                            <td class="font-13">{{ $complaint->subject }}</td>
                            <td><span class="tag font-11 {{ $complaint->priority === 'urgent' ? 'tone-danger' : ($complaint->priority === 'high' ? 'tone-amber' : 'tone-slate') }}">{{ \App\Models\Crm\CaseModel::PRIORITIES[$complaint->priority] }}</span></td>
                            <td><span class="tag font-11 {{ in_array($complaint->status, ['resolved','closed']) ? 'tone-success' : 'tone-sky' }}">{{ \App\Models\Crm\CaseModel::STATUSES[$complaint->status] }}</span></td>
                            <td class="text-muted font-13">{{ $complaint->assignedTo?->name ?? '—' }}</td>
                            <td class="text-end"><a href="{{ route('admin.crm.cases.edit', $complaint) }}" class="btn btn-light btn-sm">Manage</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5"><i class="ti ti-alert-triangle font-24 d-block mb-2"></i>No complaints logged yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        (function () {
            var select = document.getElementById('return-order-select');
            if (!select) return;
            function sync() {
                document.querySelectorAll('.return-order-lines').forEach(function (el) {
                    el.classList.toggle('d-none', el.dataset.orderId !== select.value);
                });
            }
            select.addEventListener('change', sync);
        })();
    </script>
@endsection
