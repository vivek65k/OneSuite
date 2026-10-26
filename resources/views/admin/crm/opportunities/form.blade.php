@extends('layouts.admin')

@php
    $isEditing = $opportunity->exists;
    $cancelUrl = route('admin.crm.opportunities.index');
    $currentStatus = old('status', $opportunity->status ?: 'open');
@endphp

@section('title', $isEditing ? 'Edit Opportunity' : 'Add Opportunity')

@section('content')
    <x-page-title :title="$isEditing ? 'Edit Opportunity' : 'Add Opportunity'" :breadcrumbs="['Opportunities' => $cancelUrl]" />

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Please fix {{ $errors->count() === 1 ? 'this issue' : 'these '.$errors->count().' issues' }} before saving:</div>
            <ul class="mb-0 ps-3 font-13">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $isEditing ? route('admin.crm.opportunities.update', $opportunity) : route('admin.crm.opportunities.store') }}">
        @csrf
        @if ($isEditing) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card form-section tone-indigo">
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-briefcase"></i></span>
                        <div>
                            <h5 class="form-section-title">Opportunity</h5>
                            <p class="form-section-sub">The deal - its value, stage, and timeline.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $opportunity->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="account_id">Account</label>
                                <select class="form-select @error('account_id') is-invalid @enderror" id="account_id" name="account_id">
                                    <option value="">— None —</option>
                                    @foreach ($accounts as $id => $name)
                                        <option value="{{ $id }}" @selected((string) old('account_id', $opportunity->account_id) === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="contact_id">Contact</label>
                                <select class="form-select @error('contact_id') is-invalid @enderror" id="contact_id" name="contact_id">
                                    <option value="">— None —</option>
                                    @foreach ($contacts as $id => $name)
                                        <option value="{{ $id }}" @selected((string) old('contact_id', $opportunity->contact_id) === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('contact_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="sales_stage_id">Stage <span class="text-danger">*</span></label>
                                <select class="form-select @error('sales_stage_id') is-invalid @enderror" id="sales_stage_id" name="sales_stage_id" required>
                                    @foreach ($stages as $id => $name)
                                        <option value="{{ $id }}" @selected((string) old('sales_stage_id', $opportunity->sales_stage_id) === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('sales_stage_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="amount">Amount <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" value="{{ old('amount', $opportunity->amount) }}" required>
                                @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="probability">Win probability % <span class="text-muted font-12">(blank = use stage default)</span></label>
                                <input type="number" min="0" max="100" class="form-control @error('probability') is-invalid @enderror" id="probability" name="probability" value="{{ old('probability', $opportunity->probability) }}">
                                @error('probability')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="expected_close_date">Expected close date</label>
                                <input type="date" class="form-control @error('expected_close_date') is-invalid @enderror" id="expected_close_date" name="expected_close_date" value="{{ old('expected_close_date', optional($opportunity->expected_close_date)->format('Y-m-d')) }}">
                                @error('expected_close_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="owner_employee_id">Owner</label>
                                <select class="form-select @error('owner_employee_id') is-invalid @enderror" id="owner_employee_id" name="owner_employee_id">
                                    <option value="">— Unassigned —</option>
                                    @foreach ($employees as $id => $name)
                                        <option value="{{ $id }}" @selected((string) old('owner_employee_id', $opportunity->owner_employee_id) === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('owner_employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="branch_id">Branch</label>
                                <select class="form-select" id="branch_id" name="branch_id">
                                    <option value="">— Company-wide —</option>
                                    @foreach ($branches as $id => $name)
                                        <option value="{{ $id }}" @selected((string) old('branch_id', $opportunity->branch_id) === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="team_id">Team</label>
                                <select class="form-select" id="team_id" name="team_id">
                                    <option value="">— None —</option>
                                    @foreach ($teams as $team)
                                        <option value="{{ $team->id }}" @selected((string) old('team_id', $opportunity->team_id) === (string) $team->id)>{{ $team->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="notes">Notes</label>
                                <textarea name="notes" id="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $opportunity->notes) }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="risks_blockers">Deal risks &amp; blockers</label>
                                <textarea name="risks_blockers" id="risks_blockers" rows="2" class="form-control" placeholder="e.g. Budget not yet approved, competing vendor in final round">{{ old('risks_blockers', $opportunity->risks_blockers) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($isEditing)
                    <div class="card list-view tone-teal mb-3">
                        <div class="list-toolbar">
                            <div class="list-toolbar-title"><i class="ti ti-box"></i> Product lines <span class="list-tab-count">{{ $opportunity->products->count() }}</span></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover list-table mb-0">
                                <thead class="thead-light">
                                    <tr><th>Product</th><th class="text-end">Qty</th><th class="text-end">Unit Price</th><th class="text-end">Total</th><th></th></tr>
                                </thead>
                                <tbody>
                                    @forelse ($opportunity->products as $product)
                                        <tr>
                                            <td>{{ $product->product_name }}</td>
                                            <td class="text-end">{{ rtrim(rtrim($product->quantity, '0'), '.') }}</td>
                                            <td class="text-end">{{ number_format($product->unit_price, 2) }}</td>
                                            <td class="text-end fw-semibold">{{ number_format($product->total, 2) }}</td>
                                            <td class="text-end">
                                                <form action="{{ route('admin.crm.opportunity-products.destroy', $product) }}" method="POST" class="d-inline">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-3">No product lines yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="list-filter-panel">
                            <form method="POST" action="{{ route('admin.crm.opportunity-products.store') }}" class="row g-2 align-items-end">
                                @csrf
                                <input type="hidden" name="opportunity_id" value="{{ $opportunity->id }}">
                                <div class="col-md-5"><input type="text" name="product_name" class="form-control form-control-sm" placeholder="Product / service name" required></div>
                                <div class="col-md-2"><input type="number" step="0.01" min="0.01" name="quantity" class="form-control form-control-sm" placeholder="Qty" value="1" required></div>
                                <div class="col-md-3"><input type="number" step="0.01" min="0" name="unit_price" class="form-control form-control-sm" placeholder="Unit price" required></div>
                                <div class="col-md-2"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button></div>
                            </form>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-lg-4">
                <div class="card form-section tone-violet">
                    <div class="form-section-head form-section-head-compact">
                        <span class="form-section-icon form-section-icon-sm"><i class="ti ti-flag"></i></span>
                        <h5 class="form-section-title">Status</h5>
                    </div>
                    <div class="form-section-body">
                        <div class="status-options" role="radiogroup" aria-label="Opportunity status">
                            @foreach (\App\Models\Crm\Opportunity::STATUSES as $key => $label)
                                <label class="status-option">
                                    <input type="radio" name="status" value="{{ $key }}" @checked($currentStatus === $key)>
                                    <span class="status-option-body">
                                        <span class="status-dot status-dot-{{ $key === 'won' ? 'success' : ($key === 'lost' ? 'danger' : 'info') }}">{{ $label }}</span>
                                    </span>
                                    <i class="ti ti-circle-check status-option-check"></i>
                                </label>
                            @endforeach
                        </div>

                        <div class="mt-3" id="lost-reason-wrap" style="{{ $currentStatus === 'lost' ? '' : 'display:none' }}">
                            <label class="form-label" for="lost_reason">Lost reason</label>
                            <input type="text" class="form-control form-control-sm" id="lost_reason" name="lost_reason" value="{{ old('lost_reason', $opportunity->lost_reason) }}" placeholder="e.g. Price, Timing, Competitor">
                        </div>

                        @if ($isEditing && isset($dealApprovals) && $dealApprovals->isNotEmpty())
                            <hr class="my-3">
                            <div class="font-13 fw-semibold mb-2">Deal approvals</div>
                            @foreach ($dealApprovals as $approval)
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="font-12 text-muted">{{ $approval->rule?->name }} ({{ $approval->approver?->name ?? '—' }})</span>
                                    <span class="tag tone-{{ $approval->status === 'approved' ? 'success' : ($approval->status === 'rejected' ? 'rose' : 'amber') }} font-11">{{ \App\Models\Crm\DealApproval::STATUSES[$approval->status] ?? $approval->status }}</span>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                @if ($isEditing)
                    <div class="card form-section tone-rose">
                        <div class="form-section-head form-section-head-compact">
                            <span class="form-section-icon form-section-icon-sm"><i class="ti ti-shield"></i></span>
                            <h5 class="form-section-title">Record info</h5>
                        </div>
                        <div class="form-section-body">
                            <dl class="record-meta mb-0">
                                <dt>Created</dt>
                                <dd>{{ $opportunity->created_at?->format('M j, Y g:i A') }}</dd>
                                <dt>Last updated</dt>
                                <dd>{{ $opportunity->updated_at?->diffForHumans() }}</dd>
                                @if ($opportunity->closed_at)
                                    <dt>Closed</dt>
                                    <dd>{{ $opportunity->closed_at->format('M j, Y') }}</dd>
                                @endif
                            </dl>
                        </div>
                    </div>
                @endif

                @if ($isEditing && isset($timeline))
                    <div class="card list-view">
                        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-history"></i> Timeline <span class="list-tab-count">{{ $timeline->count() }}</span></div></div>
                        <div class="p-3" style="max-height: 320px; overflow-y: auto;">
                            @forelse ($timeline as $event)
                                <div class="d-flex align-items-start gap-2 mb-3">
                                    <span class="avatar-box avatar-sm bg-soft-primary rounded-circle flex-shrink-0">
                                        <span class="avatar-title bg-transparent"><i class="ti ti-list-check font-14"></i></span>
                                    </span>
                                    <div class="flex-grow-1">
                                        <div class="font-13 fw-semibold">{{ $event->subject }}</div>
                                        <div class="text-muted font-11">{{ $event->owner?->name ?? 'Unknown' }} &middot; {{ $event->created_at->diffForHumans() }}</div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted font-13 text-center mb-0">No activity logged yet.</p>
                            @endforelse
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="record-footer">
            <a href="{{ $cancelUrl }}" class="btn btn-light px-4">Cancel</a>
            <button type="submit" class="btn btn-de-primary text-light px-4">
                <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Create opportunity' }}
            </button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            var radios = document.querySelectorAll('input[name="status"]');
            var wrap = document.getElementById('lost-reason-wrap');
            radios.forEach(function (radio) {
                radio.addEventListener('change', function () {
                    wrap.style.display = radio.value === 'lost' && radio.checked ? '' : wrap.style.display;
                    if (radio.checked && radio.value !== 'lost') wrap.style.display = 'none';
                });
            });
        })();
    </script>
@endpush
