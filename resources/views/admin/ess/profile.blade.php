@extends('layouts.admin')

@section('title', 'My Profile')

@php
    use App\Http\Controllers\Admin\Ess\MyProfileController as Profile;
    use Illuminate\Support\Str;

    $statusTones = ['active' => 'success', 'on_leave' => 'warning', 'inactive' => 'muted', 'terminated' => 'danger'];
    $initials = collect(preg_split('/\s+/', trim($employee->name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: '?';
    $address = collect([$employee->address, $employee->city, $employee->state, $employee->country, $employee->postal_code])->filter()->implode(', ');
    $joined = $employee->joining_date;
    $tenure = $joined && $joined->isPast() ? $joined->diffForHumans(null, true) : null;
    $days = fn ($n) => rtrim(rtrim(number_format((float) $n, 1), '0'), '.');
    $birthday = $employee->date_of_birth ? $employee->date_of_birth->copy()->year(today()->year) : null;
    $birthday = $birthday && $birthday->lt(today()) ? $birthday->addYear() : $birthday;

    $groups = [
        ['title' => 'Personal', 'icon' => 'ti-id', 'tone' => 'violet', 'items' => [
            ['icon' => 'ti-mail', 'tone' => 'sky', 'label' => 'Work email', 'value' => $employee->email],
            ['icon' => 'ti-man', 'tone' => 'violet', 'label' => 'Gender', 'value' => $employee->gender ? Str::headline($employee->gender) : null],
            ['icon' => 'ti-gift', 'tone' => 'pink', 'label' => 'Date of birth', 'value' => $employee->date_of_birth?->format('M j, Y')],
        ]],
        ['title' => 'Employment', 'icon' => 'ti-briefcase', 'tone' => 'teal', 'items' => [
            ['icon' => 'ti-hash', 'tone' => 'slate', 'label' => 'Employee code', 'value' => $employee->employee_code],
            ['icon' => 'ti-sitemap', 'tone' => 'indigo', 'label' => 'Department', 'value' => $employee->department?->name],
            ['icon' => 'ti-building-store', 'tone' => 'sky', 'label' => 'Branch', 'value' => $employee->branch?->name],
            ['icon' => 'ti-clock', 'tone' => 'violet', 'label' => 'Employment type', 'value' => $employee->employment_type ? Str::headline($employee->employment_type) : null],
            ['icon' => 'ti-calendar-event', 'tone' => 'pink', 'label' => 'Joining date', 'value' => $joined?->format('M j, Y')],
            ['icon' => 'ti-hierarchy', 'tone' => 'amber', 'label' => 'Reports to', 'value' => $manager?->name],
        ]],
    ];
    $records = [
        ['title' => 'Family & emergency contacts', 'icon' => 'ti-users', 'tone' => 'pink', 'rows' => $employee->familyMembers->map(fn ($m) => [$m->name, collect([$m->relation, $m->phone])->filter()->implode(' · ')])],
        ['title' => 'Education', 'icon' => 'ti-school', 'tone' => 'indigo', 'rows' => $employee->educations->sortByDesc('end_year')->map(fn ($e) => [$e->qualification, collect([$e->institution, $e->field_of_study, collect([$e->start_year, $e->end_year])->filter()->implode('–')])->filter()->implode(' · ')])],
        ['title' => 'Previous experience', 'icon' => 'ti-briefcase', 'tone' => 'teal', 'rows' => $employee->experiences->sortByDesc('start_date')->map(fn ($x) => [$x->designation ?: $x->company_name, collect([$x->designation ? $x->company_name : null, collect([$x->start_date ? \Illuminate\Support\Carbon::parse($x->start_date)->format('M Y') : null, $x->end_date ? \Illuminate\Support\Carbon::parse($x->end_date)->format('M Y') : null])->filter()->implode(' – ')])->filter()->implode(' · ')])],
    ];
    $editOpen = $errors->hasAny(Profile::OWN_FIELDS);
@endphp

@section('content')
    <x-page-title title="My Profile" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="card profile-hero">
        <div class="profile-hero-cover"></div>
        <div class="profile-hero-body">
            @if ($employee->avatar)
                <img src="{{ asset('storage/'.$employee->avatar) }}" alt="" class="profile-hero-avatar">
            @else
                <span class="profile-hero-avatar" aria-hidden="true">{{ $initials }}</span>
            @endif

            <div class="profile-hero-main">
                <div class="profile-hero-name">
                    <h4>{{ $employee->name }}</h4>
                    <span class="status-dot status-dot-{{ $statusTones[$employee->status] ?? 'muted' }}">{{ Str::headline($employee->status) }}</span>
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
                    @if ($joined)
                        <span class="tag tone-pink"><i class="ti ti-calendar-event"></i>Joined {{ $joined->format('M j, Y') }}</span>
                    @endif
                </div>
            </div>

            <div class="profile-hero-actions">
                <form action="{{ route('admin.my-profile.photo') }}" method="POST" enctype="multipart/form-data" id="photo-form">
                    @csrf
                    <input type="file" name="photo" id="photo-input" class="d-none" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    <button type="button" class="btn btn-light btn-sm px-3" id="photo-pick" title="JPG, PNG or WebP, up to 2 MB"><i class="ti ti-camera font-14 me-1"></i> {{ $employee->avatar ? 'Change photo' : 'Add photo' }}</button>
                </form>
                @if ($employee->avatar)
                    <form action="{{ route('admin.my-profile.photo.remove') }}" method="POST" onsubmit="return confirm('Remove your photo?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-icon btn-icon-danger" title="Remove photo"><i class="ti ti-trash font-18"></i></button>
                    </form>
                @endif
                <button type="button" class="btn btn-de-primary btn-sm text-light px-3" data-bs-toggle="modal" data-bs-target="#contact-modal"><i class="ti ti-pencil font-14 me-1"></i> Edit contact details</button>
            </div>
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="With the company" :value="$tenure ?? '—'" icon="ti-calendar-event" tone="indigo" :hint="$joined ? ($joined->isPast() ? 'since '.$joined->format('M j, Y') : 'joining on '.$joined->format('M j, Y')) : 'Joining date not set'" />
        <x-kpi-card label="Leave left this year" :value="$balances->isNotEmpty() ? $days($leaveLeft) : '—'" icon="ti-plane" tone="teal" :hint="$balances->isNotEmpty() ? 'across '.$balances->count().' leave '.Str::plural('type', $balances->count()) : 'No leave allocated for '.now()->year" />
        <x-kpi-card label="{{ $directReports->isNotEmpty() ? 'My team' : 'Reports to' }}" :value="$directReports->isNotEmpty() ? $teamSize : ($manager ? Str::limit($manager->name, 16) : '—')" icon="ti-hierarchy" tone="amber" :hint="$directReports->isNotEmpty() ? $directReports->count().' report directly to you' : ($manager ? ($manager->designation ?: 'Your manager') : 'Nobody is set as your manager')" />
        <x-kpi-card label="Profile complete" :value="$completeness.'%'" icon="ti-user-check" :tone="$completeness === 100 ? 'success' : ($completeness < 50 ? 'rose' : 'violet')" :hint="$completeness === 100 ? 'Everything is filled in' : 'Missing: '.Str::limit(collect($checks)->reject(fn ($done) => $done)->keys()->implode(', '), 46)" />
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card form-section tone-indigo">
                <div class="form-section-head form-section-head-compact">
                    <span class="form-section-icon form-section-icon-sm"><i class="ti ti-phone"></i></span>
                    <h5 class="form-section-title">Contact details</h5>
                    <button type="button" class="btn btn-sm btn-light ms-auto" data-bs-toggle="modal" data-bs-target="#contact-modal"><i class="ti ti-pencil font-14 me-1"></i>Edit</button>
                </div>
                <div class="form-section-body">
                    <div class="info-grid">
                        <div class="info-item tone-teal">
                            <span class="info-item-icon"><i class="ti ti-phone"></i></span>
                            <div class="info-item-text">
                                <span class="info-item-label">Phone</span>
                                <span class="info-item-value {{ $employee->phone ? '' : 'is-empty' }}">{{ $employee->phone ?: 'Not set' }}</span>
                            </div>
                        </div>
                        <div class="info-item tone-amber">
                            <span class="info-item-icon"><i class="ti ti-map-pin"></i></span>
                            <div class="info-item-text">
                                <span class="info-item-label">Address</span>
                                <span class="info-item-value {{ $address ? '' : 'is-empty' }}">{{ $address ?: 'Not set' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="font-12 text-muted mt-2">You can keep these up to date yourself. HR sees the change straight away.</div>
                </div>
            </div>

            @foreach ($groups as $group)
                <div class="card form-section tone-{{ $group['tone'] }}">
                    <div class="form-section-head form-section-head-compact">
                        <span class="form-section-icon form-section-icon-sm"><i class="ti {{ $group['icon'] }}"></i></span>
                        <h5 class="form-section-title">{{ $group['title'] }}</h5>
                        <span class="font-12 text-muted ms-auto"><i class="ti ti-lock"></i> Managed by HR</span>
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
                </div>
                <div class="form-section-body">
                    <span class="info-item-label d-block mb-2">You report up to</span>
                    <div class="report-line mb-3">
                        @forelse ($reportingLine as $person)
                            <span class="report-line-person" @if ($person->email) title="{{ $person->email }}" @endif>
                                <x-avatar :person="$person" />
                                <span>{{ $person->name }}<small>{{ $person->designation ?: 'No designation' }}</small></span>
                            </span>
                            <i class="ti ti-chevron-right report-line-arrow"></i>
                        @empty
                            <span class="text-muted font-13">Nobody: you are at the top of the reporting line.</span>
                            <i class="ti ti-chevron-right report-line-arrow"></i>
                        @endforelse
                        <span class="report-line-person is-current">
                            <x-avatar :person="$employee" />
                            <span>You<small>{{ $employee->designation ?: 'No designation' }}</small></span>
                        </span>
                    </div>
                    @if ($directReports->isNotEmpty())
                        <span class="info-item-label d-block mb-2">Report directly to you ({{ $directReports->count() }}){{ $teamSize > $directReports->count() ? ' · '.$teamSize.' people in your whole team' : '' }}</span>
                        <div class="report-line">
                            @foreach ($directReports as $report)
                                <span class="report-line-person">
                                    <x-avatar :person="$report" />
                                    <span>{{ $report->name }}<small>{{ $report->designation ?: 'No designation' }}</small></span>
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            @foreach ($records as $record)
                <div class="card form-section tone-{{ $record['tone'] }}">
                    <div class="form-section-head form-section-head-compact">
                        <span class="form-section-icon form-section-icon-sm"><i class="ti {{ $record['icon'] }}"></i></span>
                        <h5 class="form-section-title">{{ $record['title'] }}</h5>
                        @if ($record['rows']->isNotEmpty())
                            <span class="count-pill ms-auto">{{ $record['rows']->count() }}</span>
                        @endif
                    </div>
                    <div class="form-section-body">
                        @forelse ($record['rows'] as [$title, $meta])
                            <div class="d-flex justify-content-between flex-wrap gap-2 py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                                <span class="fw-semibold">{{ $title ?: 'Untitled' }}</span>
                                <span class="font-13 text-muted">{{ $meta }}</span>
                            </div>
                        @empty
                            <span class="text-muted font-13">Nothing on file. Ask HR to add it to your record.</span>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>

        <div class="col-lg-4">
            <div class="card form-section tone-violet">
                <div class="form-section-head form-section-head-compact">
                    <span class="form-section-icon form-section-icon-sm"><i class="ti ti-user-check"></i></span>
                    <h5 class="form-section-title">Profile checklist</h5>
                    <span class="count-pill ms-auto">{{ collect($checks)->filter()->count() }} / {{ count($checks) }}</span>
                </div>
                <div class="form-section-body">
                    <div class="mini-progress tone-{{ $completeness === 100 ? 'success' : 'violet' }} mb-3">
                        <div class="mini-progress-bar is-toned"><span style="width: {{ $completeness }}%"></span></div>
                        <span class="mini-progress-label">{{ $completeness }}%</span>
                    </div>
                    @foreach ($checks as $label => $done)
                        <div class="d-flex align-items-center gap-2 py-1 font-13 {{ $done ? '' : 'text-muted' }}">
                            <i class="ti {{ $done ? 'ti-circle-check text-success' : 'ti-circle-dashed' }} font-16"></i>
                            <span>{{ $label }}</span>
                            @unless ($done)
                                <span class="ms-auto font-12">{{ in_array($label, ['Photo', 'Phone', 'Address'], true) ? 'you can add this' : 'ask HR' }}</span>
                            @endunless
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card form-section tone-teal">
                <div class="form-section-head form-section-head-compact">
                    <span class="form-section-icon form-section-icon-sm"><i class="ti ti-plane"></i></span>
                    <h5 class="form-section-title">Leave balance {{ now()->year }}</h5>
                    <a href="{{ route('admin.my-leave.index') }}" class="btn btn-sm btn-light ms-auto">My leave</a>
                </div>
                <div class="form-section-body">
                    @forelse ($balances as $balance)
                        @php
                            $total = (float) $balance->allocated + (float) $balance->carried_forward + (float) $balance->adjusted;
                            $left = max(0, (float) $balance->remaining);
                        @endphp
                        <div class="py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                            <div class="d-flex justify-content-between font-13">
                                <span class="fw-semibold">{{ $balance->leaveType?->name ?? 'Leave' }}</span>
                                <span>{{ $days($left) }} <span class="text-muted">of {{ $days($total) }} left</span></span>
                            </div>
                            <div class="mini-progress tone-teal">
                                <div class="mini-progress-bar is-toned"><span style="width: {{ $total > 0 ? min(100, round($left / $total * 100)) : 0 }}%"></span></div>
                            </div>
                        </div>
                    @empty
                        <span class="text-muted font-13">No leave has been allocated to you for {{ now()->year }} yet.</span>
                    @endforelse
                </div>
            </div>

            <div class="card form-section tone-sky">
                <div class="form-section-head form-section-head-compact">
                    <span class="form-section-icon form-section-icon-sm"><i class="ti ti-star"></i></span>
                    <h5 class="form-section-title">Skills</h5>
                </div>
                <div class="form-section-body">
                    @forelse ($employee->skills as $skill)
                        <span class="tag tone-sky mb-1" title="{{ Str::headline((string) $skill->proficiency) }}">{{ $skill->skill_name }}{{ $skill->proficiency ? ' · '.Str::headline($skill->proficiency) : '' }}</span>
                    @empty
                        <span class="text-muted font-13">No skills on file. Ask HR to add them.</span>
                    @endforelse
                    @if ($birthday)
                        <div class="font-12 text-muted mt-3"><i class="ti ti-gift"></i> {{ $birthday->isToday() ? 'Happy birthday!' : 'Next birthday '.$birthday->format('M j').' · in '.(int) today()->diffInDays($birthday).' days' }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="contact-modal" tabindex="-1" aria-labelledby="contact-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('admin.my-profile.update') }}" class="modal-content">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="contact-modal-title">Edit contact details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="contact-phone">Phone</label>
                            <input type="tel" name="phone" id="contact-phone" maxlength="30" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $employee->phone) }}" placeholder="+91 98765 43210">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="contact-address">Address</label>
                            <input type="text" name="address" id="contact-address" maxlength="255" class="form-control @error('address') is-invalid @enderror" value="{{ old('address', $employee->address) }}" placeholder="House, street, area">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="contact-city">City</label>
                            <input type="text" name="city" id="contact-city" maxlength="100" class="form-control @error('city') is-invalid @enderror" value="{{ old('city', $employee->city) }}">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="contact-state">State</label>
                            <input type="text" name="state" id="contact-state" maxlength="100" class="form-control @error('state') is-invalid @enderror" value="{{ old('state', $employee->state) }}">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="contact-country">Country</label>
                            <input type="text" name="country" id="contact-country" maxlength="100" class="form-control @error('country') is-invalid @enderror" value="{{ old('country', $employee->country) }}">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="contact-postal">Postal code</label>
                            <input type="text" name="postal_code" id="contact-postal" maxlength="20" class="form-control @error('postal_code') is-invalid @enderror" value="{{ old('postal_code', $employee->postal_code) }}">
                        </div>
                    </div>
                    <div class="font-12 text-muted mt-3"><i class="ti ti-lock"></i> Your name, email, job details and dates are managed by HR.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-check font-14 me-1"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var input = document.getElementById('photo-input');
            document.getElementById('photo-pick').addEventListener('click', function () { input.click(); });
            input.addEventListener('change', function () {
                var file = input.files[0];
                if (!file) return;
                if (file.size > {{ Profile::PHOTO_MAX_KILOBYTES }} * 1024) { alert('That photo is larger than 2 MB. Choose a smaller one.'); input.value = ''; return; }
                document.getElementById('photo-form').submit();
            });
            // A save that was refused: reopen the form with what was typed.
            if (@json($editOpen)) { bootstrap.Modal.getOrCreateInstance(document.getElementById('contact-modal')).show(); }
        })();
    </script>
@endpush
