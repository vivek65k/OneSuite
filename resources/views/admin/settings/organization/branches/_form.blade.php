<div class="card">
    <div class="card-header">
        <h4 class="card-title">Branch</h4>
    </div>
    <div class="card-body">
        <div class="form-group row">
            <div class="col-md-4 mb-3">
                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $branch->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="code">Code</label>
                <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $branch->code) }}">
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="phone">Phone</label>
                <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $branch->phone) }}">
            </div>
        </div>

        <div class="form-group row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="email">Email</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $branch->email) }}">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3 mb-3">
                <div class="form-check form-switch form-switch-success mt-4">
                    <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1" {{ old('is_default', $branch->is_default) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_default">Default branch</label>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="form-check form-switch form-switch-success mt-4">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $branch->exists ? $branch->is_active : true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title">Address</h4>
    </div>
    <div class="card-body">
        <div class="form-group row">
            <div class="col-12 mb-3">
                <label class="form-label" for="address">Address</label>
                <input type="text" class="form-control" id="address" name="address" value="{{ old('address', $branch->address) }}">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" for="city">City</label>
                <input type="text" class="form-control" id="city" name="city" value="{{ old('city', $branch->city) }}">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" for="state">State</label>
                <input type="text" class="form-control" id="state" name="state" value="{{ old('state', $branch->state) }}">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" for="country">Country</label>
                <input type="text" class="form-control" id="country" name="country" value="{{ old('country', $branch->country) }}">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" for="postal_code">Postal code</label>
                <input type="text" class="form-control" id="postal_code" name="postal_code" value="{{ old('postal_code', $branch->postal_code) }}">
            </div>
        </div>
    </div>
</div>

<div class="mb-4">
    <button type="submit" class="btn btn-de-primary text-light px-4">Save</button>
    <a href="{{ route('admin.branches.index') }}" class="btn btn-de-danger text-light px-4">Cancel</a>
</div>
