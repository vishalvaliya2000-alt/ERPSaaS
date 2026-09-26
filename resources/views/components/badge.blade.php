@props([
    'variant' => 'neutral',
    'size' => 'md',
    'dot' => false,
])

@php
    $baseClasses = 'inline-flex items-center gap-1.5 font-bold rounded-full select-none';

    $sizeClasses = match($size) {
        'sm' => 'px-2 py-0.5 text-[10px]',
        'lg' => 'px-3.5 py-1 text-xs',
        default => 'px-2.5 py-0.5 text-xs',
    };

    $variantClasses = match($variant) {
        'lime' => 'bg-[#D7FF53] text-[#091315] border border-[#D7FF53]/40',
        'lime-soft' => 'bg-[#F3FED4] text-[#091315] border border-[#D7FF53]',
        'obsidian' => 'bg-[#091315] text-[#D7FF53] border border-neutral-800',
        'emerald' => 'bg-emerald-50 text-emerald-700 border border-emerald-200/80',
        'amber' => 'bg-amber-50 text-amber-800 border border-amber-200/80',
        'rose' => 'bg-rose-50 text-rose-700 border border-rose-200/80',
        'indigo' => 'bg-indigo-50 text-indigo-700 border border-indigo-200/80',
        'neutral' => 'bg-[#F5F6F8] text-neutral-700 border border-neutral-200/80',
        default => 'bg-[#F5F6F8] text-neutral-700 border border-neutral-200/80',
    };

    $dotColor = match($variant) {
        'lime', 'lime-soft' => 'bg-[#091315]',
        'obsidian' => 'bg-[#D7FF53]',
        'emerald' => 'bg-emerald-500',
        'amber' => 'bg-amber-500',
        'rose' => 'bg-rose-500',
        'indigo' => 'bg-indigo-500',
        default => 'bg-neutral-500',
    };

    $classes = "{$baseClasses} {$sizeClasses} {$variantClasses}";
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    @if ($dot)
        <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>
    @endif
    {{ $slot }}
</span>
