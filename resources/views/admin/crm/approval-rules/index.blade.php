@extends('layouts.admin')

@section('title', 'Approval Rules')

@section('content')
    <x-page-title title="Approval Rules" />
    @include('admin.crm.automation._rule-list', [
        'routePrefix' => 'admin.crm.approval-rules',
        'icon' => 'ti ti-shield-check',
        'showEntity' => true,
        'showCondition' => true,
        'showRun' => false,
        'employeeLabel' => 'Approver',
    ])
@endsection
