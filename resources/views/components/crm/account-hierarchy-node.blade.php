@props(['account', 'depth' => 0])

<div class="hierarchy-node" style="margin-left: {{ $depth * 1.75 }}rem;">
    <div class="hierarchy-node-row">
        <i class="ti {{ $depth === 0 ? 'ti-building' : 'ti-corner-down-right' }} hierarchy-node-icon"></i>
        <a href="{{ route('admin.crm.accounts.edit', $account) }}" class="fw-semibold">{{ $account->name }}</a>
        @if ($account->contacts_count)
            <span class="tag tone-indigo">{{ $account->contacts_count }} {{ \Illuminate\Support\Str::plural('contact', $account->contacts_count) }}</span>
        @endif
        @if ($account->owner)
            <span class="text-muted font-13">{{ $account->owner->name }}</span>
        @endif
    </div>
    @if ($account->children->isNotEmpty())
        <div class="hierarchy-children">
            @foreach ($account->children as $child)
                <x-crm.account-hierarchy-node :account="$child" :depth="$depth + 1" />
            @endforeach
        </div>
    @endif
</div>
