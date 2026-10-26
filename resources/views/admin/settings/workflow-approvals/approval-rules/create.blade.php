@extends('layouts.admin')

@section('title', 'Create Approval Rule')

@section('content')
    <x-page-title title="Create Approval Rule" :breadcrumbs="['Approval Matrix' => route('admin.approval-rules.index')]" />

    <form method="POST" action="{{ route('admin.approval-rules.store') }}">
        @csrf
        @include('admin.settings.workflow-approvals.approval-rules._form')
    </form>
@endsection
