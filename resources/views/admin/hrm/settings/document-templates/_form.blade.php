<div class="card">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $template->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Document Type</label>
                <select class="form-select" name="type">
                    <option value="">Not linked to a type</option>
                    @foreach (\App\Models\EmployeeDocument::TYPES as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', $template->type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <div class="form-check form-switch form-switch-success mt-4">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $template->exists ? $template->is_active : true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label">Body <span class="text-danger">*</span></label>
                <textarea class="form-control @error('body') is-invalid @enderror" name="body" rows="12" placeholder="Use placeholders like @{{employee_name}}, @{{designation}}, @{{joining_date}}...">{{ old('body', $template->body) }}</textarea>
                @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">A reusable wording template HR can copy from when issuing documents. Placeholders are filled in manually — this doesn't auto-generate the final file.</div>
            </div>
        </div>
    </div>
</div>

<div class="mb-4">
    <button type="submit" class="btn btn-de-primary text-light px-4">Save</button>
    <a href="{{ route('admin.document-templates.index') }}" class="btn btn-de-danger text-light px-4">Cancel</a>
</div>
