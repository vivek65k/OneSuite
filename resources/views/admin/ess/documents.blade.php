@extends('layouts.admin')

@section('title', 'My Documents')

@php
    use App\Http\Controllers\Admin\Ess\MyDocumentController as Docs;
    use App\Models\EmployeeDocument;
    use Illuminate\Support\Str;

    $pageUrl = fn (array $overrides = []) => route('admin.my-documents.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All documents', 'tone' => 'indigo', 'count' => $counts['all']],
        'mine' => ['label' => 'My own papers', 'tone' => 'teal', 'count' => $counts['mine']],
        'company' => ['label' => 'From the company', 'tone' => 'violet', 'count' => $counts['company']],
        'expiring' => ['label' => 'Expired or expiring', 'tone' => 'rose', 'count' => $counts['expiring']],
    ];
    $fileMeta = ['pdf' => ['rose', 'ti-file-text', 'PDF'], 'doc' => ['indigo', 'ti-file-text', 'Word'], 'docx' => ['indigo', 'ti-file-text', 'Word'], 'jpg' => ['teal', 'ti-photo', 'Image'], 'jpeg' => ['teal', 'ti-photo', 'Image'], 'png' => ['teal', 'ti-photo', 'Image'], 'webp' => ['teal', 'ti-photo', 'Image']];
    $typeIcons = ['pan' => 'ti-id', 'aadhar' => 'ti-id', 'passport' => 'ti-plane', 'visa' => 'ti-world', 'certificate' => 'ti-file-certificate', 'other' => 'ti-file'];
    $accept = '.'.implode(',.', EmployeeDocument::EXTENSIONS);
    $maxMb = EmployeeDocument::MAX_KILOBYTES / 1024;
    $missing = $checklist->where('done', false);
    $next = $kpis['next'];
    // After a refused save, the upload form keeps what was typed; a refused edit reopens its popup.
    $editing = old('_method') === 'PUT' ? (int) old('_document') : 0;
    $typed = collect(['name' => old('name'), 'expiry' => old('expiry_date')])->map(fn ($value) => is_scalar($value) ? (string) $value : '')->all();
    $uploadErrors = ! $editing && $errors->hasAny(['category', 'name', 'expiry_date', 'file']);
@endphp

