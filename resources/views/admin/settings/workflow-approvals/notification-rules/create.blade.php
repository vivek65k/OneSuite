@extends('layouts.admin')

@section('title', 'Create Notification Rule')

@section('content')
    <x-page-title title="Create Notification Rule" :breadcrumbs="['Notifications' => route('admin.notification-rules.index')]" />

    <form method="POST" action="{{ route('admin.notification-rules.store') }}">
        @csrf
        @include('admin.settings.workflow-approvals.notification-rules._form')
    </form>
@endsection
