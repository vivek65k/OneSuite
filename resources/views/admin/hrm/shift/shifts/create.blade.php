@extends('layouts.admin')

@section('title', 'Add Shift')

@section('content')
    <x-page-title title="Add Shift" :breadcrumbs="['Shift Master' => route('admin.shifts.index')]" />

    <form method="POST" action="{{ route('admin.shifts.store') }}">
        @csrf
        @include('admin.hrm.shift.shifts._form')
    </form>
@endsection
