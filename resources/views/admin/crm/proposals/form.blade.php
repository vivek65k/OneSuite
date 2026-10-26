@extends('layouts.admin')

@php
    $isEditing = $proposal->exists;
    $cancelUrl = route('admin.crm.proposals.index');
@endphp

@section('title', $isEditing ? 'Edit Proposal' : 'New Proposal')

@section('content')
    <x-page-title :title="$isEditing ? 'Edit Proposal' : 'New Proposal'" :breadcrumbs="['Proposals' => $cancelUrl]" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <form method="POST" action="{{ $isEditing ? route('admin.crm.proposals.update', $proposal) : route('admin.crm.proposals.store') }}">
        @csrf
        @if ($isEditing) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card form-section tone-indigo">
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-file-text"></i></span>
                        <div><h5 class="form-section-title">Proposal</h5></div>
                    </div>
                    <div class="form-section-body">
                        <label class="form-label" for="title">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $proposal->title) }}" required>

                        <label class="form-label mt-3" for="content">Content</label>
                        <textarea name="content" id="content" rows="14" class="form-control" placeholder="Write the proposal content here...">{{ old('content', $proposal->content) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="record-sidebar">
                    <div class="card form-section tone-violet">
                        <div class="form-section-head form-section-head-compact">
                            <span class="form-section-icon form-section-icon-sm"><i class="ti ti-tag"></i></span>
                            <h5 class="form-section-title">Linked to</h5>
                        </div>
                        <div class="form-section-body">
                            <label class="form-label" for="account_id">Account</label>
                            <select class="form-select" id="account_id" name="account_id">
                                <option value="">— None —</option>
                                @foreach ($accounts as $id => $name)
                                    <option value="{{ $id }}" @selected((string) old('account_id', $proposal->account_id) === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </select>

                            <label class="form-label mt-3" for="quotation_id">Quotation</label>
                            <select class="form-select" id="quotation_id" name="quotation_id">
                                <option value="">— None —</option>
                                @foreach ($quotations as $id => $number)
                                    <option value="{{ $id }}" @selected((string) old('quotation_id', $proposal->quotation_id) === (string) $id)>{{ $number }}</option>
                                @endforeach
                            </select>

                            <label class="form-label mt-3" for="owner_employee_id">Owner</label>
                            <select class="form-select" id="owner_employee_id" name="owner_employee_id">
                                <option value="">— Unassigned —</option>
                                @foreach ($employees as $id => $name)
                                    <option value="{{ $id }}" @selected((string) old('owner_employee_id', $proposal->owner_employee_id) === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if ($isEditing)
                        <div class="card form-section tone-amber">
                            <div class="form-section-head form-section-head-compact">
                                <span class="form-section-icon form-section-icon-sm"><i class="ti ti-flag"></i></span>
                                <h5 class="form-section-title">Status: {{ \App\Models\Crm\Proposal::STATUSES[$proposal->status] ?? $proposal->status }}</h5>
                            </div>
                            <div class="form-section-body d-grid gap-2">
                                @if ($proposal->status === 'draft')
                                    <button type="submit" form="send-proposal-form" class="btn btn-de-primary btn-sm text-light"><i class="ti ti-send font-14 me-1"></i> Mark as Sent</button>
                                @endif
                                @if ($proposal->status === 'sent')
                                    <button type="submit" form="accept-proposal-form" class="btn btn-sm review-btn is-approve"><i class="ti ti-check"></i> Accepted</button>
                                    <button type="submit" form="decline-proposal-form" class="btn btn-sm review-btn is-reject"><i class="ti ti-x"></i> Declined</button>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="record-footer">
            <a href="{{ $cancelUrl }}" class="btn btn-light px-4">Cancel</a>
            <button type="submit" class="btn btn-de-primary text-light px-4">
                <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Create proposal' }}
            </button>
        </div>
    </form>

    @if ($isEditing)
        <form id="send-proposal-form" action="{{ route('admin.crm.proposals.send', $proposal) }}" method="POST" class="d-none">@csrf</form>
        <form id="accept-proposal-form" action="{{ route('admin.crm.proposals.decision', $proposal) }}" method="POST" class="d-none">@csrf<input type="hidden" name="decision" value="accepted"></form>
        <form id="decline-proposal-form" action="{{ route('admin.crm.proposals.decision', $proposal) }}" method="POST" class="d-none">@csrf<input type="hidden" name="decision" value="declined"></form>
    @endif
@endsection
