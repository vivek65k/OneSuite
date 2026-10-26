@extends('layouts.admin')

@section('title', 'Cycle Counting & Stocktaking')

@section('content')
    <x-page-title title="Cycle Counting & Stocktaking" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-5">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-calendar-time"></i> Count Schedules</div></div>
                <div class="list-filter-panel">
                    <form method="POST" action="{{ route('admin.inventory.cycle-counting.schedules.store') }}" class="mb-3">
                        @csrf
                        <div class="row g-2 mb-2">
                            <div class="col-6"><input type="text" name="name" class="form-control form-control-sm" placeholder="Schedule name" required></div>
                            <div class="col-6">
                                <select name="warehouse_id" class="form-select form-select-sm" required>
                                    @foreach ($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-4">
                                <select name="type" class="form-select form-select-sm" required>
                                    <option value="cycle">Cycle</option>
                                    <option value="physical">Physical</option>
                                </select>
                            </div>
                            <div class="col-4">
                                <select name="frequency" class="form-select form-select-sm">
                                    <option value="">No frequency</option>
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                            </div>
                            <div class="col-4">
                                <select name="abc_class" class="form-select form-select-sm">
                                    <option value="">Any ABC class</option>
                                    <option value="A">Class A</option>
                                    <option value="B">Class B</option>
                                    <option value="C">Class C</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-light btn-sm">Save Schedule</button>
                    </form>
                    @forelse ($schedules as $schedule)
                        <div class="d-flex justify-content-between align-items-center border-top py-2">
                            <div>
                                <div class="font-13 fw-semibold">{{ $schedule->name }}</div>
                                <div class="text-muted font-11">{{ $schedule->warehouse?->name }} · {{ \App\Models\Inventory\CountSchedule::TYPES[$schedule->type] }} @if($schedule->frequency) · {{ ucfirst($schedule->frequency) }} @endif @if($schedule->abc_class) · Class {{ $schedule->abc_class }} @endif</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted font-13 mb-0">No schedules yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Start a count sheet</div></div>
                <div class="list-filter-panel">
                    <p class="text-muted font-13 mb-2"><i class="ti ti-info-circle"></i> Blind counting hides the system quantity from the counter until each line is submitted.</p>
                    <form method="POST" action="{{ route('admin.inventory.cycle-counting.sheets.store') }}">
                        @csrf
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <select name="warehouse_id" class="form-select form-select-sm" required>
                                    @foreach ($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="type" class="form-select form-select-sm" required>
                                    <option value="cycle">Cycle Count</option>
                                    <option value="physical">Physical Stocktake</option>
                                </select>
                            </div>
                            <div class="col-md-5 form-check form-switch align-self-center">
                                <input class="form-check-input" type="checkbox" name="blind_count" value="1" checked>
                                <label class="form-check-label font-13">Blind count</label>
                            </div>
                        </div>
                        <label class="form-label">Items to count</label>
                        <select name="item_id[]" class="form-select form-select-sm mb-2" multiple size="5" required>
                            @foreach ($items as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach
                        </select>
                        <button type="submit" class="btn btn-de-primary text-light px-4">Create Count Sheet</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-clipboard-check"></i> Count Sheets <span class="list-tab-count">{{ $sheets->total() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Sheet #</th><th>Warehouse</th><th>Type</th><th>Status</th><th style="min-width:420px">Lines</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($sheets as $sheet)
                        <tr>
                            <td class="font-monospace font-13">{{ $sheet->count_number }}</td>
                            <td class="text-muted font-13">{{ $sheet->warehouse?->name }}</td>
                            <td class="text-muted font-13">{{ \App\Models\Inventory\CountSchedule::TYPES[$sheet->type] }} {{ $sheet->blind_count ? '· Blind' : '' }}</td>
                            <td><span class="tag tone-{{ $sheet->status === 'approved' ? 'success' : ($sheet->status === 'variance_review' ? 'amber' : 'sky') }}">{{ \App\Models\Inventory\CountSheet::STATUSES[$sheet->status] }}</span></td>
                            <td>
                                @foreach ($sheet->lines as $line)
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="font-13" style="min-width:110px">{{ $line->item?->name }}</span>
                                        @if (in_array($line->status, ['pending', 'recount_requested']))
                                            <form action="{{ route('admin.inventory.cycle-counting.lines.count', $line) }}" method="POST" class="d-flex gap-1">
                                                @csrf
                                                <input type="number" step="0.01" min="0" name="counted_qty" class="form-control form-control-sm" style="width:80px" placeholder="Counted qty" required>
                                                <button type="submit" class="btn btn-light btn-sm">Submit</button>
                                            </form>
                                        @else
                                            <span class="font-11 text-muted">Sys {{ $line->system_qty }} · Counted {{ $line->counted_qty }}</span>
                                            <span class="tag font-11 {{ abs($line->variance) < 0.0001 ? 'tone-success' : 'tone-danger' }}">Var {{ $line->variance >= 0 ? '+' : '' }}{{ $line->variance }}</span>
                                            @if ($line->status === 'counted' && abs($line->variance) >= 0.0001)
                                                <form action="{{ route('admin.inventory.cycle-counting.lines.recount', $line) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-light btn-sm">Recount</button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                @endforeach
                            </td>
                            <td class="text-end">
                                @if ($sheet->status === 'variance_review')
                                    <form action="{{ route('admin.inventory.cycle-counting.sheets.approve', $sheet) }}" method="POST" onsubmit="return confirm('Approve this count? Variances will be posted to stock.');">
                                        @csrf
                                        <button type="submit" class="btn btn-de-primary text-light btn-sm">Approve &amp; Post</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-clipboard-check font-24 d-block mb-2"></i>No count sheets yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($sheets->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $sheets->links() }}</div></div>
        @endif
    </div>
@endsection
