@extends('layouts.admin')

@section('title', 'Add Resume')

@section('content')
    <x-page-title title="Add Resume" :breadcrumbs="['Resume Bank' => route('admin.resume-bank.index')]" />

    <form method="POST" action="{{ route('admin.resume-bank.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.hrm.recruitment.resume-bank._form')
    </form>
@endsection
