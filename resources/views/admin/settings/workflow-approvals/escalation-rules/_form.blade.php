<div class="card">
    <div class="card-header">
        <h4 class="card-title">Escalation Rule</h4>
    </div>
    <div class="card-body">
        <div class="form-group row">
            <div class="col-md-4 mb-3">
                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $rule->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="module">Applies to</label>
                <input type="text" class="form-control" id="module" name="module" value="{{ old('module', $rule->module) }}" placeholder="e.g. Purchase Order">
            </div>
            <div class="col-md-4 mb-3">
                <div class="form-check form-switch form-switch-success mt-4">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $rule->exists ? $rule->is_active : true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
        </div>

        <div class="form-group row">
            <div class="col-md-4 mb-3">
                <label class="form-label" for="after_hours">Escalate after (hours) <span class="text-danger">*</span></label>
                <input type="number" min="1" class="form-control @error('after_hours') is-invalid @enderror" id="after_hours" name="after_hours" value="{{ old('after_hours', $rule->after_hours) }}" required>
                @error('after_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="escalate_to_role">Escalate to role <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('escalate_to_role') is-invalid @enderror" id="escalate_to_role" name="escalate_to_role" value="{{ old('escalate_to_role', $rule->escalate_to_role) }}" placeholder="e.g. Department Head" required>
                @error('escalate_to_role')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

<div class="mb-4">
    <button type="submit" class="btn btn-de-primary text-light px-4">Save</button>
    <a href="{{ route('admin.escalation-rules.index') }}" class="btn btn-de-danger text-light px-4">Cancel</a>
</div>
