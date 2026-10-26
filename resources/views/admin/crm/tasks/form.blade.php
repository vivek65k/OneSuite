@extends('layouts.admin')

@php
    $isEditing = $task->exists;
    $cancelUrl = route('admin.crm.tasks.index');
@endphp

@section('title', $isEditing ? 'Edit Task' : 'Add Task')

@section('content')
    <x-page-title :title="$isEditing ? 'Edit Task' : 'Add Task'" :breadcrumbs="['Tasks' => $cancelUrl]" />

    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="card form-section tone-indigo" style="max-width: 720px;">
        <div class="form-section-head">
            <span class="form-section-icon"><i class="ti ti-list-check"></i></span>
            <div><h5 class="form-section-title">{{ $isEditing ? 'Edit task' : 'New task' }}</h5></div>
        </div>
        <div class="form-section-body">
            <form method="POST" action="{{ $isEditing ? route('admin.crm.tasks.update', $task) : route('admin.crm.tasks.store') }}">
                @csrf
                @if ($isEditing) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="subject">Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="subject" name="subject" value="{{ old('subject', $task->subject) }}" required autofocus>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="due_at">Due date</label>
                        <input type="datetime-local" class="form-control" id="due_at" name="due_at" value="{{ old('due_at', optional($task->due_at)->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="priority">Priority <span class="text-danger">*</span></label>
                        <select class="form-select" id="priority" name="priority" required>
                            @foreach (\App\Models\Crm\Activity::PRIORITIES as $key => $label)
                                <option value="{{ $key }}" @selected(old('priority', $task->priority ?? 'medium') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="relatable">Related to</label>
                        <select class="form-select" id="relatable" name="relatable">
                            <option value="">— None —</option>
                            @foreach ($relatable as $key => $label)
                                <option value="{{ $key }}" @selected(old('relatable', $currentRelatable ?? null) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="owner_employee_id">Owner</label>
                        <select class="form-select" id="owner_employee_id" name="owner_employee_id">
                            <option value="">— Unassigned —</option>
                            @foreach ($employees as $id => $name)
                                <option value="{{ $id }}" @selected((string) old('owner_employee_id', $task->owner_employee_id) === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="reminder_at">Reminder at</label>
                        <input type="datetime-local" class="form-control" id="reminder_at" name="reminder_at" value="{{ old('reminder_at', optional($task->reminder_at)->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="description">Description</label>
                        <textarea name="description" id="description" rows="3" class="form-control">{{ old('description', $task->description) }}</textarea>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_recurring" id="is_recurring" value="1" @checked(old('is_recurring', $task->is_recurring))>
                            <label class="form-check-label" for="is_recurring">Recurring task</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="recurrence_frequency">Repeats</label>
                        <select class="form-select" id="recurrence_frequency" name="recurrence_frequency">
                            <option value="">—</option>
                            @foreach (\App\Models\Crm\Activity::RECURRENCE_FREQUENCIES as $key => $label)
                                <option value="{{ $key }}" @selected(old('recurrence_frequency', $task->recurrence_frequency) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="font-12 text-muted mt-1 mb-0">A new task is created automatically when this one is marked complete.</p>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-de-primary text-light px-4">{{ $isEditing ? 'Save changes' : 'Create task' }}</button>
                    <a href="{{ $cancelUrl }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
