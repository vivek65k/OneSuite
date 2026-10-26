@extends('layouts.admin')

@section('title', 'Edit HR Policy')

@section('content')
    <x-page-title title="Edit HR Policy" :breadcrumbs="['HR Policies' => route('admin.hr-policies.index')]" />

    <form method="POST" action="{{ route('admin.hr-policies.update', $policy) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.hrm.settings.hr-policies._form')
    </form>
@endsection
