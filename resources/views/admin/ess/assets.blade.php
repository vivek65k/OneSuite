@extends('layouts.admin')

@section('title', 'My Assets')

@php
    use App\Http\Controllers\Admin\Ess\MyAssetController as Assets;
    use App\Models\EmployeeAsset;
    use Illuminate\Support\Str;

    $pageUrl = fn (array $overrides = []) => route('admin.my-assets.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'Everything', 'tone' => 'indigo', 'count' => $counts['all']],
        'held' => ['label' => 'With me now', 'tone' => 'teal', 'count' => $counts['held']],
        'returned' => ['label' => 'Returned', 'tone' => 'slate', 'count' => $counts['returned']],
    ];
    // Same look per category as the HR asset pages; the third entry names the identifier.
    $categoryMeta = [
        'laptop' => ['indigo', 'ti-device-laptop', 'Serial number'],
        'mobile' => ['teal', 'ti-device-mobile', 'IMEI'],
        'id_card' => ['amber', 'ti-id', 'Card number'],
        'sim_card' => ['success', 'ti-antenna-bars-5', 'SIM / phone number'],
        'uniform' => ['rose', 'ti-shirt', 'Size'],
        'other' => ['slate', 'ti-box', 'Serial / reference'],
    ];
    $conditionMeta = ['good' => ['success', 'ti-circle-check', 'Returned in good condition'], 'damaged' => ['amber', 'ti-alert-triangle', 'Returned damaged'], 'lost' => ['rose', 'ti-alert-circle', 'Recorded as lost']];
    $span = fn (?int $days) => $days === null ? null : ($days < 1 ? 'today' : ($days < 60 ? $days.' '.Str::plural('day', $days) : ($days < 730 ? round($days / 30.4).' months' : round($days / 365, 1).' years')));
    $oldest = $kpis['oldest'];
    $daysToGo = $leaving ? (int) today()->diffInDays($leaving->last_working_day, false) : null;
@endphp

