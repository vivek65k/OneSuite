@extends('layouts.admin')

@section('title', 'Create Escalation Rule')

@section('content')
    <x-page-title title="Create Escalation Rule" :breadcrumbs="['Escalation Rules' => route('admin.escalation-rules.index')]" />

    <form method="POST" action="{{ route('admin.escalation-rules.store') }}">
        @csrf
        @include('admin.settings.workflow-approvals.escalation-rules._form')
    </form>
@endsection
