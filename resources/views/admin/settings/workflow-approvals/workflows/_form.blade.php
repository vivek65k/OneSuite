@php
    $existingSteps = $steps ?? collect();
@endphp

<div class="card">
    <div class="card-header">
        <h4 class="card-title">Workflow</h4>
    </div>
    <div class="card-body">
        <div class="form-group row">
            <div class="col-md-4 mb-3">
                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $workflow->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="module">Applies to</label>
                <input type="text" class="form-control" id="module" name="module" value="{{ old('module', $workflow->module) }}" placeholder="e.g. Purchase Order">
            </div>
            <div class="col-md-4 mb-3">
                <div class="form-check form-switch form-switch-success mt-4">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $workflow->exists ? $workflow->is_active : true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
        </div>

        <div class="form-group mb-3">
            <label class="form-label" for="description">Description</label>
            <textarea class="form-control" id="description" name="description" rows="2">{{ old('description', $workflow->description) }}</textarea>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">Steps</h4>
        <button type="button" class="btn btn-de-primary btn-sm text-light px-3" id="add-step"><i class="ti ti-plus font-14 me-1"></i> Add step</button>
    </div>
    <div class="card-body">
        <p class="text-muted font-13">Steps run in the order listed below, top to bottom.</p>
        <table class="table mb-0" id="steps-table">
            <thead class="thead-light">
                <tr>
                    <th style="width: 40px">#</th>
                    <th>Step name</th>
                    <th>Approver role</th>
                    <th style="width: 40px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($existingSteps as $step)
                    <tr class="step-row">
                        <td class="step-number align-middle"></td>
                        <td><input type="text" class="form-control" name="steps[{{ $loop->index }}][name]" value="{{ $step->name }}" placeholder="e.g. Manager Approval"></td>
                        <td><input type="text" class="form-control" name="steps[{{ $loop->index }}][approver_role]" value="{{ $step->approver_role }}" placeholder="e.g. Manager"></td>
                        <td class="text-end align-middle"><button type="button" class="btn-icon remove-step" title="Remove"><i class="ti ti-trash font-16"></i></button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="text-muted font-13 mb-0 d-none" id="no-steps-message">No steps yet — click "Add step" to define the approval flow.</p>
    </div>
</div>

<div class="mb-4">
    <button type="submit" class="btn btn-de-primary text-light px-4">Save</button>
    <a href="{{ route('admin.workflows.index') }}" class="btn btn-de-danger text-light px-4">Cancel</a>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var table = document.getElementById('steps-table');
            var tbody = table.querySelector('tbody');
            var noStepsMessage = document.getElementById('no-steps-message');
            var nextIndex = tbody.querySelectorAll('.step-row').length;

            function renumber() {
                tbody.querySelectorAll('.step-row').forEach(function (row, i) {
                    row.querySelector('.step-number').textContent = i + 1;
                });
                noStepsMessage.classList.toggle('d-none', tbody.querySelectorAll('.step-row').length > 0);
            }

            function addRow() {
                var row = document.createElement('tr');
                row.className = 'step-row';
                row.innerHTML =
                    '<td class="step-number align-middle"></td>' +
                    '<td><input type="text" class="form-control" name="steps[' + nextIndex + '][name]" placeholder="e.g. Manager Approval"></td>' +
                    '<td><input type="text" class="form-control" name="steps[' + nextIndex + '][approver_role]" placeholder="e.g. Manager"></td>' +
                    '<td class="text-end align-middle"><button type="button" class="btn-icon remove-step" title="Remove"><i class="ti ti-trash font-16"></i></button></td>';
                tbody.appendChild(row);
                nextIndex++;
                renumber();
            }

            document.getElementById('add-step').addEventListener('click', addRow);

            tbody.addEventListener('click', function (e) {
                var button = e.target.closest('.remove-step');
                if (!button) {
                    return;
                }
                button.closest('.step-row').remove();
                renumber();
            });

            renumber();
        });
    </script>
@endpush
