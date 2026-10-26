@extends('layouts.admin')

@section('title', 'Create Email Template')

@section('content')
    <x-page-title title="Create Email Template" :breadcrumbs="['Email Templates' => route('admin.email-templates.index')]" />

    <form method="POST" action="{{ route('admin.email-templates.store') }}">
        @csrf
        @include('admin.settings.workflow-approvals.email-templates._form')
    </form>
@endsection
