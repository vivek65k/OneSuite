@extends('layouts.admin')

@section('title', 'E-signatures')

@section('content')
    <x-page-title title="E-signatures" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Total Requests" :value="$total" icon="ti-signature" tone="indigo" />
        <x-kpi-card label="Pending" :value="$pendingCount" icon="ti-clock" tone="amber" />
        <x-kpi-card label="Signed" :value="$signedCount" icon="ti-circle-check" tone="success" />
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-send"></i> Send a signature request</div>
        </div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.crm.esignatures.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-2">
                    <label class="form-label">Document type</label>
                    <select name="signable_type" class="form-select form-select-sm" id="sig-type" required>
                        <option value="contract">Contract</option>
                        <option value="proposal">Proposal</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Document</label>
                    <select name="signable_id" class="form-select form-select-sm" id="sig-contract" required>
                        @foreach ($contracts as $id => $title)
                            <option value="{{ $id }}">{{ $title }}</option>
                        @endforeach
                    </select>
                    <select name="signable_id" class="form-select form-select-sm d-none" id="sig-proposal" disabled>
                        @foreach ($proposals as $id => $title)
                            <option value="{{ $id }}">{{ $title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">Signer name</label><input type="text" name="signer_name" class="form-control form-control-sm" required></div>
                <div class="col-md-3"><label class="form-label">Signer email</label><input type="email" name="signer_email" class="form-control form-control-sm" required></div>
                <div class="col-md-1"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Send</button></div>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr><th>Document</th><th>Signer</th><th>Status</th><th>Sent</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr>
                </thead>
                <tbody>
                    @forelse ($requests as $req)
                        <tr>
                            <td class="fw-semibold">{{ $req->signable?->title ?? $req->signable?->contract_number ?? '—' }}</td>
                            <td>
                                <div class="list-user-text">
                                    <div class="list-user-name">{{ $req->signer_name }}</div>
                                    <div class="list-user-meta">{{ $req->signer_email }}</div>
                                </div>
                            </td>
                            <td><span class="tag tone-{{ $req->status === 'signed' ? 'success' : ($req->status === 'declined' ? 'rose' : 'amber') }}">{{ \App\Models\Crm\ESignatureRequest::STATUSES[$req->status] ?? $req->status }}</span></td>
                            <td>{{ $req->sent_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="text-end">
                                @if ($req->status === 'pending')
                                    <form action="{{ route('admin.crm.esignatures.signed', $req) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn-icon btn-icon-success" title="Mark signed"><i class="ti ti-check font-16"></i></button>
                                    </form>
                                    <form action="{{ route('admin.crm.esignatures.declined', $req) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn-icon btn-icon-danger" title="Mark declined"><i class="ti ti-x font-16"></i></button>
                                    </form>
                                @endif
                                <form action="{{ route('admin.crm.esignatures.destroy', $req) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this request?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5"><i class="ti ti-signature font-24 d-block mb-2"></i>No signature requests yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($requests->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $requests->links() }}</div></div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var typeSelect = document.getElementById('sig-type');
            var contractSelect = document.getElementById('sig-contract');
            var proposalSelect = document.getElementById('sig-proposal');
            if (!typeSelect) return;
            typeSelect.addEventListener('change', function () {
                if (typeSelect.value === 'contract') {
                    contractSelect.classList.remove('d-none'); contractSelect.disabled = false;
                    proposalSelect.classList.add('d-none'); proposalSelect.disabled = true;
                } else {
                    proposalSelect.classList.remove('d-none'); proposalSelect.disabled = false;
                    contractSelect.classList.add('d-none'); contractSelect.disabled = true;
                }
            });
        })();
    </script>
@endpush
