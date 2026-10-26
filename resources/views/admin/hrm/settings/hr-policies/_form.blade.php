<div class="card">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('title') is-invalid @enderror" name="title" value="{{ old('title', $policy->title) }}" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Category</label>
                <input type="text" class="form-control" name="category" value="{{ old('category', $policy->category) }}" placeholder="e.g. Conduct">
            </div>
            <div class="col-md-3">
                <label class="form-label">Effective Date</label>
                <input type="date" class="form-control" name="effective_date" value="{{ old('effective_date', optional($policy->effective_date)->format('Y-m-d')) }}">
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="3">{{ old('description', $policy->description) }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Document File</label>
                <input type="file" class="form-control" name="file">
                @if ($policy->file_path)
                    <div class="form-text">Current: <a href="{{ asset('storage/'.$policy->file_path) }}" target="_blank">View file</a> (uploading a new one replaces it)</div>
                @endif
            </div>
            <div class="col-md-6">
                <div class="form-check form-switch form-switch-success mt-4">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $policy->exists ? $policy->is_active : true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mb-4">
    <button type="submit" class="btn btn-de-primary text-light px-4">Save</button>
    <a href="{{ route('admin.hr-policies.index') }}" class="btn btn-de-danger text-light px-4">Cancel</a>
</div>
