@extends('layouts.admin')

@section('title', 'Add Lead')

@section('content')
    <x-page-title title="Add Lead" :breadcrumbs="['Leads' => route('admin.crm.leads.index')]" />

    <form method="POST" action="{{ route('admin.crm.leads.store') }}">
        @csrf
        @include('admin.crm.leads._form')
    </form>
@endsection
