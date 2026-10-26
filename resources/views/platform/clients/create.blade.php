@extends('layouts.platform')

@section('title', 'New Client')

@section('content')
    <x-page-title title="New Client" :breadcrumbs="['Clients' => route('platform.clients.index')]" />

    <form method="POST" action="{{ route('platform.clients.store') }}">
        @csrf
        @include('platform.clients._form')
    </form>
@endsection
