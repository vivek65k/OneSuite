@extends('layouts.admin')

@section('title', 'Add Price Book')

@section('content')
    <x-page-title title="Add Price Book" :breadcrumbs="['Price Books' => route('admin.crm.price-books.index')]" />

    <div class="card form-section tone-indigo" style="max-width: 560px;">
        <div class="form-section-head">
            <span class="form-section-icon"><i class="ti ti-book"></i></span>
            <div>
                <h5 class="form-section-title">New price book</h5>
                <p class="form-section-sub">A named price list, e.g. "Standard", "Enterprise", "Partner".</p>
            </div>
        </div>
        <div class="form-section-body">
            <form method="POST" action="{{ route('admin.crm.price-books.store') }}">
                @csrf

                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required autofocus>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror

                <label class="form-label mt-3" for="currency">Currency</label>
                <input type="text" class="form-control @error('currency') is-invalid @enderror" id="currency" name="currency" value="{{ old('currency', 'INR') }}" required>
                @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror

                <div class="form-check mt-3">
                    <input type="checkbox" class="form-check-input" id="is_default" name="is_default" value="1" @checked(old('is_default'))>
                    <label class="form-check-label" for="is_default">Make this the default price book</label>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-de-primary text-light px-4">Create price book</button>
                    <a href="{{ route('admin.crm.price-books.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
