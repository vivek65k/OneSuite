@extends('layouts.admin')

@section('title', 'Create SMS Template')

@section('content')
    <x-page-title title="Create SMS Template" :breadcrumbs="['SMS Templates' => route('admin.sms-templates.index')]" />

    <form method="POST" action="{{ route('admin.sms-templates.store') }}">
        @csrf
        @include('admin.settings.workflow-approvals.sms-templates._form')
    </form>
@endsection
