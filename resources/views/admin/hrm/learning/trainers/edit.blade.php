@extends('layouts.admin')

@section('title', 'Edit Trainer')

@section('content')
    <x-page-title title="Edit Trainer" :breadcrumbs="['Trainer Management' => route('admin.trainers.index')]" />

    <form method="POST" action="{{ route('admin.trainers.update', $trainer) }}">
        @csrf
        @method('PUT')
        @include('admin.hrm.learning.trainers._form')
    </form>
@endsection
