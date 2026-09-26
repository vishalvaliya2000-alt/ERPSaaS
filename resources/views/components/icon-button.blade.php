@props([
    'variant' => 'secondary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'title' => null,
    'disabled' => false,
])

@php
    $baseClasses = 'inline-flex items-center justify-center rounded-full transition-all duration-150 active:scale-95 cursor-pointer shrink-0';

    $sizeClasses = match($size) {
        'xs' => 'w-7 h-7 text-xs',
        'sm' => 'w-8 h-8 text-xs',
        'lg' => 'w-11 h-11 text-base',
        default => 'w-9 h-9 text-sm',
    };

    $variantClasses = match($variant) {
        'primary' => 'bg-[#D7FF53] hover:bg-[#C8F043] text-[#091315] shadow-sm',
        'obsidian' => 'bg-[#091315] hover:bg-black text-[#D7FF53]',
        'secondary' => 'bg-neutral-100 hover:bg-neutral-200 text-neutral-700 hover:text-neutral-900',
        'outline' => 'bg-white hover:bg-neutral-50 text-neutral-700 border border-neutral-200/80 shadow-2xs',
        'ghost' => 'bg-transparent hover:bg-neutral-100 text-neutral-500 hover:text-neutral-900',
        'danger' => 'bg-rose-50 hover:bg-rose-100 text-rose-600',
        default => 'bg-neutral-100 hover:bg-neutral-200 text-neutral-700',
    };

    $classes = "{$baseClasses} {$sizeClasses} {$variantClasses}";
@endphp

@if ($href)
    <a href="{{ $href }}" title="{{ $title }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" title="{{ $title }}" {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
