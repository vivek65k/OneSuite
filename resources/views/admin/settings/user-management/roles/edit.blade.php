@extends('layouts.admin')

@section('title', 'Edit Role')

@section('content')
    <x-page-title title="Edit Role" :breadcrumbs="['Roles & Permissions' => route('admin.roles.index')]" />

    <form method="POST" action="{{ route('admin.roles.update', $role) }}">
        @csrf
        @method('PUT')
        @include('admin.settings.user-management.roles._form')
    </form>
@endsection
