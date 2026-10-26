@extends('layouts.admin')

@section('title', 'Edit Workflow')

@section('content')
    <x-page-title title="Edit Workflow" :breadcrumbs="['Workflow Designer' => route('admin.workflows.index')]" />

    <form method="POST" action="{{ route('admin.workflows.update', $workflow) }}">
        @csrf
        @method('PUT')
        @include('admin.settings.workflow-approvals.workflows._form')
    </form>
@endsection
