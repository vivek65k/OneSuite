@extends('layouts.admin')

@section('title', 'Edit Overtime Rule')

@section('content')
    <x-page-title title="Edit Overtime Rule" :breadcrumbs="['Overtime Rules' => route('admin.overtime-rules.index')]" />

    <form method="POST" action="{{ route('admin.overtime-rules.update', $overtimeRule) }}">
        @csrf
        @method('PUT')
        @include('admin.hrm.shift.overtime-rules._form')
    </form>
@endsection
