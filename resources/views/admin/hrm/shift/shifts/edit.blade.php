@extends('layouts.admin')

@section('title', 'Edit Shift')

@section('content')
    <x-page-title title="Edit Shift" :breadcrumbs="['Shift Master' => route('admin.shifts.index')]" />

    <form method="POST" action="{{ route('admin.shifts.update', $shift) }}">
        @csrf
        @method('PUT')
        @include('admin.hrm.shift.shifts._form')
    </form>
@endsection
