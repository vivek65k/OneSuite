@extends('layouts.admin')

@section('title', 'Edit Offer')

@section('content')
    <x-page-title title="Edit Offer" :breadcrumbs="['Offer Letters' => route('admin.offer-letters.index')]" />

    <form method="POST" action="{{ route('admin.offer-letters.update', $offer) }}">
        @csrf
        @method('PUT')
        @include('admin.hrm.recruitment.offer-letters._form')
    </form>
@endsection
