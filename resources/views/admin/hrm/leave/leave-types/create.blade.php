@extends('layouts.admin')

@section('title', 'Add Leave Type')

@section('content')
    <x-page-title title="Add Leave Type" :breadcrumbs="['Leave Types' => route('admin.leave-types.index')]" />

    <form method="POST" action="{{ route('admin.leave-types.store') }}">
        @csrf
        @include('admin.hrm.leave.leave-types._form')
    </form>
@endsection
