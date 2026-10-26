@extends('layouts.admin')

@section('title', 'Edit Email Template')

@section('content')
    <x-page-title title="Edit Email Template" :breadcrumbs="['Email Templates' => route('admin.email-templates.index')]" />

    <form method="POST" action="{{ route('admin.email-templates.update', $template) }}">
        @csrf
        @method('PUT')
        @include('admin.settings.workflow-approvals.email-templates._form')
    </form>
@endsection
