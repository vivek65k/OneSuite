@extends('layouts.admin')

@section('title', 'Add Candidate')

@section('content')
    <x-page-title title="Add Candidate" :breadcrumbs="['Candidates' => route('admin.candidates.index')]" />

    <form method="POST" action="{{ route('admin.candidates.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.hrm.recruitment.candidates._form')
    </form>
@endsection
