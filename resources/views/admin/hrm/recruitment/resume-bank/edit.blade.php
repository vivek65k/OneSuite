@extends('layouts.admin')

@section('title', 'Edit Resume')

@section('content')
    <x-page-title title="Edit Resume" :breadcrumbs="['Resume Bank' => route('admin.resume-bank.index')]" />

    <form method="POST" action="{{ route('admin.resume-bank.update', $entry) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.hrm.recruitment.resume-bank._form')
    </form>
@endsection
