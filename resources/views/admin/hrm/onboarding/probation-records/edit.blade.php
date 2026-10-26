@extends('layouts.admin')

@section('title', 'Edit Probation Record')

@section('content')
    <x-page-title title="Edit Probation Record" :breadcrumbs="['Onboarding' => route('admin.onboarding.overview'), 'Probation Tracking' => route('admin.probation-records.index')]" />

    <form method="POST" action="{{ route('admin.probation-records.update', $record) }}">
        @csrf
        @method('PUT')
        @include('admin.hrm.onboarding.probation-records._form')
    </form>
@endsection
