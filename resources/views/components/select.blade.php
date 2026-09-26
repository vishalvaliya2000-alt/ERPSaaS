@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'required' => false,
    'disabled' => false,
    'error' => null,
    'hint' => null,
])

@php
    $selectId = $id ?? $name ?? 'select-' . uniqid();
    $hasError = $error || ($name && isset($errors) && $errors->has($name));
    $errorMessage = $error ?? ($name && isset($errors) ? $errors->first($name) : null);
@endphp

<div class="space-y-1.5 w-full">
    @if ($label)
        <label for="{{ $selectId }}" class="block text-xs font-bold text-neutral-700">
            {{ $label }}
            @if ($required)
                <span class="text-rose-500 font-bold ml-0.5">*</span>
            @endif
        </label>
    @endif

    <div class="relative rounded-xl shadow-2xs">
        <select
            name="{{ $name }}"
            id="{{ $selectId }}"
            {{ $required ? 'required' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            {{ $attributes->merge([
                'class' => 'w-full py-2.5 pl-3.5 pr-10 bg-white border rounded-xl text-neutral-900 font-medium text-xs appearance-none cursor-pointer transition-all ' .
                ($hasError
                    ? 'border-rose-300 focus:ring-2 focus:ring-rose-500 focus:border-rose-500'
                    : 'border-neutral-300 focus:outline-hidden focus:ring-2 focus:ring-[#091315] focus:border-[#091315]') .
                ($disabled ? ' bg-neutral-50 text-neutral-500 cursor-not-allowed' : '')
            ]) }}
        >
            {{ $slot }}
        </select>

        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-neutral-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </div>
    </div>

    @if ($hasError && $errorMessage)
        <p class="text-[11px] font-semibold text-rose-600 flex items-center gap-1">
            <span>⚠️</span>
            <span>{{ $errorMessage }}</span>
        </p>
    @elseif ($hint)
        <p class="text-[11px] text-neutral-400">{{ $hint }}</p>
    @endif
</div>
