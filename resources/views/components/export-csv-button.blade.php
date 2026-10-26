@props(['route'])

<a href="{{ route($route, request()->query()) }}" class="btn btn-light btn-sm">
    <i class="ti ti-download font-14 me-1"></i> Export CSV
</a>
