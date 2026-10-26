@extends('layouts.admin')

@section('title', 'Edit Interview')

@section('content')
    <x-page-title title="Edit Interview" :breadcrumbs="['Interview Schedule' => route('admin.interviews.index')]" />

    <form method="POST" action="{{ route('admin.interviews.update', $interview) }}">
        @csrf
        @method('PUT')
        @include('admin.hrm.recruitment.interviews._form')
    </form>
@endsection
