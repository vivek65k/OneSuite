@extends('layouts.admin')

@section('title', 'Edit Candidate')

@section('content')
    <x-page-title title="Edit Candidate" :breadcrumbs="['Candidates' => route('admin.candidates.index')]" />

    <form method="POST" action="{{ route('admin.candidates.update', $candidate) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.hrm.recruitment.candidates._form')
    </form>
@endsection
