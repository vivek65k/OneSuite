@extends('layouts.admin')

@section('title', $app['label'])

@section('content')
    <x-page-title :title="$app['label']" />

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="{{ $app['icon'] }} font-24 mb-2 d-block" style="font-size: 2.5rem;"></i>
                    <h4 class="mb-1">{{ $app['label'] }} is coming soon</h4>
                    <p class="text-muted mb-0">{{ $app['description'] }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
