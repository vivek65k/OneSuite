@extends('layouts.admin')

@section('title', 'Biometric Sync')

@php
    $codes = $sampleCodes->isNotEmpty() ? $sampleCodes->values() : collect(['EMP-001', 'EMP-002']);
    $sample = "employee_code,date,check_in_time,check_out_time\n"
        .$codes[0].','.now()->toDateString().",09:02,18:05\n"
        .($codes[1] ?? $codes[0]).','.now()->toDateString().",09:15,\n";
    $lastSync = $stats['last_sync'] ? \Illuminate\Support\Carbon::parse($stats['last_sync']) : null;
@endphp

@section('content')
    <x-page-title title="Biometric Sync" :breadcrumbs="['Attendance' => route('admin.attendance.dashboard')]" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Last sync" :value="$lastSync ? $lastSync->diffForHumans() : 'Never'" icon="ti-refresh" tone="indigo" :hint="$lastSync?->format('M j, Y g:i A')" />
        <x-kpi-card label="Biometric records" :value="$stats['total']" icon="ti-fingerprint" tone="violet" hint="All time" />
        <x-kpi-card label="Last 30 days" :value="$stats['last_30']" icon="ti-calendar-stats" tone="teal" />
        <x-kpi-card label="Employees with codes" :value="$stats['employees_with_codes']" icon="ti-id" tone="sky" hint="Rows match on employee code" />
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <form method="POST" action="{{ route('admin.attendance.biometric-sync.store') }}" enctype="multipart/form-data" class="card form-section tone-indigo">
                @csrf
                <div class="form-section-head">
                    <span class="form-section-icon"><i class="ti ti-upload"></i></span>
                    <div>
                        <h5 class="form-section-title">Import device export</h5>
                        <p class="form-section-sub">Upload the CSV exported from your biometric device.</p>
                    </div>
                </div>
                <div class="form-section-body">
                    @if ($errors->any())
                        <div class="alert alert-danger py-2 font-13">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
                    @endif
                    <label class="file-drop @error('file') is-invalid @enderror" for="biometric-file">
                        <i class="ti ti-cloud-upload"></i>
                        <span class="file-drop-title" id="biometric-label">Click to choose a CSV file</span>
                        <span class="file-drop-sub">or drag and drop it here · .csv or .txt, up to 5 MB</span>
                        <input type="file" id="biometric-file" name="file" accept=".csv,.txt" required>
                    </label>
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                        <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-upload font-14 me-1"></i> Import attendance</button>
                        <a href="data:text/csv;charset=utf-8,{{ rawurlencode($sample) }}" download="biometric-sample.csv" class="btn btn-light"><i class="ti ti-download font-14 me-1"></i> Download sample CSV</a>
                    </div>
                    <div class="form-hint"><i class="ti ti-info-circle"></i> Re-importing the same day is safe - existing records for that employee and date are updated, never duplicated.</div>
                </div>
            </form>
        </div>

        <div class="col-lg-5 d-flex flex-column gap-3">
            <div class="card form-section tone-sky mb-0">
                <div class="form-section-head form-section-head-compact">
                    <span class="form-section-icon form-section-icon-sm"><i class="ti ti-table"></i></span>
                    <h5 class="form-section-title">File format</h5>
                </div>
                <div class="form-section-body">
                    <table class="table table-sm format-table mb-2">
                        <thead><tr><th>Column</th><th>Format</th><th></th></tr></thead>
                        <tbody>
                            <tr><td><code>employee_code</code></td><td>As in the employee record</td><td><span class="tag tone-danger">Required</span></td></tr>
                            <tr><td><code>date</code></td><td>YYYY-MM-DD</td><td><span class="tag tone-danger">Required</span></td></tr>
                            <tr><td><code>check_in_time</code></td><td>HH:MM (24h)</td><td><span class="tag tone-slate">Optional</span></td></tr>
                            <tr><td><code>check_out_time</code></td><td>HH:MM (24h)</td><td><span class="tag tone-slate">Optional</span></td></tr>
                        </tbody>
                    </table>
                    <pre class="code-sample">{{ $sample }}</pre>
                    <div class="list-subtext">Imported rows are marked <strong>Present</strong> with source <strong>Biometric</strong>. Rows with an unknown employee code are skipped and counted.</div>
                </div>
            </div>

            <div class="card side-list tone-violet mb-0">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-fingerprint"></i></span>
                    <span class="fw-semibold">Recent biometric days</span>
                </div>
                @forelse ($recentDays as $row)
                    @php $day = \Illuminate\Support\Carbon::parse($row->day); @endphp
                    <a href="{{ route('admin.attendance.punch-logs', ['source' => 'biometric', 'from' => $day->toDateString(), 'to' => $day->toDateString(), 'per_page' => 25]) }}" class="side-list-item">
                        <span class="when-block tone-violet"><span class="when-block-month">{{ $day->format('M') }}</span><span class="when-block-day">{{ $day->format('j') }}</span></span>
                        <div class="flex-grow-1 font-13">{{ $day->isToday() ? 'Today' : $day->format('l') }}</div>
                        <span class="countdown tone-violet">{{ $row->aggregate }} {{ Str::plural('record', $row->aggregate) }}</span>
                    </a>
                @empty
                    <div class="side-list-empty text-muted"><i class="ti ti-fingerprint"></i> No biometric imports yet</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var input = document.getElementById('biometric-file');
            var label = document.getElementById('biometric-label');
            input.addEventListener('change', function () {
                if (input.files.length) {
                    label.textContent = input.files[0].name;
                    input.closest('.file-drop').classList.add('has-file');
                }
            });
        })();
    </script>
@endpush
