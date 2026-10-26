@extends('layouts.admin')

@section('title', 'Add Employee')

@section('content')
    <x-page-title title="Add Employee" :breadcrumbs="['Employees' => route('admin.employees.index')]" />

    <form method="POST" action="{{ route('admin.employees.store') }}">
        @csrf
        @include('admin.hrm.employees._form')
    </form>
@endsection
