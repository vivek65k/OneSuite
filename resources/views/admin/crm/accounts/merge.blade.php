@extends('layouts.admin')

@section('title', 'Merge Accounts')

@section('content')
    <x-page-title title="Merge Accounts" :breadcrumbs="['Accounts' => route('admin.crm.accounts.index')]" />

    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="card form-section tone-rose" style="max-width: 620px;">
        <div class="form-section-head">
            <span class="form-section-icon"><i class="ti ti-arrows-join"></i></span>
            <div>
                <h5 class="form-section-title">Merge duplicate accounts</h5>
                <p class="form-section-sub">Contacts, opportunities, cases and quotes move to the surviving account. The duplicate is deleted. This cannot be undone.</p>
            </div>
        </div>
        <div class="form-section-body">
            <form method="POST" action="{{ route('admin.crm.accounts.merge.confirm') }}" onsubmit="return confirm('Merge these accounts? This cannot be undone.');">
                @csrf
                <label class="form-label">Keep this account (survivor)</label>
                <select name="survivor_id" class="form-select mb-3" required>
                    <option value="">Select account</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                    @endforeach
                </select>

                <label class="form-label">Merge and delete this account (duplicate)</label>
                <select name="duplicate_id" class="form-select mb-3" required>
                    <option value="">Select account</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                    @endforeach
                </select>

                <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-arrows-join font-14 me-1"></i> Merge Accounts</button>
                <a href="{{ route('admin.crm.accounts.index') }}" class="btn btn-light">Cancel</a>
            </form>
        </div>
    </div>
@endsection
