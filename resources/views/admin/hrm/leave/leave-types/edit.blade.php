@extends('layouts.admin')

@section('title', 'Edit Leave Type')

@section('content')
    <x-page-title title="Edit Leave Type" :breadcrumbs="['Leave Types' => route('admin.leave-types.index')]" />

    <form method="POST" action="{{ route('admin.leave-types.update', $leaveType) }}">
        @csrf
        @method('PUT')
        @include('admin.hrm.leave.leave-types._form')
    </form>
@endsection
