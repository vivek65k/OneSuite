@extends('layouts.admin')

@section('title', 'Edit Department')

@section('content')
    <x-page-title title="Edit Department" :breadcrumbs="['Departments' => route('admin.departments.index')]" />

    <form method="POST" action="{{ route('admin.departments.update', $department) }}">
        @csrf
        @method('PUT')
        @include('admin.settings.organization.departments._form')
    </form>
@endsection
