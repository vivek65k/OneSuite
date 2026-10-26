@extends('layouts.admin')

@section('title', 'Add Holiday')

@section('content')
    <x-page-title title="Add Holiday" :breadcrumbs="['Holiday Calendar' => route('admin.holidays.index')]" />

    <form method="POST" action="{{ route('admin.holidays.store') }}">
        @csrf
        @include('admin.settings.organization.holidays._form')
    </form>
@endsection
