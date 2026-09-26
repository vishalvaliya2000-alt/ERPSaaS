@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'disabled' => false,
    'icon' => null,
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-bold transition-all duration-150 active:scale-[0.98] select-none cursor-pointer rounded-full gap-2';

    $sizeClasses = match($size) {
        'xs' => 'px-2.5 py-1 text-[11px]',
        'sm' => 'px-3 py-1.5 text-xs',
        'lg' => 'px-6 py-3.5 text-sm font-extrabold',
        default => 'px-4 py-2.5 text-xs',
    };

    $variantClasses = match($variant) {
        'primary' => 'bg-[#D7FF53] hover:bg-[#C8F043] text-[#091315] font-extrabold shadow-sm hover:shadow border border-[#D7FF53]/30',
        'obsidian' => 'bg-[#091315] hover:bg-black text-[#D7FF53] font-extrabold shadow-sm border border-neutral-800',
        'secondary' => 'bg-[#F5F6F8] hover:bg-neutral-200/70 text-neutral-800 border border-neutral-200/80 font-semibold',
        'outline' => 'bg-white hover:bg-neutral-50 text-neutral-700 border border-neutral-300 font-semibold shadow-2xs',
        'ghost' => 'bg-transparent hover:bg-neutral-100 text-neutral-600 hover:text-neutral-900 font-medium',
        'danger' => 'bg-rose-600 hover:bg-rose-700 text-white font-bold shadow-sm',
        'gradient' => 'bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-white font-bold shadow-md shadow-amber-500/20 border border-amber-300/30',
        default => 'bg-[#D7FF53] hover:bg-[#C8F043] text-[#091315] font-extrabold',
    };

    if ($disabled) {
        $variantClasses .= ' opacity-50 cursor-not-allowed pointer-events-none';
    }

    $classes = "{$baseClasses} {$sizeClasses} {$variantClasses}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)
            <span class="shrink-0">{!! $icon !!}</span>
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)
            <span class="shrink-0">{!! $icon !!}</span>
        @endif
        {{ $slot }}
    </button>
@endif
