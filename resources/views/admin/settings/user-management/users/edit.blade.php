@extends('layouts.admin')

@section('title', 'Edit User')

@section('content')
    <x-page-title title="Edit User" :breadcrumbs="['Users' => route('admin.users.index')]" />

    <form method="POST" action="{{ route('admin.users.update', $user) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.settings.user-management.users._form')
    </form>
@endsection
