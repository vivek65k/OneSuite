@extends('layouts.admin')

@section('title', 'Add Trainer')

@section('content')
    <x-page-title title="Add Trainer" :breadcrumbs="['Trainer Management' => route('admin.trainers.index')]" />

    <form method="POST" action="{{ route('admin.trainers.store') }}">
        @csrf
        @include('admin.hrm.learning.trainers._form')
    </form>
@endsection
