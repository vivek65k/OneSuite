@php
    $isEditing = $holiday->exists;
    $cancelUrl = route('admin.holidays.index', $isEditing ? ['year' => $holiday->date->year] : []);
    $date = old('date', optional($holiday->date)->format('Y-m-d'));
    $recurring = (bool) old('is_recurring', $isEditing ? $holiday->is_recurring : false);
    $nextYear = fn ($m, $d) => (now()->month > $m || (now()->month === $m && now()->day > $d)) ? now()->year + 1 : now()->year;
    // Fixed-date holidays fill in the date too; moveable ones (Holi, Diwali, Eid) only the name.
    $suggestions = [
        ['name' => "New Year's Day", 'm' => 1, 'd' => 1],
        ['name' => 'Republic Day', 'm' => 1, 'd' => 26],
        ['name' => 'Holi', 'm' => null, 'd' => null],
        ['name' => 'Labour Day', 'm' => 5, 'd' => 1],
        ['name' => 'Independence Day', 'm' => 8, 'd' => 15],
        ['name' => 'Gandhi Jayanti', 'm' => 10, 'd' => 2],
        ['name' => 'Diwali', 'm' => null, 'd' => null],
        ['name' => 'Christmas Day', 'm' => 12, 'd' => 25],
    ];
@endphp

<div class="record-header">
    <a href="{{ $cancelUrl }}" class="btn-icon record-back" title="Back"><i class="ti ti-arrow-left font-18"></i></a>
    <span class="list-avatar list-avatar-square record-avatar tone-pink" aria-hidden="true"><i class="ti ti-gift font-20"></i></span>
    <div class="record-header-text">
        <div class="record-header-title">{{ $isEditing ? $holiday->name : 'New holiday' }}</div>
        <div class="record-header-meta">
            @if ($isEditing)
                <span>{{ $holiday->date->format('l, M j, Y') }}</span>
                <span class="tag {{ $holiday->is_recurring ? 'tone-pink' : 'tag-outline tone-slate' }}">{{ $holiday->is_recurring ? 'Every year' : 'One-time' }}</span>
            @else
                Rosters, leave and attendance treat this day as a day off for everyone.
            @endif
        </div>
    </div>
    <div class="record-header-actions">
        <a href="{{ $cancelUrl }}" class="btn btn-light btn-sm px-3">Cancel</a>
        <button type="submit" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Add holiday' }}</button>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <div class="fw-semibold mb-1">Please fix {{ $errors->count() === 1 ? 'this issue' : 'these '.$errors->count().' issues' }} before saving:</div>
        <ul class="mb-0 ps-3 font-13">
            @foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card form-section tone-pink">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-gift"></i></span>
                <div>
                    <h5 class="form-section-title">Holiday</h5>
                    <p class="form-section-sub">Pick a common one or type your own.</p>
                </div>
            </div>
            <div class="form-section-body">
                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $holiday->name) }}" maxlength="255" placeholder="e.g. Independence Day" required>
                <div class="quick-chips">
                    @foreach ($suggestions as $s)
                        <button type="button" class="quick-chip" data-name="{{ $s['name'] }}"
                                @if ($s['m']) data-date="{{ sprintf('%04d-%02d-%02d', $nextYear($s['m'], $s['d']), $s['m'], $s['d']) }}" @endif>
                            {{ $s['name'] }}{{ $s['m'] ? '' : ' *' }}
                        </button>
                    @endforeach
                </div>
                <div class="form-hint"><i class="ti ti-info-circle"></i> Fixed-date holidays also fill in the date and repeat yearly. * Dates move each year - set the date yourself.</div>
            </div>
        </div>

        <div class="card form-section tone-indigo">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-calendar-event"></i></span>
                <div>
                    <h5 class="form-section-title">Date</h5>
                    <p class="form-section-sub">Holidays apply to every branch.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label" for="date">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('date') is-invalid @enderror" id="date" name="date" value="{{ $date }}" required>
                    </div>
                    <div class="col-md-7">
                        <div class="holiday-weekday" id="hd-weekday">Pick a date</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card form-section tone-violet mb-0">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-repeat"></i></span>
                <div>
                    <h5 class="form-section-title">Repeats</h5>
                    <p class="form-section-sub">Yearly holidays show up every year on the same month and day.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="toggle-cards">
                    <label class="toggle-card tone-slate">
                        <input type="radio" name="is_recurring" value="0" @checked(! $recurring)>
                        <span class="toggle-card-icon"><i class="ti ti-calendar"></i></span>
                        <span class="toggle-card-body">
                            <span class="toggle-card-title">One-time</span>
                            <span class="toggle-card-hint">Only on this date - e.g. Diwali, which moves each year</span>
                        </span>
                    </label>
                    <label class="toggle-card tone-pink">
                        <input type="radio" name="is_recurring" value="1" @checked($recurring)>
                        <span class="toggle-card-icon"><i class="ti ti-repeat"></i></span>
                        <span class="toggle-card-body">
                            <span class="toggle-card-title">Every year</span>
                            <span class="toggle-card-hint">Same day every year - e.g. Independence Day</span>
                        </span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="holiday-card" id="hd-card">
            <div class="holiday-card-month" id="hd-month">—</div>
            <div class="holiday-card-day" id="hd-day">?</div>
            <div class="holiday-card-name" id="hd-name">Holiday name</div>
            <div class="holiday-card-when" id="hd-when">Pick a date</div>
            <div class="holiday-card-tags" id="hd-tags"></div>
            <div class="holiday-card-next" id="hd-next" hidden>
                <div class="leave-summary-label">Next dates</div>
                <ul id="hd-next-list"></ul>
            </div>
            <div class="leave-alert is-danger" id="hd-clash" hidden><i class="ti ti-alert-circle"></i> <span></span></div>
        </div>
    </div>
