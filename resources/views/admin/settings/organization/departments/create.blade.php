@extends('layouts.admin')

@section('title', 'Create Department')

@section('content')
    <x-page-title title="Create Department" :breadcrumbs="['Departments' => route('admin.departments.index')]" />

    <form method="POST" action="{{ route('admin.departments.store') }}">
        @csrf
        @include('admin.settings.organization.departments._form')
    </form>
@endsection
