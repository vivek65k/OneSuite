@extends('layouts.admin')

@section('title', 'Employee Documents')

@php
    use App\Http\Controllers\Admin\Mss\TeamDocumentController as TeamDocs;
    use App\Models\EmployeeDocument;
    use Illuminate\Support\Str;

    $pageUrl = fn (array $overrides = []) => route('admin.team-documents.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All documents', 'tone' => 'indigo', 'count' => $counts['all']],
        'expiring' => ['label' => 'Expired or expiring', 'tone' => 'rose', 'count' => $counts['expiring']],
        'personal' => ['label' => 'Their own papers', 'tone' => 'teal', 'count' => $counts['personal']],
        'company' => ['label' => 'From the company', 'tone' => 'violet', 'count' => $counts['company']],
    ];
    $filterValues = ['employee_id' => $focus?->name, 'type' => EmployeeDocument::TYPES[$state['type']] ?? null, 'search' => $state['search']];
    $activeFilters = collect(['employee_id' => 'Person', 'type' => 'Type', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $fileMeta = ['pdf' => ['rose', 'ti-file-text', 'PDF'], 'doc' => ['indigo', 'ti-file-text', 'Word'], 'docx' => ['indigo', 'ti-file-text', 'Word'], 'jpg' => ['teal', 'ti-photo', 'Image'], 'jpeg' => ['teal', 'ti-photo', 'Image'], 'png' => ['teal', 'ti-photo', 'Image'], 'webp' => ['teal', 'ti-photo', 'Image']];
    $next = $kpis['next'];
@endphp

@section('content')
    <x-page-title title="Employee Documents" />

    @unless ($hasTeam)
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="ti ti-users font-24 d-block mb-2"></i>
                <div class="fw-semibold text-body mb-1">Nobody reports to you</div>
                <span class="font-13">This page shows the documents on file for the people below you in the reporting line. HR sets who reports to whom on each employee's profile.</span>
            </div>
        </div>
    @else
        <div class="kpi-strip">
            <x-kpi-card label="Documents on file" :value="$kpis['documents']" icon="ti-file-text" tone="indigo" :hint="$kpis['with'].' of '.$kpis['people'].' '.Str::plural('person', $kpis['people']).' have something on file'" />
            <x-kpi-card label="Expired" :value="$kpis['expired']" icon="ti-alert-triangle" :tone="$kpis['expired'] ? 'rose' : 'success'" :hint="$kpis['expired'] ? 'for '.$kpis['expired_people'].' '.Str::plural('person', $kpis['expired_people']).'; ask them to renew' : 'Nothing has run out'" :href="$kpis['expired'] ? $pageUrl(['tab' => 'expiring']) : null" />
            <x-kpi-card label="Expiring soon" :value="$kpis['soon']" icon="ti-clock" :tone="$kpis['soon'] ? 'amber' : 'teal'" :hint="$next ? Str::before($next->employee->name, ' ').'\'s '.Str::limit($next->name, 16).' in '.$next->days_left.' '.Str::plural('day', $next->days_left) : 'Nothing in the next '.TeamDocs::SOON_DAYS.' days'" :href="$kpis['soon'] ? $pageUrl(['tab' => 'expiring']) : null" />
            <x-kpi-card label="Incomplete files" :value="$kpis['incomplete']" icon="ti-list-check" :tone="$kpis['incomplete'] ? 'violet' : 'success'" :hint="$kpis['incomplete'] ? $kpis['missing_papers'].' expected '.Str::plural('paper', $kpis['missing_papers']).' missing across the team' : 'Everyone has the essentials'" />
        </div>

        <div class="card list-view">
            <form method="GET" action="{{ route('admin.team-documents.index') }}">
                @if ($state['tab'] !== '')
                    <input type="hidden" name="tab" value="{{ $state['tab'] }}">
                @endif
                <div class="list-toolbar">
                    <nav class="list-tabs" aria-label="Document group">
                        @foreach ($tabs as $value => $tab)
                            @if ($value === '' || $tab['count'] || $state['tab'] === $value)
                                <a href="{{ $pageUrl(['tab' => $value ?: null]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $state['tab'] === $value ? 'active' : '' }}">
                                    {{ $tab['label'] }}
                                    <span class="list-tab-count">{{ $tab['count'] }}</span>
                                </a>
                            @endif
                        @endforeach
                    </nav>
                    <div class="list-toolbar-actions">
                        <div class="list-search">
                            <i class="ti ti-search"></i>
                            <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search document or person" aria-label="Search">
                        </div>
                        <select name="employee_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Team member">
                            <option value="">Whole team</option>
                            @foreach ($everyone as $person)
                                <option value="{{ $person->id }}" @selected($focus?->id === $person->id)>{{ $person->name }}{{ in_array($person->status, ['terminated', 'inactive'], true) ? ' (left)' : '' }}</option>
                            @endforeach
                        </select>
                        <select name="type" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Document type">
                            <option value="">Every type</option>
                            @foreach (EmployeeDocument::TYPES as $value => $label)
                                @if (($typeCounts[$value] ?? 0) || $state['type'] === $value)
                                    <option value="{{ $value }}" @selected($state['type'] === $value)>{{ $label }} ({{ $typeCounts[$value] ?? 0 }})</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>

            @if ($activeFilters->isNotEmpty())
                <div class="list-subbar">
                    <div class="list-chips">
                        @foreach ($activeFilters as $filter)
                            <a href="{{ $pageUrl([$filter['key'] => null]) }}" class="filter-chip" title="Remove filter">{{ $filter['label'] }}: <strong>{{ $filter['value'] }}</strong> <i class="ti ti-x"></i></a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="table-responsive table-responsive-sticky">
                <table class="table table-hover list-table mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Team member</th>
                            <th>Document</th>
                            <th>Type</th>
                            <th>Expiry</th>
                            <th>Added</th>
                            <th class="text-end"><span class="visually-hidden">Open</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($documents as $document)
                            @php
                                $person = $document->employee;
                                $file = $fileMeta[$document->extension] ?? ['slate', 'ti-file', strtoupper($document->extension ?: 'File')];
                            @endphp
                            <tr class="{{ in_array($document->expiry_state, ['expired', 'soon'], true) ? 'is-pending-row' : '' }}">
                                <td>
                                    <a href="{{ $pageUrl(['employee_id' => $person->id, 'tab' => null]) }}" class="list-user text-body" title="Show only {{ $person->name }}'s documents">
                                        <x-avatar :person="$person" />
                                        <div class="list-user-text">
                                            <div class="list-user-name">{{ $person->name }}</div>
                                            <div class="list-user-meta">{{ collect([$person->designation ?: $person->department?->name, $person->is_direct ? null : 'indirect'])->filter()->implode(' · ') ?: $person->employee_code }}</div>
                                        </div>
                                    </a>
                                </td>
                                <td>
                                    <div class="list-user">
                                        <span class="list-avatar list-avatar-square tone-{{ $file[0] }}"><i class="ti {{ $file[1] }}"></i></span>
                                        <div class="list-user-text">
                                            <a href="{{ $document->url }}" target="_blank" rel="noopener" class="list-user-name">{{ $document->name ?: 'Untitled document' }}</a>
                                            <div class="list-user-meta">{{ $file[2] }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="tag tone-{{ $document->is_personal ? 'teal' : 'violet' }}">{{ EmployeeDocument::TYPES[$document->category] ?? ($document->category ? Str::headline($document->category) : 'Uncategorized') }}</span>
                                </td>
                                <td class="text-nowrap">
                                    @if ($document->expiry_state === 'none')
                                        <span class="text-muted">—</span>
                                    @else
                                        {{ $document->expiry_date->format('M j, Y') }}
                                        <div>
                                            @if ($document->expiry_state === 'expired')
                                                <span class="tag tone-rose"><i class="ti ti-alert-triangle"></i>Expired {{ abs($document->days_left) }} {{ Str::plural('day', abs($document->days_left)) }} ago</span>
                                            @elseif ($document->expiry_state === 'soon')
                                                <span class="tag tone-amber"><i class="ti ti-clock"></i>{{ $document->days_left === 0 ? 'Expires today' : 'In '.$document->days_left.' '.Str::plural('day', $document->days_left) }}</span>
                                            @else
                                                <span class="payslip-parts">valid</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="text-nowrap text-muted">{{ $document->created_at?->format('M j, Y') }}</td>
                                <td class="text-end"><a href="{{ $document->url }}" target="_blank" rel="noopener" class="btn-icon btn-icon-accent" title="Open"><i class="ti ti-eye font-16"></i></a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="ti ti-file-text font-24 d-block mb-2"></i>
                                    @if ($activeFilters->isNotEmpty() || $state['tab'] !== '')
                                        <div class="fw-semibold text-body mb-1">{{ $state['tab'] === 'expiring' && $activeFilters->isEmpty() ? 'Nothing has expired or is about to' : 'No documents match' }}</div>
                                        <span class="font-13"><a href="{{ route('admin.team-documents.index') }}">Show all documents</a></span>
                                    @else
                                        <div class="fw-semibold text-body mb-1">No documents on file for your team yet</div>
                                        <span class="font-13">People add their own papers on My Documents; HR files letters and contracts.</span>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($shownOf[1] > $shownOf[0])
                <div class="list-subbar"><span class="font-12 text-muted">Showing the latest {{ $shownOf[0] }} of {{ $shownOf[1] }}. Pick a person or a type to see the rest.</span></div>
            @endif
        </div>

        <div class="card list-view">
            <div class="list-toolbar"><span class="list-toolbar-title">What each file has</span></div>
            <div class="table-responsive">
                <table class="table list-table mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Team member</th>
                            @foreach (TeamDocs::EXPECTED as $type)
                                <th class="text-center">{{ EmployeeDocument::TYPES[$type] }}</th>
                            @endforeach
                            <th>On file</th>
                            <th>Needs attention</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($byPerson as $row)
                            <tr>
                                <td>
                                    <a href="{{ $pageUrl(['employee_id' => $row['employee']->id, 'tab' => null, 'type' => null, 'search' => null]) }}" class="list-user text-body" title="Show only {{ $row['employee']->name }}'s documents">
                                        <x-avatar :person="$row['employee']" />
                                        <div class="list-user-text"><div class="list-user-name">{{ $row['employee']->name }}</div></div>
                                    </a>
                                </td>
                                @foreach ($row['have'] as $type => $has)
                                    <td class="text-center">
                                        <i class="ti {{ $has ? 'ti-circle-check text-success' : 'ti-circle-dashed text-muted' }} font-18" title="{{ EmployeeDocument::TYPES[$type] }} {{ $has ? 'on file' : 'missing' }}"></i>
                                        <span class="visually-hidden">{{ $has ? 'On file' : 'Missing' }}</span>
                                    </td>
                                @endforeach
                                <td>{{ $row['count'] }}</td>
                                <td>
                                    @if ($row['expired'])
                                        <span class="tag tone-rose"><i class="ti ti-alert-triangle"></i>{{ $row['expired'] }} expired</span>
                                    @endif
                                    @if ($row['soon'])
                                        <span class="tag tone-amber"><i class="ti ti-clock"></i>{{ $row['soon'] }} expiring</span>
                                    @endif
                                    @if ($row['missing']->isNotEmpty())
                                        <span class="font-12 text-muted" title="{{ $row['missing']->implode(', ') }}">{{ $row['missing']->count() }} missing</span>
                                    @elseif (! $row['expired'] && ! $row['soon'])
                                        <span class="font-12 text-success"><i class="ti ti-check"></i> Complete</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="list-subbar"><span class="font-12 text-muted"><i class="ti ti-info-circle"></i> PAN and Aadhar are uploaded by the person on My Documents; the offer and appointment letters are filed by HR. You can open documents here but not change them.</span></div>
        </div>
    @endunless
@endsection
