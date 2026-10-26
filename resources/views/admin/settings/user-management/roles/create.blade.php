@extends('layouts.admin')

@section('title', 'Create Role')

@section('content')
    <x-page-title title="Create Role" :breadcrumbs="['Roles & Permissions' => route('admin.roles.index')]" />

    <form method="POST" action="{{ route('admin.roles.store') }}">
        @csrf
        @include('admin.settings.user-management.roles._form')
    </form>
@endsection
