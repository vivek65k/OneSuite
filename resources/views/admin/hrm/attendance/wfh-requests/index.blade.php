@extends('layouts.admin')

@section('title', 'Work From Home')

@section('content')
    @include('admin.hrm.attendance._requests', ['type' => 'wfh'])
@endsection
