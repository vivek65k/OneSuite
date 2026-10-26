@extends('admin.hrm.employees.layout')

@section('title', $employee->name.' - Profile')

@php
    $profileGroups = [
        ['title' => 'Contact & personal', 'icon' => 'ti-id', 'tone' => 'indigo', 'items' => [
            ['icon' => 'ti-mail', 'tone' => 'sky', 'label' => 'Email', 'value' => $employee->email],
            ['icon' => 'ti-phone', 'tone' => 'teal', 'label' => 'Phone', 'value' => $employee->phone],
            ['icon' => 'ti-man', 'tone' => 'violet', 'label' => 'Gender', 'value' => $employee->gender ? Str::headline($employee->gender) : null],
            ['icon' => 'ti-gift', 'tone' => 'pink', 'label' => 'Date of birth', 'value' => optional($employee->date_of_birth)->format('M j, Y')],
        ]],
        ['title' => 'Employment', 'icon' => 'ti-briefcase', 'tone' => 'teal', 'items' => [
            ['icon' => 'ti-sitemap', 'tone' => 'indigo', 'label' => 'Department', 'value' => $employee->department?->name],
            ['icon' => 'ti-building-store', 'tone' => 'sky', 'label' => 'Branch', 'value' => $employee->branch?->name],
            ['icon' => 'ti-clock', 'tone' => 'violet', 'label' => 'Employment type', 'value' => $employee->employment_type ? Str::headline($employee->employment_type) : null],
            ['icon' => 'ti-calendar-event', 'tone' => 'pink', 'label' => 'Joining date', 'value' => optional($employee->joining_date)->format('M j, Y')],
            ['icon' => 'ti-hierarchy', 'tone' => 'amber', 'label' => 'Reports to', 'value' => $employee->manager?->name],
            ['icon' => 'ti-clock', 'tone' => 'lime', 'label' => 'Tenure', 'value' => $employee->joining_date && $employee->joining_date->isPast() ? $employee->joining_date->diffForHumans(null, true) : null],
        ]],
    ];
    $address = collect([$employee->address, $employee->city, $employee->state, $employee->country, $employee->postal_code])->filter()->implode(', ');
@endphp

@section('employee-content')
    @foreach ($profileGroups as $group)
        <div class="card form-section tone-{{ $group['tone'] }}">
            <div class="form-section-head form-section-head-compact">
                <span class="form-section-icon form-section-icon-sm"><i class="ti {{ $group['icon'] }}"></i></span>
                <h5 class="form-section-title">{{ $group['title'] }}</h5>
            </div>
            <div class="form-section-body">
                <div class="info-grid">
                    @foreach ($group['items'] as $item)
                        <div class="info-item tone-{{ $item['tone'] }}">
                            <span class="info-item-icon"><i class="ti {{ $item['icon'] }}"></i></span>
                            <div class="info-item-text">
                                <span class="info-item-label">{{ $item['label'] }}</span>
                                <span class="info-item-value {{ filled($item['value']) ? '' : 'is-empty' }}">{{ filled($item['value']) ? $item['value'] : 'Not set' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach

    <div class="card form-section tone-amber">
        <div class="form-section-head form-section-head-compact">
            <span class="form-section-icon form-section-icon-sm"><i class="ti ti-hierarchy"></i></span>
            <h5 class="form-section-title">Reporting line</h5>
            @can('viewAny', \App\Models\Employee::class)
                <a href="{{ route('admin.employees.org-chart') }}" class="btn btn-sm btn-light ms-auto"><i class="ti ti-sitemap font-14 me-1"></i>Org chart</a>
            @endcan
        </div>
        <div class="form-section-body">
            <span class="info-item-label d-block mb-2">Reports up to</span>
            <div class="report-line mb-3">
                @forelse ($reportingLine as $manager)
                    <a href="{{ route('admin.employees.show', $manager) }}" class="report-line-person">
                        <x-avatar :person="$manager" />
                        <span>{{ $manager->name }}<small>Level {{ $reportingLine->count() - $loop->index }} - {{ $manager->designation ?: 'No designation' }}</small></span>
                    </a>
                    <i class="ti ti-chevron-right report-line-arrow"></i>
                @empty
                    <span class="text-muted font-13">Nobody - {{ $employee->name }} is at the top of the reporting line.</span>
                    <i class="ti ti-chevron-right report-line-arrow"></i>
                @endforelse
                <span class="report-line-person is-current">
                    <x-avatar :person="$employee" />
                    <span>{{ $employee->name }}<small>{{ $employee->designation ?: 'No designation' }}</small></span>
                </span>
            </div>

            <span class="info-item-label d-block mb-2">
                Direct reports ({{ $directReports->count() }}){{ $teamSize > $directReports->count() ? ' - '.$teamSize.' people in the whole team' : '' }}
            </span>
            <div class="report-line">
                @forelse ($directReports as $report)
                    <a href="{{ route('admin.employees.show', $report) }}" class="report-line-person">
                        <x-avatar :person="$report" />
                        <span>{{ $report->name }}<small>{{ $report->designation ?: 'No designation' }}</small></span>
                    </a>
                @empty
                    <span class="text-muted font-13">Nobody reports to {{ $employee->name }}.</span>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card form-section tone-pink">
        <div class="form-section-head form-section-head-compact">
            <span class="form-section-icon form-section-icon-sm"><i class="ti ti-map-pin"></i></span>
            <h5 class="form-section-title">Address</h5>
        </div>
        <div class="form-section-body">
            <div class="info-item tone-rose">
                <span class="info-item-icon"><i class="ti ti-home"></i></span>
                <div class="info-item-text">
                    <span class="info-item-label">Residential address</span>
                    <span class="info-item-value {{ $address ? '' : 'is-empty' }}">{{ $address ?: 'Not set' }}</span>
                </div>
            </div>
        </div>
    </div>
@endsection
