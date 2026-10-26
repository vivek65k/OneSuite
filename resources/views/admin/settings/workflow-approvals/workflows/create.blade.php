@extends('layouts.admin')

@section('title', 'Create Workflow')

@section('content')
    <x-page-title title="Create Workflow" :breadcrumbs="['Workflow Designer' => route('admin.workflows.index')]" />

    <form method="POST" action="{{ route('admin.workflows.store') }}">
        @csrf
        @include('admin.settings.workflow-approvals.workflows._form')
    </form>
@endsection
