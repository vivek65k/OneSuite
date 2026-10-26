@extends('layouts.admin')

@section('title', 'Geo Attendance')

@section('content')
    <x-page-title title="Geo Attendance" :breadcrumbs="['Attendance' => route('admin.attendance.dashboard')]" />

    @include('admin.hrm.attendance._log-list', ['geo' => true, 'route' => 'admin.attendance.geo'])
@endsection
