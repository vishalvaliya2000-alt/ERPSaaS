@props([
    'badge' => null,
    'tag' => null,
    'title' => null,
    'value' => null,
    'subvalue' => null,
    'icon' => null,
    'trend' => null,
    'trendType' => 'up',
    'actionText' => null,
    'actionUrl' => null,
    'progress' => null,
    'progressColor' => 'bg-[#091315]',
])

<div {{ $attributes->merge([
    'class' => 'bg-white p-6 rounded-3xl border border-neutral-200/80 shadow-[0_2px_12px_rgba(0,0,0,0.02)] flex flex-col justify-between space-y-4 hover:shadow-md transition-all'
]) }}>
    <!-- Header -->
    <div class="flex items-start justify-between gap-3">
        <div class="flex items-center gap-3.5">
            @if ($icon)
                <div class="w-11 h-11 rounded-2xl bg-[#D7FF53] text-[#091315] flex items-center justify-center font-black shrink-0 shadow-2xs">
                    {!! $icon !!}
                </div>
            @endif
            <div>
                @if ($tag)
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider block font-mono">{{ $tag }}</span>
                @endif
                @if ($title)
                    <h3 class="font-extrabold text-sm text-neutral-900 font-display">{{ $title }}</h3>
                @endif
            </div>
        </div>

        @if ($badge)
            <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-[#F5F6F8] text-neutral-800 border border-neutral-200/80">
                {{ $badge }}
            </span>
        @endif
    </div>

    <!-- Main Value & Subvalue -->
    <div class="space-y-1">
        <div class="flex items-baseline justify-between">
            <h4 class="text-2xl font-black text-neutral-900 tracking-tight font-display">
                {{ $value }}
            </h4>
            @if ($trend)
                <span class="text-xs font-bold flex items-center gap-1 {{ $trendType === 'up' ? 'text-emerald-600' : ($trendType === 'down' ? 'text-rose-600' : 'text-neutral-500') }}">
                    @if ($trendType === 'up')
                        ↑
                    @elseif ($trendType === 'down')
                        ↓
                    @endif
                    {{ $trend }}
                </span>
            @endif
        </div>

        @if ($subvalue)
            <p class="text-xs text-neutral-500 font-medium">{{ $subvalue }}</p>
        @endif
    </div>

    <!-- Progress Bar (if provided) -->
    @if ($progress !== null)
        <div class="space-y-1">
            <div class="w-full bg-[#F5F6F8] h-2 rounded-full overflow-hidden border border-neutral-200/80">
                <div class="{{ $progressColor }} h-full rounded-full transition-all duration-700" style="width: {{ $progress }}%;"></div>
            </div>
        </div>
    @endif

    <!-- Optional Custom Slot or Bottom Action -->
    @if ($slot->isNotEmpty())
        <div>
            {{ $slot }}
        </div>
    @endif

    @if ($actionText && $actionUrl)
        <div class="pt-3 border-t border-neutral-100 flex items-center justify-end">
            <a href="{{ $actionUrl }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-[#F5F6F8] hover:bg-[#091315] hover:text-[#D7FF53] text-xs font-bold text-neutral-800 border border-neutral-200/80 transition-all">
                <span>{{ $actionText }}</span>
                <span>→</span>
            </a>
        </div>
    @endif
</div>
