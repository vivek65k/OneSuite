@extends('layouts.admin')

@section('title', 'Punch Logs')

@section('content')
    <x-page-title title="Punch Logs" :breadcrumbs="['Attendance' => route('admin.attendance.dashboard')]" />

    @include('admin.hrm.attendance._log-list', ['geo' => false, 'route' => 'admin.attendance.punch-logs'])
@endsection
