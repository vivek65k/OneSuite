@extends('layouts.admin')

@section('title', 'Edit SMS Template')

@section('content')
    <x-page-title title="Edit SMS Template" :breadcrumbs="['SMS Templates' => route('admin.sms-templates.index')]" />

    <form method="POST" action="{{ route('admin.sms-templates.update', $template) }}">
        @csrf
        @method('PUT')
        @include('admin.settings.workflow-approvals.sms-templates._form')
    </form>
@endsection
