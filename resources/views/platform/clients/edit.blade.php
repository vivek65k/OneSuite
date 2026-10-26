@extends('layouts.platform')

@section('title', 'Edit Client')

@section('content')
    <x-page-title title="Edit Client" :breadcrumbs="['Clients' => route('platform.clients.index')]" />

    <form method="POST" action="{{ route('platform.clients.update', $client) }}">
        @csrf
        @method('PUT')
        @include('platform.clients._form')
    </form>
@endsection