@section('content')
    <x-page-title title="My Assets" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif
    @if ($leaving)
        <div class="alert alert-warning d-flex align-items-center gap-2">
            <i class="ti ti-logout font-18"></i>
            <div>
                <strong>{{ $kpis['held'] }} {{ Str::plural('item', $kpis['held']) }} to hand back</strong>
                {{ $daysToGo < 0 ? 'Your last working day was '.$leaving->last_working_day->format('M j, Y').'. Please return them now.' : ($daysToGo === 0 ? 'by the end of today, your last working day.' : 'by '.$leaving->last_working_day->format('M j, Y').', your last working day ('.$daysToGo.' '.Str::plural('day', $daysToGo).' away).') }}
            </div>
        </div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="With me now" :value="$kpis['held']" icon="ti-device-laptop" tone="teal" :hint="$kpis['held'] ? Str::limit($kpis['kinds']->implode(', '), 40) : 'Nothing is assigned to you'" :href="$kpis['held'] ? $pageUrl(['tab' => 'held']) : null" />
        <x-kpi-card label="Held longest" :value="$oldest ? $span($oldest->days_held) : '—'" icon="ti-history" tone="indigo" :hint="$oldest ? Str::limit($oldest->asset_name, 24).' · since '.$oldest->assigned_date->format('M j, Y') : 'No assignment dates'" />
        <x-kpi-card label="Problems reported" :value="$kpis['reported']" icon="ti-alert-triangle" :tone="$kpis['reported'] ? 'amber' : 'success'" :hint="$kpis['reported'] ? 'The asset team has been told' : 'Everything is in order'" />
        <x-kpi-card label="Returned" :value="$kpis['returned']" icon="ti-arrow-back-up" tone="slate" :hint="$kpis['returned'] ? ($kpis['problems'] ? $kpis['problems'].' came back damaged or lost' : 'All in good condition') : 'Nothing handed back yet'" :href="$kpis['returned'] ? $pageUrl(['tab' => 'returned']) : null" />
    </div>

    <div class="card list-view">
        <div class="list-toolbar">
            <nav class="list-tabs" aria-label="Asset status">
                @foreach ($tabs as $value => $tab)
                    @if ($value === '' || $tab['count'] || $state['tab'] === $value)
                        <a href="{{ $pageUrl(['tab' => $value ?: null]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $state['tab'] === $value ? 'active' : '' }}">
                            {{ $tab['label'] }}
                            <span class="list-tab-count">{{ $tab['count'] }}</span>
                        </a>
                    @endif
                @endforeach
            </nav>
        </div>

        @if ($held->isEmpty() && $returned->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="ti ti-device-laptop font-24 d-block mb-2"></i>
                @if ($state['tab'] !== '' && $counts['all'])
                    <div class="fw-semibold text-body mb-1">{{ $state['tab'] === 'held' ? 'You are not holding anything right now' : 'Nothing has been returned yet' }}</div>
                    <span class="font-13"><a href="{{ route('admin.my-assets.index') }}">Show everything</a></span>
                @else
                    <div class="fw-semibold text-body mb-1">No company assets are assigned to you</div>
                    <span class="font-13">Laptops, phones, ID cards and other items appear here when they are issued to you.</span>
                @endif
            </div>
        @endif
    </div>

    @if ($held->isNotEmpty())
        <div class="row g-3 mb-3">
            @foreach ($held as $asset)
                @php $meta = $categoryMeta[$asset->category] ?? $categoryMeta['other']; @endphp
                <div class="col-md-6 col-xl-4">
                    <div class="card form-section tone-{{ $meta[0] }} mb-0 h-100">
                        <div class="form-section-head form-section-head-compact">
                            <span class="form-section-icon form-section-icon-sm"><i class="ti {{ $meta[1] }}"></i></span>
                            <div class="flex-grow-1" style="min-width: 0">
                                <h5 class="form-section-title text-truncate" title="{{ $asset->asset_name }}">{{ $asset->asset_name }}</h5>
                                <div class="font-12 text-muted">{{ EmployeeAsset::CATEGORIES[$asset->category] ?? Str::headline((string) $asset->category) }}</div>
                            </div>
                            @if ($asset->asset_tag)
                                <span class="tag tone-slate" title="Asset tag"><i class="ti ti-tag"></i>{{ $asset->asset_tag }}</span>
                            @endif
                        </div>
                        <div class="form-section-body d-flex flex-column">
                            <dl class="row font-13 mb-2">
                                @foreach (array_filter(['Brand / model' => $asset->brand_model, $meta[2] => $asset->identifier, 'With you since' => $asset->assigned_date ? $asset->assigned_date->format('M j, Y').' · '.$span($asset->days_held) : null]) as $label => $value)
                                    <dt class="col-5 text-muted fw-normal">{{ $label }}</dt>
                                    <dd class="col-7 mb-1 text-break">{{ $value }}</dd>
                                @endforeach
                            </dl>
                            @if ($asset->report)
                                <div class="run-note tone-amber mb-2"><i class="ti ti-alert-triangle"></i><span>You reported {{ Str::after($asset->report, ' on ') }}</span></div>
                            @endif
                            <div class="mt-auto d-flex justify-content-end">
                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#report-modal"
                                        data-action="{{ route('admin.my-assets.report', $asset) }}" data-name="{{ $asset->asset_name }}{{ $asset->asset_tag ? ' ('.$asset->asset_tag.')' : '' }}"><i class="ti ti-alert-triangle font-14 me-1"></i> Report a problem</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($returned->isNotEmpty())
        <div class="card list-view">
            <div class="list-toolbar"><span class="list-toolbar-title">Returned</span></div>
            <div class="table-responsive">
                <table class="table table-hover list-table mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Asset</th>
                            <th>Tag / number</th>
                            <th>With you</th>
                            <th>Returned</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($returned as $asset)
                            @php
                                $meta = $categoryMeta[$asset->category] ?? $categoryMeta['other'];
                                $condition = $conditionMeta[$asset->condition_at_return] ?? null;
                            @endphp
                            <tr>
                                <td>
                                    <div class="list-user">
                                        <span class="list-avatar list-avatar-square tone-{{ $meta[0] }}"><i class="ti {{ $meta[1] }}"></i></span>
                                        <div class="list-user-text">
                                            <div class="list-user-name">{{ $asset->asset_name }}</div>
                                            <div class="list-user-meta">{{ collect([EmployeeAsset::CATEGORIES[$asset->category] ?? Str::headline((string) $asset->category), $asset->brand_model])->filter()->implode(' · ') }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    {{ $asset->asset_tag ?: '—' }}
                                    @if ($asset->identifier)
                                        <div class="payslip-parts">{{ $meta[2] }} {{ $asset->identifier }}</div>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    {{ $asset->assigned_date?->format('M j, Y') ?? '—' }}{{ $asset->returned_date ? ' – '.$asset->returned_date->format('M j, Y') : '' }}
                                    @if ($asset->days_held !== null && $asset->returned_date)
                                        <div class="payslip-parts">{{ $span($asset->days_held) }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if ($condition)
                                        <span class="tag tone-{{ $condition[0] }}"><i class="ti {{ $condition[1] }}"></i>{{ $condition[2] }}</span>
                                    @else
                                        <span class="tag tone-slate"><i class="ti ti-arrow-back-up"></i>Returned</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="modal fade" id="report-modal" tabindex="-1" aria-labelledby="report-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="#" class="modal-content" id="report-form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="report-modal-title">Report a problem</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="font-13 text-muted mb-3" id="report-intro"></div>
                    <label class="form-label">What is wrong? <span class="text-danger">*</span></label>
                    <div class="pick-list">
                        @foreach (Assets::ISSUES as $value => $label)
                            <label class="pick-item d-flex align-items-center gap-2">
                                <input type="radio" name="issue" value="{{ $value }}" class="form-check-input mt-0" required>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <label class="form-label mt-3" for="report-note">Anything the asset team should know <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="text" name="note" id="report-note" maxlength="300" class="form-control" placeholder="e.g. Screen flickers after an hour">
                    <div class="font-12 text-muted mt-2">The asset team is notified and your report is kept on the asset's record. It does not change who holds the item.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-send font-14 me-1"></i> Send report</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var modal = document.getElementById('report-modal');
            modal.addEventListener('show.bs.modal', function (event) {
                var b = event.relatedTarget;
                if (!b) return;
                document.getElementById('report-form').action = b.dataset.action;
                document.getElementById('report-intro').textContent = 'About ' + b.dataset.name + '.';
                document.getElementById('report-note').value = '';
                modal.querySelectorAll('input[name="issue"]').forEach(function (r) { r.checked = false; });
            });
        })();
    </script>
@endpush
