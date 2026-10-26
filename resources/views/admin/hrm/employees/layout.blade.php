@extends('layouts.admin')

@php
    $heroStatusTones = ['active' => 'success', 'on_leave' => 'warning', 'inactive' => 'muted', 'terminated' => 'danger'];
    $heroInitials = collect(preg_split('/\s+/', trim($employee->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

@section('content')
    <x-page-title :title="$employee->name" :breadcrumbs="['Employees' => route('admin.employees.index')]" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    {{-- Profile hero: gradient cover, overlapping avatar, colour-coded facts --}}
    <div class="card profile-hero">
        <div class="profile-hero-cover"></div>
        <div class="profile-hero-body">
            @if ($employee->avatar)
                <img src="{{ asset('storage/'.$employee->avatar) }}" alt="" class="profile-hero-avatar">
            @else
                <span class="profile-hero-avatar" aria-hidden="true">{{ $heroInitials }}</span>
            @endif

            <div class="profile-hero-main">
                <div class="profile-hero-name">
                    <h4>{{ $employee->name }}</h4>
                    <span class="status-dot status-dot-{{ $heroStatusTones[$employee->status] ?? 'muted' }}">{{ Str::headline($employee->status) }}</span>
                </div>
                <p class="profile-hero-role">{{ $employee->designation ?: 'No designation set' }}</p>
                <div class="profile-hero-facts">
                    <span class="tag tone-slate"><i class="ti ti-hash"></i>{{ $employee->employee_code }}</span>
                    @if ($employee->department)
                        <span class="tag tone-indigo"><i class="ti ti-sitemap"></i>{{ $employee->department->name }}</span>
                    @endif
                    @if ($employee->branch)
                        <span class="tag tone-sky"><i class="ti ti-building-store"></i>{{ $employee->branch->name }}</span>
                    @endif
                    @if ($employee->employment_type)
                        <span class="tag tone-violet"><i class="ti ti-briefcase"></i>{{ Str::headline($employee->employment_type) }}</span>
                    @endif
                    @if ($employee->joining_date)
                        <span class="tag tone-pink"><i class="ti ti-calendar-event"></i>Joined {{ $employee->joining_date->format('M j, Y') }}</span>
                    @endif
                </div>
            </div>

            <div class="profile-hero-actions">
                @if ($employee->email)
                    <a href="mailto:{{ $employee->email }}" class="btn-icon btn-icon-info" title="Email {{ $employee->email }}"><i class="ti ti-mail font-18"></i></a>
                @endif
                @if ($employee->phone)
                    <a href="tel:{{ $employee->phone }}" class="btn-icon btn-icon-success" title="Call {{ $employee->phone }}"><i class="ti ti-phone font-18"></i></a>
                @endif
                @can('update', $employee)
                    <a href="{{ route('admin.employees.edit', $employee) }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-pencil font-14 me-1"></i> Edit</a>
                @endcan
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body p-2">
                    @include('admin.hrm.employees._nav')
                </div>
            </div>
        </div>

        <div class="col-md-9 mb-3">
            @yield('employee-content')
        </div>
    </div>
@endsection
