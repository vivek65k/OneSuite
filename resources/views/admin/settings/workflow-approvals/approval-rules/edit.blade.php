@extends('layouts.admin')

@section('title', 'Edit Approval Rule')

@section('content')
    <x-page-title title="Edit Approval Rule" :breadcrumbs="['Approval Matrix' => route('admin.approval-rules.index')]" />

    <form method="POST" action="{{ route('admin.approval-rules.update', $rule) }}">
        @csrf
        @method('PUT')
        @include('admin.settings.workflow-approvals.approval-rules._form')
    </form>
@endsection
