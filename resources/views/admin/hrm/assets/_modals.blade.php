{{-- Edit and transfer dialogs shared by Assign Assets and the Laptop / Mobile / ... registers. Needs $formPeople, $canCreate and $canUpdate. --}}
    @if ($canUpdate)
        <div class="modal fade" id="asset-modal" tabindex="-1" aria-labelledby="asset-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="#" class="modal-content" id="asset-edit">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="asset-modal-title">Change asset details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="font-13 text-muted mb-3" id="edit-holder"></div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label" for="edit-category">Category</label>
                                <select name="category" id="edit-category" class="form-select" required>
                                    @foreach (\App\Models\EmployeeAsset::CATEGORIES as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="edit-date">Handed over on</label>
                                <input type="date" name="assigned_date" id="edit-date" max="{{ today()->toDateString() }}" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="edit-name">Asset</label>
                                <input type="text" name="asset_name" id="edit-name" maxlength="255" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="edit-brand">Brand / model</label>
                                <input type="text" name="brand_model" id="edit-brand" maxlength="255" class="form-control">
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="edit-tag">Asset tag</label>
                                <input type="text" name="asset_tag" id="edit-tag" maxlength="100" class="form-control">
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="edit-identifier">Serial / number</label>
                                <input type="text" name="identifier" id="edit-identifier" maxlength="100" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-check font-14 me-1"></i> Save</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($canUpdate && $canCreate)
        <div class="modal fade" id="transfer-modal" tabindex="-1" aria-labelledby="transfer-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="#" class="modal-content" id="transfer-form">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="transfer-modal-title">Transfer asset</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="font-13 text-muted mb-3" id="transfer-intro"></div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="transfer-employee">Hand it to</label>
                                <select name="employee_id" id="transfer-employee" class="form-select" required>
                                    <option value="">Select employee</option>
                                    @foreach ($formPeople as $person)
                                        <option value="{{ $person->id }}">{{ $person->name }}{{ $person->employee_code ? ' ('.$person->employee_code.')' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="transfer-date">Handed over on</label>
                                <input type="date" name="transfer_date" id="transfer-date" max="{{ today()->toDateString() }}" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="transfer-remarks">Note <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="remarks" id="transfer-remarks" maxlength="500" class="form-control" placeholder="Why it is changing hands">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-switch-horizontal font-14 me-1"></i> Transfer</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

@push('scripts')
    <script>
        (function () {
            var el = function (id) { return document.getElementById(id); };
            var today = @json(today()->toDateString());

            var editModal = el('asset-modal');
            if (editModal) {
                editModal.addEventListener('show.bs.modal', function (event) {
                    var b = event.relatedTarget;
                    if (!b) return;
                    el('asset-edit').action = b.dataset.action;
                    el('edit-holder').textContent = b.dataset.holder ? 'Held by ' + b.dataset.holder : '';
                    el('edit-category').value = b.dataset.category; el('edit-date').value = b.dataset.date || '';
                    el('edit-name').value = b.dataset.name; el('edit-brand').value = b.dataset.brand || '';
                    el('edit-tag').value = b.dataset.tag || ''; el('edit-identifier').value = b.dataset.identifier || '';
                });
            }

            var transferModal = el('transfer-modal');
            if (transferModal) {
                transferModal.addEventListener('show.bs.modal', function (event) {
                    var b = event.relatedTarget;
                    if (!b) return;
                    el('transfer-form').action = b.dataset.action;
                    el('transfer-intro').textContent = b.dataset.name + ' is with ' + (b.dataset.holder || 'someone') + ' now. Their record is closed on the handover date and a new one opens for the next person.';
                    var pick = el('transfer-employee');
                    pick.value = '';
                    // The current holder can't receive their own asset.
                    [].forEach.call(pick.options, function (o) { o.disabled = o.value !== '' && o.value === b.dataset.holderId; });
                    el('transfer-date').value = today; el('transfer-date').min = b.dataset.since || '';
                    el('transfer-remarks').value = '';
                });
            }
        })();
    </script>
@endpush
