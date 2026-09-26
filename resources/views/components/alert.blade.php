@props([
    'type' => 'success',
    'title' => null,
    'dismissible' => true,
])

@php
    $typeClasses = match($type) {
        'success' => 'bg-emerald-50 border-emerald-200 text-emerald-800',
        'warning' => 'bg-amber-50 border-amber-200 text-amber-800',
        'error', 'danger' => 'bg-rose-50 border-rose-200 text-rose-800',
        'info' => 'bg-neutral-50 border-neutral-200 text-neutral-800',
        'lime' => 'bg-[#F3FED4] border-[#D7FF53] text-[#091315]',
        default => 'bg-neutral-50 border-neutral-200 text-neutral-800',
    };

    $icon = match($type) {
        'success' => '✓',
        'warning' => '⚠️',
        'error', 'danger' => '✕',
        'info' => 'ℹ️',
        'lime' => '⚡',
        default => '•',
    };
@endphp

<div
    x-data="{ show: true }"
    x-show="show"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    {{ $attributes->merge([
        'class' => "p-4 rounded-2xl border text-xs flex items-start gap-3 {$typeClasses}"
    ]) }}
>
    <span class="w-5 h-5 rounded-full bg-white/60 flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs">
        {{ $icon }}
    </span>

    <div class="flex-1 space-y-0.5">
        @if ($title)
            <h5 class="font-bold text-xs">{{ $title }}</h5>
        @endif
        <div class="font-medium text-xs">
            {{ $slot }}
        </div>
    </div>

    @if ($dismissible)
        <button
            type="button"
            @click="show = false"
            class="text-current opacity-60 hover:opacity-100 transition-opacity p-0.5 cursor-pointer"
        >
            ✕
        </button>
    @endif
</div>
