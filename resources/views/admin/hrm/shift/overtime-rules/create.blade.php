@extends('layouts.admin')

@section('title', 'Add Overtime Rule')

@section('content')
    <x-page-title title="Add Overtime Rule" :breadcrumbs="['Overtime Rules' => route('admin.overtime-rules.index')]" />

    <form method="POST" action="{{ route('admin.overtime-rules.store') }}">
        @csrf
        @include('admin.hrm.shift.overtime-rules._form')
    </form>
@endsection
