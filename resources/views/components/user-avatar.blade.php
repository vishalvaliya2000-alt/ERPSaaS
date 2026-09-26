@props([
    'name' => 'User',
    'size' => 'md',
    'status' => null,
])

@php
    $words = explode(' ', trim($name));
    $initials = '';
    foreach (array_slice($words, 0, 2) as $w) {
        $initials .= strtoupper(mb_substr($w, 0, 1));
    }
    if (empty($initials)) $initials = 'U';

    $sizeClasses = match($size) {
        'xs' => 'w-6 h-6 text-[10px]',
        'sm' => 'w-8 h-8 text-xs',
        'lg' => 'w-12 h-12 text-base font-black',
        'xl' => 'w-16 h-16 text-xl font-black',
        default => 'w-9 h-9 text-xs font-bold',
    };
@endphp

<div class="relative inline-flex shrink-0">
    <div {{ $attributes->merge([
        'class' => "{$sizeClasses} rounded-full bg-[#091315] text-[#D7FF53] flex items-center justify-center font-display border border-neutral-800 shadow-2xs select-none"
    ]) }}>
        {{ $initials }}
    </div>

    @if ($status)
        <span class="absolute bottom-0 right-0 block h-2 w-2 rounded-full ring-2 ring-white {{ $status === 'online' ? 'bg-emerald-500' : 'bg-neutral-400' }}"></span>
    @endif
</div>
