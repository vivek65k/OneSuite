@extends('layouts.admin')

@section('title', 'Edit Branch')

@section('content')
    <x-page-title title="Edit Branch" :breadcrumbs="['Branches' => route('admin.branches.index')]" />

    <form method="POST" action="{{ route('admin.branches.update', $branch) }}">
        @csrf
        @method('PUT')
        @include('admin.settings.organization.branches._form')
    </form>
@endsection
