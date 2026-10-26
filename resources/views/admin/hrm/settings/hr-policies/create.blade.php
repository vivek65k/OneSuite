@extends('layouts.admin')

@section('title', 'Add HR Policy')

@section('content')
    <x-page-title title="Add HR Policy" :breadcrumbs="['HR Policies' => route('admin.hr-policies.index')]" />

    <form method="POST" action="{{ route('admin.hr-policies.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.hrm.settings.hr-policies._form')
    </form>
@endsection
