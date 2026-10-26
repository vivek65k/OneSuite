@extends('layouts.admin')

@section('title', 'Edit Document Template')

@section('content')
    <x-page-title title="Edit Document Template" :breadcrumbs="['Document Templates' => route('admin.document-templates.index')]" />

    <form method="POST" action="{{ route('admin.document-templates.update', $template) }}">
        @csrf
        @method('PUT')
        @include('admin.hrm.settings.document-templates._form')
    </form>
@endsection
