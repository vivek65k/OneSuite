<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8" />
    <title>@yield('title', 'Clients') - OneSuite Platform</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
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

<body id="body">
    <div class="leftbar-tab-menu" id="app-sidebar">
        <a href="{{ route('platform.clients.index') }}" class="simple-sidebar-brand text-decoration-none">
            <img src="{{ asset('vendor/metrica/assets/images/logo-sm.png') }}" alt="logo" height="28">
            <span class="logo-text">Platform Admin</span>
        </a>

        <div class="simple-sidebar-nav" data-simplebar>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('platform.clients.*') ? 'active' : '' }}" href="{{ route('platform.clients.index') }}">
                        <i class="ti ti-building-store"></i> <span class="link-text">Clients</span>
                        <span class="link-flyout">Clients</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('platform.mail-setup.*') ? 'active' : '' }}" href="{{ route('platform.mail-setup.edit') }}">
                        <i class="ti ti-mail"></i> <span class="link-text">Mail Setup</span>
                        <span class="link-flyout">Mail Setup</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <nav class="navbar-custom" id="navbar-custom">
        <ul class="list-unstyled topbar-nav float-end mb-0">
            <li class="dropdown">
                <a class="nav-link dropdown-toggle nav-user" data-bs-toggle="dropdown" href="#" role="button"
                    aria-haspopup="false" aria-expanded="false">
                    <div class="d-flex align-items-center">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'P') }}" alt="profile-user" class="rounded-circle me-2 thumb-sm" />
                        <div>
                            <small class="d-none d-md-block font-11">Platform Admin</small>
                            <span class="d-none d-md-block fw-semibold font-12">{{ auth()->user()?->name }} <i class="mdi mdi-chevron-down"></i></span>
                        </div>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item">
                            <i class="ti ti-power font-16 me-1 align-text-bottom"></i> Logout
                        </button>
                    </form>
                </div>
            </li>
        </ul>
    </nav>

    <div class="page-wrapper">
        <div class="page-content-tab">
            <div class="container-fluid">
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @yield('content')
            </div>

            <footer class="footer text-center text-sm-start">
                &copy; {{ date('Y') }} OneSuite Platform
                <span class="text-muted d-none d-sm-inline-block float-end">Platform administration</span>
            </footer>
        </div>
    </div>

    <script src="{{ asset('vendor/metrica/assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/metrica/assets/libs/simplebar/simplebar.min.js') }}"></script>

    @stack('scripts')
</body>
</html>
