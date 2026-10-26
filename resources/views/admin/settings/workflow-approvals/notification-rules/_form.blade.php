<div class="card">
    <div class="card-header">
        <h4 class="card-title">Notification Rule</h4>
    </div>
    <div class="card-body">
        <div class="form-group row">
            <div class="col-md-8 mb-3">
                <label class="form-label" for="event">Event <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('event') is-invalid @enderror" id="event" name="event" value="{{ old('event', $rule->event) }}" placeholder="e.g. Leave Request Approved" required>
                @error('event')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
                <div class="form-check form-switch form-switch-success mt-4">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $rule->exists ? $rule->is_active : true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
        </div>

        <div class="form-group row">
            <div class="col-md-3 mb-3">
                <div class="form-check form-switch form-switch-success">
                    <input class="form-check-input channel-toggle" type="checkbox" id="via_in_app" name="via_in_app" value="1" data-target="#in_app_note" {{ old('via_in_app', $rule->exists ? $rule->via_in_app : true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="via_in_app">In-app</label>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="form-check form-switch form-switch-success">
                    <input class="form-check-input channel-toggle" type="checkbox" id="via_email" name="via_email" value="1" data-target="#email-template-field" {{ old('via_email', $rule->via_email) ? 'checked' : '' }}>
                    <label class="form-check-label" for="via_email">Email</label>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="form-check form-switch form-switch-success">
                    <input class="form-check-input channel-toggle" type="checkbox" id="via_sms" name="via_sms" value="1" data-target="#sms-template-field" {{ old('via_sms', $rule->via_sms) ? 'checked' : '' }}>
                    <label class="form-check-label" for="via_sms">SMS</label>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="form-check form-switch form-switch-success">
                    <input class="form-check-input channel-toggle" type="checkbox" id="via_whatsapp" name="via_whatsapp" value="1" data-target="#whatsapp-template-field" {{ old('via_whatsapp', $rule->via_whatsapp) ? 'checked' : '' }}>
                    <label class="form-check-label" for="via_whatsapp">WhatsApp</label>
                </div>
            </div>
        </div>

        <div class="form-group row">
            <div class="col-md-4 mb-3 channel-field" id="email-template-field">
                <label class="form-label" for="email_template_id">Email template</label>
                <select class="form-select" id="email_template_id" name="email_template_id">
                    <option value="">None</option>
                    @foreach ($emailTemplates as $id => $name)
                        <option value="{{ $id }}" @selected((int) old('email_template_id', $rule->email_template_id) === $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3 channel-field" id="sms-template-field">
                <label class="form-label" for="sms_template_id">SMS template</label>
                <select class="form-select" id="sms_template_id" name="sms_template_id">
                    <option value="">None</option>
                    @foreach ($smsTemplates as $id => $name)
                        <option value="{{ $id }}" @selected((int) old('sms_template_id', $rule->sms_template_id) === $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3 channel-field" id="whatsapp-template-field">
                <label class="form-label" for="whatsapp_template_id">WhatsApp template</label>
                <select class="form-select" id="whatsapp_template_id" name="whatsapp_template_id">
                    <option value="">None</option>
                    @foreach ($whatsappTemplates as $id => $name)
                        <option value="{{ $id }}" @selected((int) old('whatsapp_template_id', $rule->whatsapp_template_id) === $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

<div class="mb-4">
    <button type="submit" class="btn btn-de-primary text-light px-4">Save</button>
    <a href="{{ route('admin.notification-rules.index') }}" class="btn btn-de-danger text-light px-4">Cancel</a>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.channel-toggle').forEach(function (toggle) {
                var target = toggle.dataset.target && document.querySelector(toggle.dataset.target);
                if (!target || !target.classList.contains('channel-field')) {
                    return;
                }

                function sync() {
                    target.classList.toggle('d-none', !toggle.checked);
                }

                toggle.addEventListener('change', sync);
                sync();
            });
        });
    </script>
@endpush
