@props([
    'placeholder' => 'Search records...',
    'model' => null,
])

<div class="relative w-full max-w-md">
    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-neutral-400">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
    </div>
    <input
        type="search"
        @if($model) x-model="{{ $model }}" @endif
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge([
            'class' => 'w-full pl-9 pr-4 py-2 bg-[#F5F6F8] hover:bg-neutral-100 focus:bg-white border border-neutral-200/80 rounded-full text-xs font-medium text-neutral-900 placeholder-neutral-400 focus:outline-hidden focus:ring-2 focus:ring-[#091315] focus:border-[#091315] transition-all'
        ]) }}
    >
</div>
