<div class="card">
    <div class="card-header">
        <h4 class="card-title">Approval Rule</h4>
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
                <label class="form-label" for="department_id">Department</label>
                <select class="form-select" id="department_id" name="department_id">
                    <option value="">Any</option>
                    @foreach ($departments as $id => $name)
                        <option value="{{ $id }}" @selected((int) old('department_id', $rule->department_id) === $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-group row">
            <div class="col-md-3 mb-3">
                <label class="form-label" for="min_amount">Min amount</label>
                <input type="number" step="0.01" min="0" class="form-control @error('min_amount') is-invalid @enderror" id="min_amount" name="min_amount" value="{{ old('min_amount', $rule->min_amount) }}">
                @error('min_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" for="max_amount">Max amount</label>
                <input type="number" step="0.01" min="0" class="form-control @error('max_amount') is-invalid @enderror" id="max_amount" name="max_amount" value="{{ old('max_amount', $rule->max_amount) }}">
                @error('max_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" for="approver_role">Approver role <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('approver_role') is-invalid @enderror" id="approver_role" name="approver_role" value="{{ old('approver_role', $rule->approver_role) }}" placeholder="e.g. Manager" required>
                @error('approver_role')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" for="level">Level <span class="text-danger">*</span></label>
                <input type="number" min="1" class="form-control @error('level') is-invalid @enderror" id="level" name="level" value="{{ old('level', $rule->level ?: 1) }}" required>
                <small class="form-text text-muted">Lower runs first.</small>
                @error('level')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="form-group">
            <div class="form-check form-switch form-switch-success">
                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $rule->exists ? $rule->is_active : true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Active</label>
            </div>
        </div>
    </div>
</div>

<div class="mb-4">
    <button type="submit" class="btn btn-de-primary text-light px-4">Save</button>
    <a href="{{ route('admin.approval-rules.index') }}" class="btn btn-de-danger text-light px-4">Cancel</a>
</div>
