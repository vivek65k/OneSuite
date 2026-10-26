@extends('layouts.admin')

@section('title', 'Compliance Reports')

@php
    use Illuminate\Support\Str;

    $gaps = $missingTaxDeclaration->count() + $missingKyc->count();
    $gapTone = $gaps > 0 ? 'rose' : 'success';
@endphp

@section('content')
    <x-page-title title="Compliance Reports" />

    <div class="record-header">
        <span class="list-avatar list-avatar-square record-avatar tone-{{ $gapTone }}" aria-hidden="true"><i class="ti {{ $gaps > 0 ? 'ti-shield-x' : 'ti-shield-check' }} font-20"></i></span>
        <div class="record-header-text">
            <div class="record-header-title">{{ $gaps > 0 ? $gaps.' compliance '.Str::plural('gap', $gaps).' to close' : 'No compliance gaps' }}</div>
            <div class="record-header-meta">
                <span class="status-dot status-dot-{{ $pfSetting->is_enabled ? 'success' : 'muted' }}">PF {{ $pfSetting->is_enabled ? 'enabled' : 'disabled' }}</span>
                <span class="status-dot status-dot-{{ $esiSetting->is_enabled ? 'success' : 'muted' }}">ESI {{ $esiSetting->is_enabled ? 'enabled' : 'disabled' }}</span>
                <span>FY {{ $financialYear }}</span>
            </div>
        </div>
        <div class="record-header-actions">
            <x-export-csv-button route="admin.compliance-reports.export" />
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="PF Policy" :value="$pfSetting->is_enabled ? 'Enabled' : 'Disabled'" icon="ti-building-bank" :tone="$pfSetting->is_enabled ? 'success' : 'slate'" :hint="$pfSetting->is_enabled ? $pfSetting->employee_contribution_percent.'% employee / '.$pfSetting->employer_contribution_percent.'% employer' : null" />
        <x-kpi-card label="ESI Policy" :value="$esiSetting->is_enabled ? 'Enabled' : 'Disabled'" icon="ti-medical-cross" :tone="$esiSetting->is_enabled ? 'success' : 'slate'" :hint="$esiSetting->is_enabled ? $esiSetting->employee_contribution_percent.'% employee / '.$esiSetting->employer_contribution_percent.'% employer' : null" />
        <x-kpi-card label="Missing Tax Declaration" :value="$missingTaxDeclaration->count()" icon="ti-receipt-tax" :tone="$missingTaxDeclaration->count() > 0 ? 'amber' : 'success'" :hint="'FY '.$financialYear" />
        <x-kpi-card label="Missing KYC Documents" :value="$missingKyc->count()" icon="ti-id" :tone="$missingKyc->count() > 0 ? 'rose' : 'success'" />
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            <div class="card list-view tone-amber mb-0 h-100">
                <div class="list-toolbar">
                    <div class="list-toolbar-title"><i class="ti ti-receipt-tax"></i> Missing Tax Declaration ({{ $financialYear }}) <span class="list-tab-count">{{ $missingTaxDeclaration->count() }}</span></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <tbody>
                            @forelse ($missingTaxDeclaration as $employee)
                                <tr>
                                    <td>
                                        <div class="list-user">
                                            <x-avatar :person="$employee" />
                                            <div class="list-user-text">
                                                <div class="list-user-name">{{ $employee->name }}</div>
                                                <div class="list-user-meta">{{ $employee->employee_code }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end"><span class="tag tone-amber">Not declared</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted py-5"><i class="ti ti-shield-check font-24 d-block mb-2"></i>All active employees have declared.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card list-view tone-rose mb-0 h-100">
                <div class="list-toolbar">
                    <div class="list-toolbar-title"><i class="ti ti-id"></i> Missing KYC Documents (PAN / Aadhar) <span class="list-tab-count">{{ $missingKyc->count() }}</span></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <tbody>
                            @forelse ($missingKyc as $row)
                                <tr>
                                    <td>
                                        <div class="list-user">
                                            <x-avatar :person="$row['employee']" />
                                            <div class="list-user-text">
                                                <div class="list-user-name">{{ $row['employee']->name }}</div>
                                                <div class="list-user-meta">{{ $row['employee']->employee_code }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        @foreach ($row['missing'] as $doc)
                                            <span class="tag tone-rose">{{ strtoupper($doc) }}</span>
                                        @endforeach
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted py-5"><i class="ti ti-shield-check font-24 d-block mb-2"></i>All active employees have PAN and Aadhar on file.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
