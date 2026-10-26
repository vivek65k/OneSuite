@extends('layouts.admin')

@php
    $isEditing = $workflow->exists;
@endphp

@section('title', $isEditing ? 'Edit Workflow' : 'New Workflow')

@section('content')
    <x-page-title :title="$isEditing ? 'Edit Workflow' : 'New Workflow'" :breadcrumbs="['Workflows' => route('admin.crm.workflows.index')]" />

    <div class="card form-section tone-indigo" style="max-width: 760px;">
        <div class="form-section-head">
            <span class="form-section-icon"><i class="ti ti-git-branch"></i></span>
            <div>
                <h5 class="form-section-title">{{ $isEditing ? 'Edit workflow' : 'New workflow' }}</h5>
                <p class="form-section-sub">Chain steps together to run automatically when the trigger event fires.</p>
            </div>
        </div>
        <div class="form-section-body">
            <form method="POST" action="{{ $isEditing ? route('admin.crm.workflows.update', $workflow) : route('admin.crm.workflows.store') }}" id="workflowForm">
                @csrf
                @if ($isEditing) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $workflow->name) }}" required autofocus>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Trigger Event</label>
                        <select name="trigger_event" class="form-select">
                            <option value="">None</option>
                            @foreach (\App\Models\Crm\AutomationRule::TRIGGER_EVENTS as $key => $label)
                                <option value="{{ $key }}" @selected(old('trigger_event', $workflow->trigger_event) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label d-flex justify-content-between align-items-center">
                            <span>Steps</span>
                            <button type="button" class="btn btn-light btn-sm" id="addStepBtn"><i class="ti ti-plus font-14 me-1"></i> Add Step</button>
                        </label>
                        <div id="stepsBuilder"></div>
                        <input type="hidden" name="steps_json" id="stepsJsonInput">
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-de-primary text-light px-4">{{ $isEditing ? 'Save changes' : 'Create workflow' }}</button>
                    <a href="{{ route('admin.crm.workflows.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const builder = document.getElementById('stepsBuilder');
            const addBtn = document.getElementById('addStepBtn');
            const hiddenInput = document.getElementById('stepsJsonInput');
            const form = document.getElementById('workflowForm');
            const stepTypes = @json($stepTypes);
            const initial = @json($steps->map(fn($s) => ['step_type' => $s->step_type, 'summary' => $s->summary])->values());

            function rowHtml(step) {
                step = step || { step_type: Object.keys(stepTypes)[0], summary: '' };
                const options = Object.entries(stepTypes).map(([k, v]) => `<option value="${k}" ${step.step_type === k ? 'selected' : ''}>${v}</option>`).join('');
                return `<div class="row g-2 align-items-end mb-2 step-row">
                    <div class="col-md-3"><select class="form-select form-select-sm step-type">${options}</select></div>
                    <div class="col-md-8"><input type="text" class="form-control form-control-sm step-summary" placeholder="e.g. Send welcome email to new lead" value="${step.summary || ''}"></div>
                    <div class="col-md-1"><button type="button" class="btn-icon btn-icon-danger remove-step" title="Remove"><i class="ti ti-trash font-16"></i></button></div>
                </div>`;
            }

            function addRow(step) { builder.insertAdjacentHTML('beforeend', rowHtml(step)); }

            builder.addEventListener('click', function (e) {
                const btn = e.target.closest('.remove-step');
                if (btn) btn.closest('.step-row').remove();
            });

            addBtn.addEventListener('click', function () { addRow(); });
            (initial.length ? initial : []).forEach(addRow);

            form.addEventListener('submit', function () {
                const rows = [...builder.querySelectorAll('.step-row')].map(row => ({
                    step_type: row.querySelector('.step-type').value,
                    summary: row.querySelector('.step-summary').value.trim(),
                }));
                hiddenInput.value = JSON.stringify(rows);
            });
        })();
    </script>
@endsection
