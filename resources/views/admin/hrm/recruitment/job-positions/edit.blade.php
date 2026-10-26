@extends('layouts.admin')

@section('title', 'Edit Job Position')

@section('content')
    <x-page-title title="Edit Job Position" :breadcrumbs="['Job Positions' => route('admin.job-positions.index')]" />

    <form method="POST" action="{{ route('admin.job-positions.update', $position) }}">
        @csrf
        @method('PUT')
        @include('admin.hrm.recruitment.job-positions._form')
    </form>
@endsection
