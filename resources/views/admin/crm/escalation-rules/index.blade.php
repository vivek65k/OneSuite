@extends('layouts.admin')

@section('title', 'Escalation Rules')

@section('content')
    <x-page-title title="Escalation Rules" />
    @include('admin.crm.automation._rule-list', [
        'routePrefix' => 'admin.crm.escalation-rules',
        'icon' => 'ti ti-arrow-up-circle',
        'showEntity' => true,
        'showCondition' => false,
        'employeeLabel' => 'Escalate To',
    ])
@endsection
