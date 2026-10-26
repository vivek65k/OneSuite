@props(['title', 'breadcrumbs' => []])

<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <h4 class="page-title">{{ $title }}</h4>
            <nav aria-label="Breadcrumb" class="crumb-trail">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.dashboard') }}" class="crumb-home"><i class="ti ti-home" aria-hidden="true"></i> <span>OneSuite</span></a>
                    </li>
                    @foreach ($breadcrumbs as $label => $url)
                        <li class="breadcrumb-item">
                            @if (is_string($label))
                                <a href="{{ $url }}">{{ $label }}</a>
                            @else
                                <span>{{ $url }}</span>
                            @endif
                        </li>
                    @endforeach
                    <li class="breadcrumb-item active" aria-current="page"><span class="crumb-current" title="{{ $title }}">{{ $title }}</span></li>
                </ol>
            </nav>
        </div>
    </div>
</div>
