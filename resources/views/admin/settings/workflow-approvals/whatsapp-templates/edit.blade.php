@extends('layouts.admin')

@section('title', 'Edit WhatsApp Template')

@section('content')
    <x-page-title title="Edit WhatsApp Template" :breadcrumbs="['WhatsApp Templates' => route('admin.whatsapp-templates.index')]" />

    <form method="POST" action="{{ route('admin.whatsapp-templates.update', $template) }}">
        @csrf
        @method('PUT')
        @include('admin.settings.workflow-approvals.whatsapp-templates._form')
    </form>
@endsection
