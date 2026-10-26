@extends('layouts.admin')

@section('title', 'Edit Course')

@section('content')
    <x-page-title title="Edit Course" :breadcrumbs="['Courses' => route('admin.courses.index')]" />

    <form method="POST" action="{{ route('admin.courses.update', $course) }}">
        @csrf
        @method('PUT')
        @include('admin.hrm.learning.courses._form')
    </form>
@endsection
