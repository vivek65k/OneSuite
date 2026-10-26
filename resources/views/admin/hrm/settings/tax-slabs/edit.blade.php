@extends('layouts.admin')

@section('title', 'Edit Tax Slab')

@section('content')
    <x-page-title title="Edit Tax Slab" :breadcrumbs="['Tax Rules' => route('admin.tax-slabs.index')]" />

    <form method="POST" action="{{ route('admin.tax-slabs.update', $slab) }}">
        @csrf
        @method('PUT')
        @include('admin.hrm.settings.tax-slabs._form')
    </form>
@endsection
