@extends('layouts.admin')

@section('title', 'Edit Holiday')

@section('content')
    <x-page-title title="Edit Holiday" :breadcrumbs="['Holiday Calendar' => route('admin.holidays.index')]" />

    <form method="POST" action="{{ route('admin.holidays.update', $holiday) }}">
        @csrf
        @method('PUT')
        @include('admin.settings.organization.holidays._form')
    </form>
@endsection
