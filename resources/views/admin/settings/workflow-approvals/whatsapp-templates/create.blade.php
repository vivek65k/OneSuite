@extends('layouts.admin')

@section('title', 'Create WhatsApp Template')

@section('content')
    <x-page-title title="Create WhatsApp Template" :breadcrumbs="['WhatsApp Templates' => route('admin.whatsapp-templates.index')]" />

    <form method="POST" action="{{ route('admin.whatsapp-templates.store') }}">
        @csrf
        @include('admin.settings.workflow-approvals.whatsapp-templates._form')
    </form>
@endsection
