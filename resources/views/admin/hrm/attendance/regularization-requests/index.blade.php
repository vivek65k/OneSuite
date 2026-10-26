@extends('layouts.admin')

@section('title', 'Regularization Requests')

@section('content')
    @include('admin.hrm.attendance._requests', ['type' => 'regularization'])
@endsection
