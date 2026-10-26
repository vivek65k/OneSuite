@extends('layouts.admin')

@section('title', 'Customer 360° View')

@section('content')
    <x-page-title title="Customer 360° View" />

    <div class="card list-view mb-3">
        <div class="list-filter-panel">
            <form method="GET" action="{{ route('admin.crm.customer-360.index') }}" class="d-flex gap-2 align-items-end">
                <div class="flex-fill">
                    <label class="form-label">Account</label>
                    <select name="account_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        @forelse ($accounts as $id => $name)
                            <option value="{{ $id }}" @selected($account && $account->id === $id)>{{ $name }}</option>
                        @empty
                            <option value="">No accounts yet</option>
                        @endforelse
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if (! $account)
        <div class="card">
            <div class="empty-state">
                <span class="empty-state-icon tone-indigo"><i class="ti ti-eye"></i></span>
                <h5>No account to show</h5>
                <p>Add an account first, then its 360° view will appear here.</p>
                <a href="{{ route('admin.crm.accounts.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add Account</a>
            </div>
        </div>
    @else
        <div class="record-header">
            <span class="list-avatar list-avatar-square record-avatar tone-indigo" aria-hidden="true"><i class="ti ti-building font-20"></i></span>
            <div class="record-header-text">
                <div class="record-header-title">{{ $account->name }}</div>
                <div class="record-header-meta">
                    @if ($account->industry)<span>{{ $account->industry }}</span>@endif
                    @if ($account->segment)<span class="tag tone-violet">{{ $account->segment->name }}</span>@endif
                    @if ($account->owner)<span>Owner: {{ $account->owner->name }}</span>@endif
                </div>
            </div>
            <div class="record-header-actions">
                <a href="{{ route('admin.crm.customer-timeline.index', ['account_id' => $account->id]) }}" class="btn btn-light btn-sm"><i class="ti ti-clock font-14 me-1"></i> Timeline</a>
                <a href="{{ route('admin.crm.accounts.edit', $account) }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-edit font-14 me-1"></i> Edit</a>
            </div>
        </div>

        <div class="kpi-strip">
            <x-kpi-card label="Contacts" :value="$account->contacts_count" icon="ti-users" tone="indigo" />
            <x-kpi-card label="Child Accounts" :value="$account->children->count()" icon="ti-sitemap" tone="teal" />
            <x-kpi-card label="Parent Account" :value="$account->parent?->name ?? '—'" icon="ti-corner-left-up" tone="violet" />
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card list-view tone-indigo mb-0 h-100">
                    <div class="list-toolbar">
                        <div class="list-toolbar-title"><i class="ti ti-building"></i> Account details</div>
                    </div>
                    <div class="card-body">
                        <dl class="record-meta mb-0">
                            <dt>Website</dt>
                            <dd>{{ $account->website ?? '—' }}</dd>
                            <dt>Phone</dt>
                            <dd>{{ $account->phone ?? '—' }}</dd>
                            <dt>Email</dt>
                            <dd>{{ $account->email ?? '—' }}</dd>
                            <dt>Billing Address</dt>
                            <dd style="white-space: pre-wrap;">{{ $account->billing_address ?? $account->address ?? '—' }}</dd>
                            <dt>Shipping Address</dt>
                            <dd style="white-space: pre-wrap;">{{ $account->shipping_address ?? $account->address ?? '—' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card list-view tone-teal mb-0 h-100">
                    <div class="list-toolbar">
                        <div class="list-toolbar-title"><i class="ti ti-users"></i> Contacts <span class="list-tab-count">{{ $account->contacts->count() }}</span></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover list-table mb-0">
                            <tbody>
                                @forelse ($account->contacts as $contact)
                                    <tr>
                                        <td>
                                            <div class="list-user">
                                                <x-avatar :person="$contact" />
                                                <div class="list-user-text">
                                                    <div class="list-user-name"><a href="{{ route('admin.crm.contacts.show', $contact) }}">{{ $contact->name }}</a></div>
                                                    <div class="list-user-meta">{{ $contact->job_title ?? $contact->email ?? '—' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end">{{ $contact->owner?->name ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="text-center text-muted py-4">No contacts linked to this account.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-0">
            <div class="col-lg-6">
                <div class="card list-view mb-0 h-100">
                    <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-briefcase"></i> Related Deals &amp; Quotes</div></div>
                    <div class="table-responsive">
                        <table class="table table-hover list-table mb-0">
                            <tbody>
                                @foreach ($opportunities as $opp)
                                    <tr><td><i class="ti ti-briefcase text-muted me-1"></i> <a href="{{ route('admin.crm.opportunities.edit', $opp) }}">{{ $opp->name }}</a></td><td class="text-muted font-13">{{ $opp->stage?->name }}</td><td class="text-end">{{ number_format($opp->amount, 0) }}</td></tr>
                                @endforeach
                                @foreach ($quotations as $quote)
                                    <tr><td><i class="ti ti-file-text text-muted me-1"></i> <a href="{{ route('admin.crm.quotations.edit', $quote) }}">{{ $quote->quotation_number }}</a></td><td class="text-muted font-13">{{ \App\Models\Crm\Quotation::STATUSES[$quote->status] ?? $quote->status }}</td><td class="text-end">{{ number_format($quote->total, 0) }}</td></tr>
                                @endforeach
                                @if ($opportunities->isEmpty() && $quotations->isEmpty())
                                    <tr><td class="text-center text-muted py-4">No deals or quotes yet.</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card list-view mb-0 h-100">
                    <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-ticket"></i> Service Cases</div></div>
                    <div class="table-responsive">
                        <table class="table table-hover list-table mb-0">
                            <tbody>
                                @forelse ($cases as $case)
                                    <tr><td><a href="{{ route('admin.crm.cases.edit', $case) }}">{{ $case->subject }}</a></td><td class="text-end"><span class="tag tone-{{ $case->status === 'resolved' || $case->status === 'closed' ? 'success' : 'sky' }}">{{ \App\Models\Crm\CaseModel::STATUSES[$case->status] ?? $case->status }}</span></td></tr>
                                @empty
                                    <tr><td class="text-center text-muted py-4">No service cases yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card list-view mt-3">
            <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-notes"></i> Notes &amp; Documents <span class="list-tab-count">{{ $notes->count() }}</span></div></div>
            <div class="p-3">
                @forelse ($notes as $note)
                    <div class="mb-2">
                        <div class="font-13">{{ $note->body }}</div>
                        <div class="text-muted font-11">{{ $note->owner?->name ?? 'Unknown' }} &middot; {{ $note->created_at->format('M j, Y') }}
                            @if ($note->file_path)&middot; <a href="{{ asset('storage/'.$note->file_path) }}" target="_blank">{{ $note->file_name }}</a>@endif
                        </div>
                    </div>
                @empty
                    <p class="text-muted text-center mb-0">No notes or documents yet.</p>
                @endforelse
            </div>
        </div>
    @endif
@endsection
