@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'rows' => 3,
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'error' => null,
    'hint' => null,
])

@php
    $textareaId = $id ?? $name ?? 'textarea-' . uniqid();
    $hasError = $error || ($name && isset($errors) && $errors->has($name));
    $errorMessage = $error ?? ($name && isset($errors) ? $errors->first($name) : null);
@endphp

<div class="space-y-1.5 w-full">
    @if ($label)
        <label for="{{ $textareaId }}" class="block text-xs font-bold text-neutral-700">
            {{ $label }}
            @if ($required)
                <span class="text-rose-500 font-bold ml-0.5">*</span>
            @endif
        </label>
    @endif

    <textarea
        name="{{ $name }}"
        id="{{ $textareaId }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        {{ $required ? 'required' : '' }}
        {{ $disabled ? 'disabled' : '' }}
        {{ $attributes->merge([
            'class' => 'w-full px-3.5 py-2.5 bg-white border rounded-xl text-neutral-900 placeholder-neutral-400 font-medium text-xs transition-all ' .
            ($hasError
                ? 'border-rose-300 focus:ring-2 focus:ring-rose-500 focus:border-rose-500'
                : 'border-neutral-300 focus:outline-hidden focus:ring-2 focus:ring-[#091315] focus:border-[#091315]') .
            ($disabled ? ' bg-neutral-50 text-neutral-500 cursor-not-allowed' : '')
        ]) }}
    >{{ old($name, $value ?? $slot) }}</textarea>

    @if ($hasError && $errorMessage)
        <p class="text-[11px] font-semibold text-rose-600 flex items-center gap-1">
            <span>⚠️</span>
            <span>{{ $errorMessage }}</span>
        </p>
    @elseif ($hint)
        <p class="text-[11px] text-neutral-400">{{ $hint }}</p>
    @endif
</div>
