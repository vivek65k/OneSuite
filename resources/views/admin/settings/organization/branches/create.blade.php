@extends('layouts.admin')

@section('title', 'Create Branch')

@section('content')
    <x-page-title title="Create Branch" :breadcrumbs="['Branches' => route('admin.branches.index')]" />

    <form method="POST" action="{{ route('admin.branches.store') }}">
        @csrf
        @include('admin.settings.organization.branches._form')
    </form>
@endsection
