<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8" />
    <title>@yield('title', 'Sign in') - One Suite</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="{{ asset('vendor/metrica/assets/images/favicon.ico') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="{{ asset('vendor/metrica/assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('vendor/metrica/assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('vendor/metrica/assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('vendor/metrica/assets/css/onesuite-theme.css') }}?v={{ filemtime(public_path('vendor/metrica/assets/css/onesuite-theme.css')) }}" rel="stylesheet" type="text/css" />
</head>

<body id="body" class="auth-page">
    <div class="auth-shell">
        <div class="auth-brand-panel">
            <a href="{{ route('login') }}" class="auth-brand-logo">
                <img src="{{ asset('vendor/metrica/assets/images/logo-sm.png') }}" height="32" alt="logo">
                <span>One Suite</span>
            </a>

            <div class="auth-brand-copy">
                <h1>Run your entire business from one place.</h1>
                <p>People, payroll, and operations — unified in a single, beautifully simple ERP.</p>

                <ul class="auth-feature-list">
                    <li>
                        <span class="auth-feature-icon"><i class="ti ti-users"></i></span>
                        Employee &amp; workforce management
                    </li>
                    <li>
                        <span class="auth-feature-icon"><i class="ti ti-calendar-time"></i></span>
                        Attendance, leave &amp; shift scheduling
                    </li>
                    <li>
                        <span class="auth-feature-icon"><i class="ti ti-report-money"></i></span>
                        Payroll &amp; compliance, done right
                    </li>
                </ul>
            </div>

            <p class="auth-brand-footer">&copy; {{ date('Y') }} One Suite &middot; Powered by OneSuite ERP</p>
        </div>

        <div class="auth-form-panel">
            <div class="auth-form-panel-inner">
                <a href="{{ route('login') }}" class="auth-form-mobile-brand d-lg-none">
                    <img src="{{ asset('vendor/metrica/assets/images/logo-sm.png') }}" height="28" alt="logo">
                    <span>One Suite</span>
                </a>

                @yield('content')
            </div>
        </div>
    </div>

    <script src="{{ asset('vendor/metrica/assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/metrica/assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('vendor/metrica/assets/libs/feather-icons/feather.min.js') }}"></script>
    <script src="{{ asset('vendor/metrica/assets/js/app.js') }}"></script>
</body>
</html>
