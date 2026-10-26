@extends('layouts.admin')

@section('title', 'Triggers & Actions')

@section('content')
    <x-page-title title="Triggers & Actions" />
    @include('admin.crm.automation._rule-list', [
        'routePrefix' => 'admin.crm.trigger-actions',
        'icon' => 'ti ti-bolt',
        'showEntity' => false,
        'showTrigger' => true,
        'showCondition' => false,
        'showActionType' => true,
        'showRun' => false,
        'employeeLabel' => 'Notify / Assign',
    ])
@endsection
