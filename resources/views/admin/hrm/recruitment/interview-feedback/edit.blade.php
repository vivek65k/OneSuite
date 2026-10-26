@extends('layouts.admin')

@section('title', 'Edit Interview Feedback')

@section('content')
    <x-page-title title="Edit Interview Feedback" :breadcrumbs="['Interview Feedback' => route('admin.interview-feedback.index')]" />

    <form method="POST" action="{{ route('admin.interview-feedback.update', $feedbackEntry) }}">
        @csrf
        @method('PUT')
        @include('admin.hrm.recruitment.interview-feedback._form')
    </form>
@endsection
