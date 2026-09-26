@props([
    'title',
    'subtitle' => null,
    'icon' => null,
    'badge' => null,
])

<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-neutral-200/80 shadow-[0_2px_12px_rgba(0,0,0,0.02)]">
    <div class="flex items-center gap-3.5">
        @if ($icon)
            <div class="w-11 h-11 rounded-2xl bg-[#F5F6F8] text-[#091315] border border-neutral-200/80 flex items-center justify-center text-xl shrink-0 shadow-2xs">
                {!! $icon !!}
            </div>
        @endif
        <div class="space-y-0.5">
            <div class="flex items-center gap-2.5">
                <h1 class="text-xl font-black text-neutral-900 tracking-tight font-display">
                    {{ $title }}
                </h1>
                @if ($badge)
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#F5F6F8] text-neutral-800 border border-neutral-200/80">
                        {{ $badge }}
                    </span>
                @endif
            </div>
            @if ($subtitle)
                <p class="text-xs text-neutral-500 font-medium">
                    {{ $subtitle }}
                </p>
            @endif
        </div>
    </div>

    @if (isset($actions) || $slot->isNotEmpty())
        <div class="flex items-center gap-3 shrink-0 flex-wrap">
            {{ $actions ?? $slot }}
        </div>
    @endif
</div>
