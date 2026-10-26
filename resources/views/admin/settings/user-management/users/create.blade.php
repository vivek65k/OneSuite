@extends('layouts.admin')

@section('title', 'Create User')

@section('content')
    <x-page-title title="Create User" :breadcrumbs="['Users' => route('admin.users.index')]" />

    <form method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.settings.user-management.users._form')
    </form>
@endsection
