@extends('layouts.admin')

@section('title', 'Edit Lead')

@section('content')
    <x-page-title title="Edit Lead" :breadcrumbs="['Leads' => route('admin.crm.leads.index')]" />

    <form method="POST" action="{{ route('admin.crm.leads.update', $lead) }}">
        @csrf
        @method('PUT')
        @include('admin.crm.leads._form')
    </form>
@endsection
