@extends('layouts.admin')

@section('title', 'Add Document Template')

@section('content')
    <x-page-title title="Add Document Template" :breadcrumbs="['Document Templates' => route('admin.document-templates.index')]" />

    <form method="POST" action="{{ route('admin.document-templates.store') }}">
        @csrf
        @include('admin.hrm.settings.document-templates._form')
    </form>
@endsection
