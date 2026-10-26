@extends('layouts.admin')

@section('title', 'Create Job Position')

@section('content')
    <x-page-title title="Create Job Position" :breadcrumbs="['Job Positions' => route('admin.job-positions.index')]" />

    <form method="POST" action="{{ route('admin.job-positions.store') }}">
        @csrf
        @include('admin.hrm.recruitment.job-positions._form')
    </form>
@endsection
