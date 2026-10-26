    @if (session('invite_url'))
        <div class="alert alert-success">
            <div class="fw-semibold mb-1">Invite sent to {{ session('invite_email') }}</div>
            <p class="text-muted font-13 mb-2">
                If mail isn't configured to actually deliver yet, share this link with them directly instead - it works once and expires in 7 days.
            </p>
            <div class="input-group">
                <input type="text" class="form-control font-13" id="invite-url-field" value="{{ session('invite_url') }}" readonly onclick="this.select()">
                <button class="btn btn-light" type="button" id="copy-invite-url"><i class="ti ti-copy font-14 me-1"></i> Copy</button>
            </div>
        </div>
    @endif

    @if (session('invite_url'))
        @push('scripts')
            <script>
                document.getElementById('copy-invite-url').addEventListener('click', function () {
                    var field = document.getElementById('invite-url-field');
                    field.select();
                    navigator.clipboard.writeText(field.value).then(function () {
                        var button = document.getElementById('copy-invite-url');
                        var original = button.innerHTML;
                        button.innerHTML = '<i class="ti ti-check font-14 me-1"></i> Copied';
                        setTimeout(function () { button.innerHTML = original; }, 1500);
                    });
                });
            </script>
        @endpush
    @endif
