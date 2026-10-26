<div class="card">
    <div class="card-header">
        <h4 class="card-title">SMS Template</h4>
    </div>
    <div class="card-body">
        <div class="form-group row">
            <div class="col-md-8 mb-3">
                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $template->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
                <div class="form-check form-switch form-switch-success mt-4">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $template->exists ? $template->is_active : true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
        </div>

        <div class="form-group mb-3">
            <label class="form-label" for="body">Message <span class="text-danger">*</span></label>
            <textarea class="form-control @error('body') is-invalid @enderror" id="body" name="body" rows="4" maxlength="1600" required>{{ old('body', $template->body) }}</textarea>
            <small class="form-text text-muted">Plain text only. Use placeholders like <code>@{{name}}</code> for dynamic values.</small>
            @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="mb-4">
    <button type="submit" class="btn btn-de-primary text-light px-4">Save</button>
    <a href="{{ route('admin.sms-templates.index') }}" class="btn btn-de-danger text-light px-4">Cancel</a>
</div>
