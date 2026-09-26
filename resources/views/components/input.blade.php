@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'error' => null,
    'hint' => null,
    'prefix' => null,
    'suffix' => null,
])

@php
    $inputId = $id ?? $name ?? 'input-' . uniqid();
    $hasError = $error || ($name && isset($errors) && $errors->has($name));
    $errorMessage = $error ?? ($name && isset($errors) ? $errors->first($name) : null);
@endphp

<div class="space-y-1.5 w-full">
    @if ($label)
        <label for="{{ $inputId }}" class="block text-xs font-bold text-neutral-700">
            {{ $label }}
            @if ($required)
                <span class="text-rose-500 font-bold ml-0.5">*</span>
            @endif
        </label>
    @endif

    <div class="relative rounded-xl shadow-2xs">
        @if ($prefix)
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-neutral-400 text-xs">
                {!! $prefix !!}
            </div>
        @endif

        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $inputId }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            {{ $readonly ? 'readonly' : '' }}
            {{ $attributes->merge([
                'class' => 'w-full py-2.5 bg-white border rounded-xl text-neutral-900 placeholder-neutral-400 font-medium text-xs transition-all ' .
                ($prefix ? 'pl-9 ' : 'pl-3.5 ') .
                ($suffix ? 'pr-9 ' : 'pr-3.5 ') .
                ($hasError
                    ? 'border-rose-300 focus:ring-2 focus:ring-rose-500 focus:border-rose-500'
                    : 'border-neutral-300 focus:outline-hidden focus:ring-2 focus:ring-[#091315] focus:border-[#091315]') .
                ($disabled ? ' bg-neutral-50 text-neutral-500 cursor-not-allowed' : '')
            ]) }}
        >

        @if ($suffix)
            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-neutral-400 text-xs">
                {!! $suffix !!}
            </div>
        @endif
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
