<div class="card">
    <div class="card-header">
        <h4 class="card-title">Department</h4>
    </div>
    <div class="card-body">
        <div class="form-group row">
            <div class="col-md-4 mb-3">
                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $department->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="code">Code</label>
                <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $department->code) }}">
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="parent_id">Parent department</label>
                <select class="form-select @error('parent_id') is-invalid @enderror" id="parent_id" name="parent_id">
                    <option value="">None</option>
                    @foreach ($parents as $id => $name)
                        <option value="{{ $id }}" @selected((int) old('parent_id', $department->parent_id) === $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('parent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="form-group row">
            <div class="col-md-4 mb-3">
                <label class="form-label" for="branch_id">Branch <span class="text-danger">*</span></label>
                <select class="form-select @error('branch_id') is-invalid @enderror" id="branch_id" name="branch_id" required>
                    <option value="">Select a branch</option>
                    @foreach ($branches as $id => $name)
                        <option value="{{ $id }}" @selected((int) old('branch_id', $department->branch_id) === $id)>{{ $name }}</option>
                    @endforeach
                </select>
                <small class="form-text text-muted">Which branch this department belongs to.</small>
                @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="form-group row">
            <div class="col-md-9 mb-3">
                <label class="form-label" for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="2">{{ old('description', $department->description) }}</textarea>
            </div>
            <div class="col-md-3 mb-3">
                <div class="form-check form-switch form-switch-success mt-4">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $department->exists ? $department->is_active : true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mb-4">
    <button type="submit" class="btn btn-de-primary text-light px-4">Save</button>
    <a href="{{ route('admin.departments.index') }}" class="btn btn-de-danger text-light px-4">Cancel</a>
</div>
