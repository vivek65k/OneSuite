@extends('layouts.admin')

@section('title', 'Edit Automation Rule')

@section('content')
    <x-page-title title="Edit Automation Rule" :breadcrumbs="['Automation Rules' => route('admin.automation-rules.index')]" />

    <form method="POST" action="{{ route('admin.automation-rules.update', $rule) }}">
        @csrf
        @method('PUT')
        @include('admin.settings.workflow-approvals.automation-rules._form')
    </form>
@endsection
