@extends('layouts.admin')

@section('title', 'Add Probation Record')

@section('content')
    <x-page-title title="Add Probation Record" :breadcrumbs="['Onboarding' => route('admin.onboarding.overview'), 'Probation Tracking' => route('admin.probation-records.index')]" />

    <form method="POST" action="{{ route('admin.probation-records.store') }}">
        @csrf
        @include('admin.hrm.onboarding.probation-records._form')
    </form>
@endsection
