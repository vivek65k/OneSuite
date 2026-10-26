@extends('layouts.admin')

@section('title', 'Create Offer')

@section('content')
    <x-page-title title="Create Offer" :breadcrumbs="['Offer Letters' => route('admin.offer-letters.index')]" />

    <form method="POST" action="{{ route('admin.offer-letters.store') }}">
        @csrf
        @include('admin.hrm.recruitment.offer-letters._form')
    </form>
@endsection