</div>

<div class="record-footer">
    <a href="{{ $cancelUrl }}" class="btn btn-light px-4">Cancel</a>
    <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Add holiday' }}</button>
</div>

@push('scripts')
    <script>
        (function () {
            var existing = @json($existing);
            var $ = function (id) { return document.getElementById(id); };
            var name = $('name'), date = $('date');

            function parse(v) { return new Date(v + 'T00:00:00'); }
            function md(v) { return v.slice(5); }
            function yearOf(v) { return parseInt(v.slice(0, 4), 10); }
            function recurring() { var r = document.querySelector('input[name="is_recurring"]:checked'); return r && r.value === '1'; }
            function tag(text, tone) { var s = document.createElement('span'); s.className = 'tag tone-' + tone; s.textContent = text; return s; }

            function refresh() {
                $('hd-name').textContent = name.value.trim() || 'Holiday name';
                var tags = $('hd-tags'); tags.innerHTML = '';
                if (!date.value) {
                    $('hd-month').textContent = '—'; $('hd-day').textContent = '?'; $('hd-when').textContent = 'Pick a date';
                    $('hd-weekday').textContent = 'Pick a date'; $('hd-next').hidden = true; $('hd-clash').hidden = true;
                    return;
                }
                var d = parse(date.value), today = new Date(); today.setHours(0, 0, 0, 0);
                var days = Math.round((d - today) / 86400000), dow = d.getDay();
                $('hd-month').textContent = d.toLocaleDateString(undefined, { month: 'short', year: 'numeric' });
                $('hd-day').textContent = d.getDate();
                $('hd-when').textContent = d.toLocaleDateString(undefined, { weekday: 'long' }) + ' · ' + (days === 0 ? 'today' : days > 0 ? 'in ' + days + ' day' + (days === 1 ? '' : 's') : Math.abs(days) + ' day' + (days === -1 ? '' : 's') + ' ago');
                $('hd-weekday').textContent = d.toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });

                if (dow === 0 || dow === 6) tags.appendChild(tag('Falls on a weekend', 'slate'));
                if (dow === 1 || dow === 5) tags.appendChild(tag('Long weekend', 'amber'));
                if (days < 0) tags.appendChild(tag('In the past', 'slate'));
                tags.appendChild(tag(recurring() ? 'Every year' : 'One-time', recurring() ? 'pink' : 'slate'));

                // Next three yearly dates.
                var list = $('hd-next-list'); list.innerHTML = '';
                $('hd-next').hidden = !recurring();
                if (recurring()) {
                    for (var y = Math.max(d.getFullYear(), today.getFullYear()), n = 0; n < 3; y++) {
                        var occ = new Date(y, d.getMonth(), d.getDate());
                        if (occ.getMonth() !== d.getMonth() || occ < today) continue;
                        var li = document.createElement('li');
                        li.textContent = occ.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
                        list.appendChild(li); n++;
                    }
                }

                // Same rule as the server: same day and overlapping years.
                var clash = existing.find(function (h) {
                    return md(h.date) === md(date.value) && (yearOf(h.date) === d.getFullYear() || (h.yearly && yearOf(h.date) <= d.getFullYear()) || (recurring() && d.getFullYear() <= yearOf(h.date)));
                });
                $('hd-clash').hidden = !clash;
                if (clash) $('hd-clash').querySelector('span').textContent = '“' + clash.name + '” is already on this day' + (clash.yearly ? ' every year' : '') + '.';
            }

            document.querySelectorAll('[data-name]').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    name.value = chip.dataset.name;
                    if (chip.dataset.date) {
                        date.value = chip.dataset.date;
                        document.querySelector('input[name="is_recurring"][value="1"]').checked = true;
                    }
                    refresh();
                });
            });
            [name, date].forEach(function (el) { el.addEventListener('input', refresh); });
            document.querySelectorAll('input[name="is_recurring"]').forEach(function (el) { el.addEventListener('change', refresh); });
            refresh();
        })();
    </script>
@endpush
