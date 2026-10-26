@extends('layouts.admin')

@section('title', 'Add Tax Slab')

@section('content')
    <x-page-title title="Add Tax Slab" :breadcrumbs="['Tax Rules' => route('admin.tax-slabs.index')]" />

    <form method="POST" action="{{ route('admin.tax-slabs.store') }}">
        @csrf
        @include('admin.hrm.settings.tax-slabs._form')
    </form>
@endsection
