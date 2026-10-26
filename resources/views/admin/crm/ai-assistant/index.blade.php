@extends('layouts.admin')

@section('title', 'Email & Proposal Assistant')

@section('content')
    <x-page-title title="Email & Proposal Assistant" />

    <div class="card form-section tone-indigo" style="max-width: 760px;">
        <div class="form-section-head">
            <span class="form-section-icon"><i class="ti ti-wand"></i></span>
            <div>
                <h5 class="form-section-title">Draft a message</h5>
                <p class="form-section-sub">Pick a recipient and a template — a draft is generated instantly, ready to tweak and send.</p>
            </div>
        </div>
        <div class="form-section-body">
            <form method="POST" action="{{ route('admin.crm.ai-assistant.generate') }}" id="assistantForm">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Template</label>
                        <select name="template" class="form-select">
                            @foreach ($templates as $key => $label)
                                <option value="{{ $key }}" @selected(($selected['template'] ?? '') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Recipient Type</label>
                        <select name="recipient_type" id="recipientType" class="form-select">
                            <option value="lead" @selected(($selected['recipient_type'] ?? '') === 'lead')>Lead</option>
                            <option value="contact" @selected(($selected['recipient_type'] ?? '') === 'contact')>Contact</option>
                            <option value="opportunity" @selected(($selected['recipient_type'] ?? '') === 'opportunity')>Opportunity</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Recipient</label>
                        <select name="recipient_id" id="leadSelect" class="form-select">
                            @foreach ($leads as $lead)<option value="{{ $lead->id }}">{{ $lead->name }}</option>@endforeach
                        </select>
                        <select name="recipient_id" id="contactSelect" class="form-select d-none" disabled>
                            @foreach ($contacts as $contact)<option value="{{ $contact->id }}">{{ $contact->name }}</option>@endforeach
                        </select>
                        <select name="recipient_id" id="opportunitySelect" class="form-select d-none" disabled>
                            @foreach ($opportunities as $opportunity)<option value="{{ $opportunity->id }}">{{ $opportunity->name }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-3"><button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-wand font-14 me-1"></i> Generate Draft</button></div>
            </form>
        </div>
    </div>

    @if ($draft)
        <div class="card mt-3" style="max-width: 760px;">
            <div class="p-3">
                <div class="fw-semibold mb-1">{{ $draft['subject'] }}</div>
                <div class="text-muted" style="white-space: pre-wrap;">{{ $draft['body'] }}</div>
            </div>
        </div>
    @endif

    <script>
        (function () {
            const typeSelect = document.getElementById('recipientType');
            const selects = { lead: document.getElementById('leadSelect'), contact: document.getElementById('contactSelect'), opportunity: document.getElementById('opportunitySelect') };
            function sync() {
                Object.entries(selects).forEach(([key, el]) => {
                    const active = key === typeSelect.value;
                    el.classList.toggle('d-none', !active);
                    el.disabled = !active;
                });
            }
            typeSelect.addEventListener('change', sync);
            sync();
        })();
    </script>
@endsection
