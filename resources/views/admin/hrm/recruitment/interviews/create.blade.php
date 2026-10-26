@extends('layouts.admin')

@section('title', 'Schedule Interview')

@section('content')
    <x-page-title title="Schedule Interview" :breadcrumbs="['Interview Schedule' => route('admin.interviews.index')]" />

    <form method="POST" action="{{ route('admin.interviews.store') }}">
        @csrf
        @include('admin.hrm.recruitment.interviews._form')
    </form>
@endsection
