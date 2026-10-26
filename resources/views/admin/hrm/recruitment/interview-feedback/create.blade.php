@extends('layouts.admin')

@section('title', 'Add Interview Feedback')

@section('content')
    <x-page-title title="Add Interview Feedback" :breadcrumbs="['Interview Feedback' => route('admin.interview-feedback.index')]" />

    <form method="POST" action="{{ route('admin.interview-feedback.store') }}">
        @csrf
        @include('admin.hrm.recruitment.interview-feedback._form')
    </form>
@endsection
