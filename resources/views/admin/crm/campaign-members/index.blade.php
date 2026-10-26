@extends('layouts.admin')

@section('title', 'Campaign Members')

@section('content')
    <x-page-title title="Campaign Members" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-plus"></i> Add a member</div>
        </div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.crm.campaign-members.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">Campaign <span class="text-danger">*</span></label>
                    <select name="campaign_id" class="form-select form-select-sm" required>
                        <option value="">Select campaign</option>
                        @foreach ($campaigns as $campaign)
                            <option value="{{ $campaign->id }}" @selected($campaignId == $campaign->id)>{{ $campaign->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                    <select name="member_type" class="form-select form-select-sm" id="memberTypeSelect" required>
                        <option value="lead">Lead</option>
                        <option value="contact">Contact</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Member <span class="text-danger">*</span></label>
                    <select name="member_id" class="form-select form-select-sm" id="leadSelect" required>
                        <option value="">Select lead</option>
                        @foreach ($leads as $lead)
                            <option value="{{ $lead->id }}">{{ $lead->name }}</option>
                        @endforeach
                    </select>
                    <select name="member_id" class="form-select form-select-sm d-none" id="contactSelect">
                        <option value="">Select contact</option>
                        @foreach ($contacts as $contact)
                            <option value="{{ $contact->id }}">{{ $contact->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button></div>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.campaign-members.index') }}">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-users"></i> All Members <span class="list-tab-count">{{ $members->total() }}</span></div>
                <div class="list-toolbar-actions">
                    <select name="campaign_id" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto;">
                        <option value="">All Campaigns</option>
                        @foreach ($campaigns as $campaign)
                            <option value="{{ $campaign->id }}" @selected($campaignId == $campaign->id)>{{ $campaign->name }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto;">
                        <option value="">All Statuses</option>
                        @foreach ($statuses as $key => $label)
                            <option value="{{ $key }}" @selected($statusFilter === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr><th>Name</th><th>Type</th><th>Campaign</th><th>Status</th><th>Added</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr>
                </thead>
                <tbody>
                    @forelse ($members as $member)
                        <tr>
                            <td class="fw-semibold">{{ $member->member?->name ?? '—' }}</td>
                            <td><span class="tag tone-indigo">{{ array_search($member->member_type, \App\Models\Crm\CampaignMember::MEMBERABLE) ?: 'member' }}</span></td>
                            <td><a href="{{ route('admin.crm.campaigns.show', $member->campaign_id) }}">{{ $member->campaign?->name ?? '—' }}</a></td>
                            <td>
                                <form action="{{ route('admin.crm.campaign-members.status', $member) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto;">
                                        @foreach ($statuses as $key => $label)
                                            <option value="{{ $key }}" @selected($member->status === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td class="text-muted font-13">{{ $member->added_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.crm.campaign-members.destroy', $member) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this member?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-users font-24 d-block mb-2"></i>No campaign members yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($members->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $members->links() }}</div></div>
        @endif
    </div>

    <script>
        (function () {
            const typeSelect = document.getElementById('memberTypeSelect');
            const leadSelect = document.getElementById('leadSelect');
            const contactSelect = document.getElementById('contactSelect');

            function sync() {
                if (typeSelect.value === 'lead') {
                    leadSelect.classList.remove('d-none');
                    leadSelect.disabled = false;
                    contactSelect.classList.add('d-none');
                    contactSelect.disabled = true;
                } else {
                    contactSelect.classList.remove('d-none');
                    contactSelect.disabled = false;
                    leadSelect.classList.add('d-none');
                    leadSelect.disabled = true;
                }
            }

            typeSelect.addEventListener('change', sync);
            sync();
        })();
    </script>
@endsection
