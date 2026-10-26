<div class="card">
    <div class="card-header">
        <h4 class="card-title">Trainer</h4>
    </div>
    <div class="card-body">
        <div class="form-group row">
            <div class="col-md-4 mb-3">
                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $trainer->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="email">Email</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $trainer->email) }}">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="phone">Phone</label>
                <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $trainer->phone) }}">
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="form-group row">
            <div class="col-md-4 mb-3">
                <label class="form-label" for="specialization">Specialization</label>
                <input type="text" class="form-control @error('specialization') is-invalid @enderror" id="specialization" name="specialization" value="{{ old('specialization', $trainer->specialization) }}" placeholder="e.g. Leadership, Technical Skills">
                @error('specialization')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
                <div class="form-check form-switch form-switch-success mt-4">
                    <input class="form-check-input" type="checkbox" id="is_internal" name="is_internal" value="1" {{ old('is_internal', $trainer->is_internal) ? 'checked' : '' }} onchange="document.getElementById('employee_id_wrap').classList.toggle('d-none', !this.checked)">
                    <label class="form-check-label" for="is_internal">Internal trainer (employee)</label>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="form-check form-switch form-switch-success mt-4">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $trainer->exists ? $trainer->is_active : true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
        </div>
        <div class="form-group row">
            <div class="col-md-4 mb-3 {{ old('is_internal', $trainer->is_internal) ? '' : 'd-none' }}" id="employee_id_wrap">
                <label class="form-label" for="employee_id">Employee</label>
                <select class="form-select @error('employee_id') is-invalid @enderror" id="employee_id" name="employee_id">
                    <option value="">Select employee</option>
                    @foreach ($employees as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('employee_id', $trainer->employee_id) === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

<div class="mb-4">
    <button type="submit" class="btn btn-de-primary text-light px-4">Save</button>
    <a href="{{ route('admin.trainers.index') }}" class="btn btn-de-danger text-light px-4">Cancel</a>
</div>
