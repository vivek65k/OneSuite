@extends('layouts.admin')

@section('title', 'Approval Levels')

@section('content')
    <x-page-title title="Approval Levels" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.approval-levels.update') }}">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-1">How many managers must approve</h4>
                <p class="text-muted font-13 mb-0">
                    Levels follow the reporting line: <strong>level 1</strong> is the employee's own manager ("Reports to"),
                    <strong>level 2</strong> is that manager's manager, and <strong>level 3</strong> the one above them.
                    A request is only approved once every level you ask for here has said yes.
                </p>
            </div>
            <div class="card-body">
                <div class="approval-levels">
                    @foreach ($types as $key => $type)
                        <div class="approval-level-row">
                            <span class="approval-level-icon"><i class="ti {{ $type['icon'] }}"></i></span>
                            <span class="approval-level-name">{{ $type['label'] }}</span>
                            <div class="approval-level-choice" role="radiogroup" aria-label="{{ $type['label'] }}">
                                @for ($level = 1; $level <= $maxLevels; $level++)
                                    <input type="radio" class="btn-check" name="levels[{{ $key }}]" id="levels-{{ $key }}-{{ $level }}" value="{{ $level }}"
                                           @checked((int) old("levels.$key", $type['levels']) === $level) @disabled(! $canEdit)>
                                    <label class="btn btn-sm btn-outline-secondary" for="levels-{{ $key }}-{{ $level }}">{{ $level }} {{ Str::plural('level', $level) }}</label>
                                @endfor
                            </div>
                        </div>
                    @endforeach
                </div>
                @error('levels.*')<div class="text-danger font-12 mt-2">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4 class="card-title">How it works</h4>
            </div>
            <div class="card-body">
                <ul class="text-muted font-13 mb-0 ps-3">
                    <li class="mb-1">A manager approves for their own level. A manager further up can approve early, which also covers the levels below them.</li>
                    <li class="mb-1">HR (a role with "All employees" data access) and the super admin can settle a request at any point, so nothing gets stuck when a manager is away.</li>
                    <li class="mb-1">If an employee has fewer managers above them than the levels asked for, the request needs only the managers they have. Someone with no manager is approved by HR.</li>
                    <li class="mb-1">A rejection at any level is final.</li>
                    <li>Other requests (loans, promotions, transfers, resignations and so on) keep their single approval.</li>
                </ul>
            </div>
        </div>

        @if ($canEdit)
            <div class="mb-4">
                <button type="submit" class="btn btn-de-primary text-light px-4">Save</button>
            </div>
        @endif
    </form>
@endsection
