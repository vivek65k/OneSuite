@extends('layouts.admin')

@section('title', 'Create Automation Rule')

@section('content')
    <x-page-title title="Create Automation Rule" :breadcrumbs="['Automation Rules' => route('admin.automation-rules.index')]" />

    <form method="POST" action="{{ route('admin.automation-rules.store') }}">
        @csrf
        @include('admin.settings.workflow-approvals.automation-rules._form')
    </form>
@endsection
