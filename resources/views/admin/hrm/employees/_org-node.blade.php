{{-- One person on the org chart and, nested below, everyone reporting to them. --}}
@php
    $person = $node['employee'];
    $canMove = auth()->user()->can('update', $person);
    // Their manager exists but isn't on this picture: another branch, outside your access, or no longer active.
    $outsideManager = $person->manager_id && $person->manager && ! $onChart->has($person->manager_id) ? $person->manager : null;
    $managerLeft = $outsideManager && in_array($outsideManager->status, ['inactive', 'terminated'], true);
@endphp

<li class="org-item" data-org-item>
    <div class="org-node {{ $person->id === $movedId ? 'is-moved' : '' }} {{ $managerLeft || ! empty($node['loop']) ? 'needs-attention' : '' }}"
         data-org-node
         data-id="{{ $person->id }}"
         data-name="{{ $person->name }}"
         data-manager-id="{{ $person->manager_id }}"
         data-total="{{ $node['total'] }}"
         data-search="{{ Str::lower($person->name.' '.$person->designation.' '.$person->department?->name.' '.$person->employee_code) }}"
         @if ($canMove) draggable="true" data-can-move @endif>
        <x-avatar :person="$person" class="org-node-avatar" />

        <a href="{{ route('admin.employees.show', $person) }}" class="org-node-name" draggable="false">{{ $person->name }}</a>
        <div class="org-node-role">{{ $person->designation ?: 'No designation' }}</div>

        @if ($person->department)
            <span class="org-node-chip">{{ $person->department->name }}</span>
        @endif

        @if ($person->status === 'on_leave')
            <span class="org-node-chip org-node-chip-leave">On leave</span>
        @endif

        @if (! empty($node['loop']))
            <div class="org-node-flag"><i class="ti ti-alert-triangle"></i> Reporting loop - set a new manager</div>
        @elseif ($outsideManager)
            <div class="org-node-flag {{ $managerLeft ? '' : 'is-info' }}">
                <i class="ti {{ $managerLeft ? 'ti-user-off' : 'ti-arrow-up' }}"></i>
                {{ $outsideManager->name }}{{ $managerLeft ? ' (no longer active)' : ($outsideManager->branch ? ' - '.$outsideManager->branch->name : '') }}
            </div>
        @endif

        <div class="org-node-foot">
            @if ($node['direct'])
                <button type="button" class="org-node-toggle" data-org-toggle title="Show or hide the {{ $node['total'] }} {{ Str::plural('person', $node['total']) }} under {{ $person->name }}">
                    <i class="ti ti-users"></i>
                    <span>{{ $node['direct'] }}{{ $node['total'] > $node['direct'] ? ' / '.$node['total'] : '' }}</span>
                    <i class="ti ti-chevron-up org-node-chevron"></i>
                </button>
            @else
                <span class="org-node-solo">No reports</span>
            @endif

            @if ($canMove)
                <button type="button" class="org-node-move" data-org-change title="Change who {{ $person->name }} reports to">
                    <i class="ti ti-switch-horizontal"></i>
                </button>
            @endif
        </div>
    </div>

    @if ($node['children']->isNotEmpty())
        <ul>
            @foreach ($node['children'] as $child)
                @include('admin.hrm.employees._org-node', ['node' => $child])
            @endforeach
        </ul>
    @endif
</li>
