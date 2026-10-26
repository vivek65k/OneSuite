@extends('layouts.admin')

@section('title', 'Edit Escalation Rule')

@section('content')
    <x-page-title title="Edit Escalation Rule" :breadcrumbs="['Escalation Rules' => route('admin.escalation-rules.index')]" />

    <form method="POST" action="{{ route('admin.escalation-rules.update', $rule) }}">
        @csrf
        @method('PUT')
        @include('admin.settings.workflow-approvals.escalation-rules._form')
    </form>
@endsection
