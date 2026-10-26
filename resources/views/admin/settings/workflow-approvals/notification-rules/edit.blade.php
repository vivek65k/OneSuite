@extends('layouts.admin')

@section('title', 'Edit Notification Rule')

@section('content')
    <x-page-title title="Edit Notification Rule" :breadcrumbs="['Notifications' => route('admin.notification-rules.index')]" />

    <form method="POST" action="{{ route('admin.notification-rules.update', $rule) }}">
        @csrf
        @method('PUT')
        @include('admin.settings.workflow-approvals.notification-rules._form')
    </form>
@endsection
