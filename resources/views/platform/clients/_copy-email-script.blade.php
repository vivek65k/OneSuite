    @push('scripts')
        <script>
            document.querySelectorAll('.client-admin-copy').forEach(function (button) {
                button.addEventListener('click', function () {
                    navigator.clipboard.writeText(button.dataset.copy).then(function () {
                        button.innerHTML = '<i class="ti ti-check"></i>';
                        setTimeout(function () { button.innerHTML = '<i class="ti ti-copy"></i>'; }, 1500);
                    });
                });
            });
        </script>
    @endpush
