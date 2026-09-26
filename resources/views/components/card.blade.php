@props([
    'title' => null,
    'subtitle' => null,
    'action' => null,
    'padded' => true,
    'hover' => false,
])

<div {{ $attributes->merge([
    'class' => 'bg-white rounded-3xl border border-neutral-200/80 shadow-[0_2px_12px_rgba(0,0,0,0.02)] overflow-hidden transition-shadow ' .
    ($hover ? 'hover:shadow-md ' : '')
]) }}>
    @if ($title || isset($header) || $action)
        <div class="px-6 py-4.5 border-b border-neutral-100 flex items-center justify-between gap-4">
            <div>
                @if ($title)
                    <h3 class="text-sm font-black text-neutral-900 tracking-tight font-display">{{ $title }}</h3>
                @endif
                @if ($subtitle)
                    <p class="text-xs text-neutral-500 mt-0.5">{{ $subtitle }}</p>
                @endif
                {{ $header ?? '' }}
            </div>
            @if ($action)
                <div class="shrink-0">
                    {{ $action }}
                </div>
            @endif
        </div>
    @endif

    <div class="{{ $padded ? 'p-6' : '' }}">
        {{ $slot }}
    </div>

    @if (isset($footer))
        <div class="px-6 py-3.5 bg-[#F5F6F8]/60 border-t border-neutral-100 flex items-center justify-between">
            {{ $footer }}
        </div>
    @endif
</div>
