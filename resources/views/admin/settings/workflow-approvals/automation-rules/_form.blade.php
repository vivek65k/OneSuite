<div class="card">
    <div class="card-header">
        <h4 class="card-title">Automation Rule</h4>
    </div>
    <div class="card-body">
        <div class="form-group row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $rule->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-check form-switch form-switch-success mt-4">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $rule->exists ? $rule->is_active : true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
        </div>

        <div class="form-group row align-items-end">
            <div class="col-md-1 text-center mb-3 d-none d-md-block">
                <span class="badge badge-soft-warning">IF</span>
            </div>
            <div class="col-md-5 mb-3">
                <label class="form-label" for="trigger_event">Trigger event <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('trigger_event') is-invalid @enderror" id="trigger_event" name="trigger_event" value="{{ old('trigger_event', $rule->trigger_event) }}" placeholder="e.g. Invoice Overdue" required>
                @error('trigger_event')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-1 text-center mb-3 d-none d-md-block">
                <span class="badge badge-soft-success">THEN</span>
            </div>
            <div class="col-md-5 mb-3">
                <label class="form-label" for="action">Action <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('action') is-invalid @enderror" id="action" name="action" value="{{ old('action', $rule->action) }}" placeholder="e.g. Send Email" required>
                @error('action')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="form-group row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="action_target">Action target</label>
                <input type="text" class="form-control" id="action_target" name="action_target" value="{{ old('action_target', $rule->action_target) }}" placeholder="e.g. a role, template, or email address">
                <small class="form-text text-muted">Who or what the action applies to, if needed.</small>
            </div>
        </div>
    </div>
</div>

<div class="mb-4">
    <button type="submit" class="btn btn-de-primary text-light px-4">Save</button>
    <a href="{{ route('admin.automation-rules.index') }}" class="btn btn-de-danger text-light px-4">Cancel</a>
</div>
