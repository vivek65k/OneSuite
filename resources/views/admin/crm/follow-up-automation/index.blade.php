@extends('layouts.admin')

@section('title', 'Follow-up Automation')

@section('content')
    <x-page-title title="Follow-up Automation" />
    <p class="text-muted font-13 mb-3">Creates a follow-up task for leads with no activity for the given number of days. Condition value = days of inactivity.</p>
    @include('admin.crm.automation._rule-list', [
        'routePrefix' => 'admin.crm.follow-up-automation',
        'icon' => 'ti ti-bell',
        'showEntity' => false,
        'showCondition' => true,
        'employeeLabel' => 'Task Owner (optional)',
    ])
@endsection
