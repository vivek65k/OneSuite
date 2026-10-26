@extends('layouts.admin')

@section('title', 'Assignment Rules')

@section('content')
    <x-page-title title="Assignment Rules" />
    @include('admin.crm.automation._rule-list', [
        'routePrefix' => 'admin.crm.assignment-rules',
        'icon' => 'ti ti-users',
        'showEntity' => true,
        'showCondition' => false,
        'employeeLabel' => 'Assign To',
    ])
@endsection