@section('content')
    <x-page-title title="My Documents" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any() && ! $uploadErrors)
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="On file" :value="$kpis['total']" icon="ti-file-text" tone="indigo" :hint="$kpis['total'] ? $kpis['personal'].' of your own · '.$kpis['company'].' from the company' : 'Nothing uploaded yet'" />
        <x-kpi-card label="Expired" :value="$kpis['expired']" icon="ti-alert-triangle" :tone="$kpis['expired'] ? 'rose' : 'success'" :hint="$kpis['expired'] ? 'Upload a renewed copy' : 'Nothing has run out'" :href="$kpis['expired'] ? $pageUrl(['tab' => 'expiring']) : null" />
        <x-kpi-card label="Expiring soon" :value="$kpis['soon']" icon="ti-clock" :tone="$kpis['soon'] ? 'amber' : 'teal'" :hint="$next ? Str::limit($next->name, 20).' in '.$next->days_left.' '.Str::plural('day', $next->days_left) : 'Nothing in the next '.Docs::SOON_DAYS.' days'" :href="$kpis['soon'] ? $pageUrl(['tab' => 'expiring']) : null" />
        <x-kpi-card label="Still needed" :value="$missing->count()" icon="ti-list-check" :tone="$missing->count() ? 'violet' : 'success'" :hint="$missing->count() ? Str::limit($missing->pluck('label')->implode(', '), 44) : 'Your file has the essentials'" />
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('admin.my-documents.store') }}" enctype="multipart/form-data" class="card form-section tone-indigo mb-0 h-100" id="upload-form">
                @csrf
                <div class="form-section-head">
                    <span class="form-section-icon"><i class="ti ti-upload"></i></span>
                    <div class="flex-grow-1">
                        <h5 class="form-section-title">Upload a document</h5>
                        <p class="form-section-sub">Your own papers: identity, passport, visa, certificates. Only you, your managers and HR can open them.</p>
                    </div>
                </div>
                <div class="form-section-body">
                    @if ($uploadErrors)
                        <div class="alert alert-danger py-2 font-13">@foreach (collect(['category', 'name', 'expiry_date', 'file'])->flatMap(fn ($field) => $errors->get($field)) as $message)<div>{{ $message }}</div>@endforeach</div>
                    @endif
                    @if ($canUpload)
                        <label class="form-label">What is it? <span class="text-danger">*</span></label>
                        <div class="segmented mb-3 flex-wrap" id="upload-types" role="group" aria-label="Document type">
                            @foreach ($types as $value => $label)
                                <button type="button" class="segmented-item {{ ! $editing && old('category') === $value ? 'active' : '' }}" data-type="{{ $value }}" data-label="{{ $label }}" data-expires="{{ in_array($value, Docs::EXPIRING, true) ? 1 : 0 }}"><i class="ti {{ $typeIcons[$value] ?? 'ti-file' }}"></i> {{ $label }}</button>
                            @endforeach
                        </div>
                        <input type="hidden" name="category" id="upload-category" value="{{ $editing ? '' : old('category') }}">

                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label" for="upload-name">Name <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="name" id="upload-name" maxlength="255" class="form-control" value="{{ $editing ? '' : old('name') }}" placeholder="e.g. Passport (renewed 2026)">
                            </div>
                            <div class="col-md-5" id="upload-expiry-wrap" hidden>
                                <label class="form-label" for="upload-expiry">Expires on <span class="text-muted fw-normal">(if it does)</span></label>
                                <input type="date" name="expiry_date" id="upload-expiry" class="form-control" value="{{ $editing ? '' : old('expiry_date') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="upload-file">File <span class="text-danger">*</span></label>
                                <input type="file" name="file" id="upload-file" class="form-control" accept="{{ $accept }}" required>
                                <div class="run-note tone-indigo mt-2" id="upload-note"><i class="ti ti-file-text"></i><span>PDF, JPG, PNG, WebP or Word, up to {{ $maxMb }} MB.</span></div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-de-primary text-light px-4" id="upload-submit"><i class="ti ti-upload font-14 me-1"></i> Upload</button>
                        </div>
                    @else
                        <div class="run-note tone-amber"><i class="ti ti-alert-triangle"></i><span>You have {{ $maxDocuments }} documents of your own, which is the limit. Remove one you no longer need to upload another.</span></div>
                    @endif
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="card form-section tone-violet mb-0 h-100">
                <div class="form-section-head form-section-head-compact">
                    <span class="form-section-icon form-section-icon-sm"><i class="ti ti-list-check"></i></span>
                    <h5 class="form-section-title">What your file should have</h5>
                    <span class="count-pill ms-auto">{{ $checklist->where('done', true)->count() }} / {{ $checklist->count() }}</span>
                </div>
                <div class="form-section-body">
                    @foreach ($checklist as $type => $item)
                        <div class="d-flex align-items-center gap-2 py-2 font-13 {{ $loop->last ? '' : 'border-bottom' }} {{ $item['done'] ? '' : 'text-muted' }}">
                            <i class="ti {{ $item['done'] ? 'ti-circle-check text-success' : 'ti-circle-dashed' }} font-16"></i>
                            <span class="{{ $item['done'] ? 'fw-semibold' : '' }}">{{ $item['label'] }}</span>
                            @if ($item['done'])
                                <span class="ms-auto font-12 text-success">on file</span>
                            @elseif ($item['from'] === 'you' && $canUpload)
                                <button type="button" class="btn btn-link btn-sm p-0 ms-auto" data-pick-type="{{ $type }}">Upload it</button>
                            @else
                                <span class="ms-auto font-12">HR provides this</span>
                            @endif
                        </div>
                    @endforeach
                    <div class="font-12 text-muted mt-3"><i class="ti ti-lock"></i> Letters and contracts issued by the company appear here once HR files them. You can open them but not change them.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card list-view">
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
        </div>
        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Document</th>
                        <th>Type</th>
                        <th>Expiry</th>
                        <th>Added</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($documents as $document)
                        @php
                            $file = $fileMeta[$document->extension] ?? ['slate', 'ti-file', strtoupper($document->extension ?: 'File')];
                            $expires = in_array($document->category, Docs::EXPIRING, true) || blank($document->category);
                        @endphp
                        <tr class="{{ in_array($document->expiry_state, ['expired', 'soon'], true) ? 'is-pending-row' : '' }}">
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
                                <div class="payslip-parts">{{ $document->is_personal ? 'your own' : 'from the company' }}</div>
                            </td>
                            <td class="text-nowrap">
                                @if ($document->expiry_state === 'none')
                                    <span class="text-muted">{{ $expires ? 'No date set' : 'Does not expire' }}</span>
                                @else
                                    {{ $document->expiry_date->format('M j, Y') }}
                                    <div>
                                        @if ($document->expiry_state === 'expired')
                                            <span class="tag tone-rose"><i class="ti ti-alert-triangle"></i>Expired {{ abs($document->days_left) }} {{ Str::plural('day', abs($document->days_left)) }} ago</span>
                                        @elseif ($document->expiry_state === 'soon')
                                            <span class="tag tone-amber"><i class="ti ti-clock"></i>{{ $document->days_left === 0 ? 'Expires today' : 'In '.$document->days_left.' '.Str::plural('day', $document->days_left) }}</span>
                                        @else
                                            <span class="payslip-parts">valid · {{ $document->expiry_date->diffForHumans(today(), ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE, 'parts' => 1]) }} left</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="text-nowrap text-muted">{{ $document->created_at?->format('M j, Y') }}</td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    <a href="{{ $document->url }}" target="_blank" rel="noopener" class="btn-icon btn-icon-accent" title="Open"><i class="ti ti-eye font-16"></i></a>
                                    @if ($document->is_personal)
                                        <button type="button" class="btn-icon" title="{{ $document->expiry_state === 'expired' ? 'Upload the renewed copy' : 'Edit or replace' }}" data-bs-toggle="modal" data-bs-target="#edit-modal"
                                                data-id="{{ $document->id }}" data-action="{{ route('admin.my-documents.update', $document) }}" data-name="{{ $document->name }}" data-expiry="{{ $document->expiry_date?->toDateString() }}" data-expires="{{ $expires ? 1 : 0 }}"><i class="ti ti-edit font-16"></i></button>
                                        <form action="{{ route('admin.my-documents.destroy', $document) }}" method="POST" class="d-inline" onsubmit="return confirm(@js('Remove '.($document->name ?: 'this document').'? The file is deleted and cannot be brought back.'))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    @else
                                        <span class="btn-icon disabled opacity-50" title="Issued by the company; only HR can change or remove it" aria-disabled="true"><i class="ti ti-lock font-16"></i></span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti ti-file-text font-24 d-block mb-2"></i>
                                @if ($state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">{{ $state['tab'] === 'expiring' ? 'Nothing has expired or is about to' : 'No documents in this group' }}</div>
                                    <span class="font-13"><a href="{{ route('admin.my-documents.index') }}">Show all documents</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No documents yet</div>
                                    <span class="font-13">Start with your PAN and Aadhar using the form above.</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="edit-modal" tabindex="-1" aria-labelledby="edit-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="#" enctype="multipart/form-data" class="modal-content" id="edit-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="_document" id="edit-id">
                <div class="modal-header">
                    <h5 class="modal-title" id="edit-modal-title">Edit document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="edit-name">Name</label>
                    <input type="text" name="name" id="edit-name" maxlength="255" class="form-control" required>
                    <div id="edit-expiry-wrap">
                        <label class="form-label mt-3" for="edit-expiry">Expires on</label>
                        <input type="date" name="expiry_date" id="edit-expiry" class="form-control" style="max-width: 220px">
                    </div>
                    <label class="form-label mt-3" for="edit-file">Replace the file <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="file" name="file" id="edit-file" class="form-control" accept="{{ $accept }}">
                    <div class="font-12 text-muted mt-1">Leave empty to keep the current file. A new file replaces the old one for good.</div>
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
            var $ = function (id) { return document.getElementById(id); };
            var maxBytes = {{ (int) EmployeeDocument::MAX_KILOBYTES }} * 1024;
            var allowed = @json(EmployeeDocument::EXTENSIONS);
            var size = function (bytes) { return bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB'; };
            // Why this file would be refused, or '' when it is fine.
            function fileProblem(file) {
                var ext = (file.name.split('.').pop() || '').toLowerCase();
                if (allowed.indexOf(ext) === -1) return 'That kind of file is not accepted. Use a PDF, an image or a Word document.';
                if (file.size > maxBytes) return 'That file is ' + size(file.size) + '; the limit is {{ $maxMb }} MB.';
                return '';
            }

            var category = $('upload-category');
            if (category) {
                var note = $('upload-note'), input = $('upload-file');
                function pick(type) {
                    var chosen = null;
                    $('upload-types').querySelectorAll('.segmented-item').forEach(function (b) { var on = b.dataset.type === type; b.classList.toggle('active', on); if (on) chosen = b; });
                    category.value = chosen ? type : '';
                    $('upload-expiry-wrap').hidden = !(chosen && chosen.dataset.expires === '1');
                    if ($('upload-expiry-wrap').hidden) $('upload-expiry').value = '';
                    $('upload-name').placeholder = chosen ? chosen.dataset.label : 'e.g. Passport (renewed 2026)';
                }
                $('upload-types').addEventListener('click', function (event) { var b = event.target.closest('[data-type]'); if (b) pick(b.dataset.type); });
                document.querySelectorAll('[data-pick-type]').forEach(function (b) {
                    b.addEventListener('click', function () { pick(b.dataset.pickType); $('upload-form').scrollIntoView({ behavior: 'smooth', block: 'center' }); input.focus(); });
                });
                input.addEventListener('change', function () {
                    var file = input.files[0], why = file ? fileProblem(file) : '';
                    note.className = 'run-note mt-2 tone-' + (why ? 'rose' : (file ? 'teal' : 'indigo'));
                    note.querySelector('span').textContent = why || (file ? file.name + ' · ' + size(file.size) : 'PDF, JPG, PNG, WebP or Word, up to {{ $maxMb }} MB.');
                    $('upload-submit').disabled = !!why;
                });
                $('upload-form').addEventListener('submit', function (event) {
                    if (!category.value) { event.preventDefault(); alert('Choose what kind of document this is.'); return; }
                    setTimeout(function () { $('upload-submit').disabled = true; }, 0);
                });
                pick(category.value);
            }

            var modal = $('edit-modal');
            function fill(d) {
                $('edit-form').action = d.action;
                $('edit-id').value = d.id;
                $('edit-name').value = d.name || '';
                $('edit-expiry').value = d.expiry || '';
                $('edit-expiry-wrap').hidden = d.expires !== '1';
                $('edit-file').value = '';
            }
            modal.addEventListener('show.bs.modal', function (event) { if (event.relatedTarget) fill(event.relatedTarget.dataset); });
            $('edit-file').addEventListener('change', function () {
                var file = this.files[0], why = file ? fileProblem(file) : '';
                if (why) { alert(why); this.value = ''; }
            });
            // A refused edit: reopen the popup with what was typed.
            var reopen = {{ $editing }};
            var button = reopen ? document.querySelector('[data-bs-target="#edit-modal"][data-id="' + reopen + '"]') : null;
            if (button) {
                fill(Object.assign({}, button.dataset, @json($typed)));
                bootstrap.Modal.getOrCreateInstance(modal).show();
            }
        })();
    </script>
@endpush
