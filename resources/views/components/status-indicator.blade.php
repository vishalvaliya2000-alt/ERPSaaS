@props([
    'status' => 'active',
    'label' => null,
])

@php
    $color = match(strtolower($status)) {
        'active', 'paid', 'delivered', 'shipped', 'won', 'approved' => 'bg-emerald-500',
        'pending', 'draft', 'in_progress', 'quotation_sent', 'sample_requested' => 'bg-amber-500',
        'overdue', 'cancelled', 'rejected', 'failed', 'urgent' => 'bg-rose-500',
        default => 'bg-neutral-400',
    };
@endphp

<span class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-700">
    <span class="relative flex h-2 w-2">
        @if(in_array(strtolower($status), ['active', 'urgent', 'pending']))
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $color }} opacity-75"></span>
        @endif
        <span class="relative inline-flex rounded-full h-2 w-2 {{ $color }}"></span>
    </span>
    <span>{{ $label ?? ucfirst(str_replace('_', ' ', $status)) }}</span>
</span>
