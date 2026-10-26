@php
    $company = \App\Models\Company::current();
    $onesuiteApp = \App\Support\AppLauncher::currentKey();
@endphp
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8" />
    <title>@yield('title', 'Dashboard') - {{ $company->name }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('vendor/metrica/assets/images/favicon.ico') }}">

    @stack('styles')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

    <link href="{{ asset('vendor/metrica/assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('vendor/metrica/assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('vendor/metrica/assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('vendor/metrica/assets/css/onesuite-sidebar.css') }}?v={{ filemtime(public_path('vendor/metrica/assets/css/onesuite-sidebar.css')) }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('vendor/metrica/assets/css/onesuite-theme.css') }}?v={{ filemtime(public_path('vendor/metrica/assets/css/onesuite-theme.css')) }}" rel="stylesheet" type="text/css" />
</head>

<body id="body" data-app="{{ $onesuiteApp }}">
    <script>
        // Applied here, before the sidebar itself renders, so a collapsed
        // choice from an earlier page doesn't flash open for a moment on
        // this one. Only the desktop icon-rail state persists — the mobile
        // off-canvas overlay is a transient per-page thing, not carried
        // across navigations.
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            document.body.classList.add('sidebar-collapsed');
        }
    </script>

    @include('partials.admin.sidebar')

    <div class="topbar">
        @include('partials.admin.topbar')
    </div>

    <div class="page-wrapper">
        <div class="page-content-tab">
            <div class="container-fluid">
                @yield('content')
            </div>

            <footer class="footer text-center text-sm-start">
                {{ $company->footer_text ?: '© '.date('Y').' '.$company->name }}
                <span class="text-muted d-none d-sm-inline-block float-end">Powered by OneSuite ERP</span>
            </footer>
        </div>
    </div>

    <script src="{{ asset('vendor/metrica/assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/metrica/assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('vendor/metrica/assets/libs/feather-icons/feather.min.js') }}"></script>

    @stack('scripts')

    <script src="{{ asset('vendor/metrica/assets/js/app.js') }}"></script>
</body>
</html>
