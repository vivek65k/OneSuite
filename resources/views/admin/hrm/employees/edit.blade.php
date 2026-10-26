@extends('layouts.admin')

@section('title', 'Edit Employee')

@section('content')
    <x-page-title title="Edit Employee" :breadcrumbs="['Employees' => route('admin.employees.index')]" />

    <form method="POST" action="{{ route('admin.employees.update', $employee) }}">
        @csrf
        @method('PUT')
        @include('admin.hrm.employees._form')
    </form>
@endsection
