@extends('layouts.admin')

@section('title', 'Add Course')

@section('content')
    <x-page-title title="Add Course" :breadcrumbs="['Courses' => route('admin.courses.index')]" />

    <form method="POST" action="{{ route('admin.courses.store') }}">
        @csrf
        @include('admin.hrm.learning.courses._form')
    </form>
@endsection
