@extends('layouts.admin')

@php
    $isEditing = $sequence->exists;
@endphp

@section('title', $isEditing ? 'Edit Sequence' : 'New Sequence')

@section('content')
    <x-page-title :title="$isEditing ? 'Edit Sequence' : 'New Sequence'" :breadcrumbs="['Email Sequences' => route('admin.crm.email-sequences.index')]" />

    <div class="card form-section tone-indigo" style="max-width: 820px;">
        <div class="form-section-head">
            <span class="form-section-icon"><i class="ti ti-mail-forward"></i></span>
            <div>
                <h5 class="form-section-title">{{ $isEditing ? 'Edit sequence' : 'New sequence' }}</h5>
                <p class="form-section-sub">Each step sends after the given number of days from the previous one.</p>
            </div>
        </div>
        <div class="form-section-body">
            <form method="POST" action="{{ $isEditing ? route('admin.crm.email-sequences.update', $sequence) : route('admin.crm.email-sequences.store') }}" id="sequenceForm">
                @csrf
                @if ($isEditing) @method('PUT') @endif

                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control mb-3 @error('name') is-invalid @enderror" name="name" value="{{ old('name', $sequence->name) }}" required autofocus>
                @error('name')<div class="invalid-feedback d-block mb-3">{{ $message }}</div>@enderror

                <label class="form-label d-flex justify-content-between align-items-center">
                    <span>Steps</span>
                    <button type="button" class="btn btn-light btn-sm" id="addStepBtn"><i class="ti ti-plus font-14 me-1"></i> Add Step</button>
                </label>
                <div id="stepsBuilder"></div>
                <input type="hidden" name="steps_json" id="stepsJsonInput">

                <div class="mt-3">
                    <button type="submit" class="btn btn-de-primary text-light px-4">{{ $isEditing ? 'Save changes' : 'Create sequence' }}</button>
                    <a href="{{ route('admin.crm.email-sequences.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    @if ($isEditing)
        <div class="card list-view mt-3" style="max-width: 820px;">
            <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Enroll a lead</div></div>
            <div class="list-filter-panel">
                <form method="POST" action="{{ route('admin.crm.email-sequences.enroll', $sequence) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-8">
                        <select name="lead_id" class="form-select form-select-sm" required>
                            <option value="">Select lead</option>
                            @foreach (\App\Models\Crm\Lead::where('company_id', $sequence->company_id)->orderBy('name')->get() as $lead)
                                <option value="{{ $lead->id }}">{{ $lead->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Enroll</button></div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover list-table mb-0">
                    <thead class="thead-light"><tr><th>Lead</th><th>Step</th><th>Next Due</th><th>Status</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                    <tbody>
                        @forelse ($sequence->enrollments as $enrollment)
                            <tr>
                                <td class="fw-semibold">{{ $enrollment->recipient_name ?? '—' }}</td>
                                <td>{{ $enrollment->current_step + 1 }} / {{ $sequence->steps->count() }}</td>
                                <td class="text-muted font-13">{{ $enrollment->next_step_due_at?->format('M j, Y') ?? '—' }}</td>
                                <td><span class="tag tone-{{ $enrollment->status === 'completed' ? 'success' : ($enrollment->status === 'stopped' ? 'slate' : 'sky') }}">{{ \App\Models\Crm\EmailSequenceEnrollment::STATUSES[$enrollment->status] ?? $enrollment->status }}</span></td>
                                <td class="text-end">
                                    @if ($enrollment->status === 'active')
                                        <form action="{{ route('admin.crm.email-sequences.enrollments.advance', $enrollment) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn-icon" title="Advance to next step"><i class="ti ti-player-play font-16"></i></button>
                                        </form>
                                        <form action="{{ route('admin.crm.email-sequences.enrollments.stop', $enrollment) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn-icon btn-icon-danger" title="Stop"><i class="ti ti-player-stop font-16"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No leads enrolled yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <script>
        (function () {
            const builder = document.getElementById('stepsBuilder');
            const addBtn = document.getElementById('addStepBtn');
            const hiddenInput = document.getElementById('stepsJsonInput');
            const form = document.getElementById('sequenceForm');
            const initial = @json($steps->map(fn($s) => ['subject' => $s->subject, 'delay_days' => $s->delay_days, 'body' => $s->body])->values());

            function rowHtml(step) {
                step = step || { subject: '', delay_days: 1, body: '' };
                return `<div class="row g-2 align-items-end mb-2 step-row">
                    <div class="col-md-2"><input type="number" min="0" class="form-control form-control-sm step-delay" placeholder="Days" value="${step.delay_days}"></div>
                    <div class="col-md-4"><input type="text" class="form-control form-control-sm step-subject" placeholder="Subject" value="${step.subject}"></div>
                    <div class="col-md-5"><input type="text" class="form-control form-control-sm step-body" placeholder="Body preview" value="${(step.body || '').replace(/"/g, '&quot;')}"></div>
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
                    delay_days: parseInt(row.querySelector('.step-delay').value || '1', 10),
                    subject: row.querySelector('.step-subject').value.trim(),
                    body: row.querySelector('.step-body').value.trim(),
                })).filter(s => s.subject);
                hiddenInput.value = JSON.stringify(rows);
            });
        })();
    </script>
@endsection
