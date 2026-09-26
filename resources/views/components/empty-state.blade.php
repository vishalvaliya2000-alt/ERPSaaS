@props([
    'icon' => '📁',
    'title' => 'No records found',
    'description' => 'Get started by creating your first record in the system.',
    'actionText' => null,
    'actionUrl' => null,
    'actionClick' => null,
])

<div class="p-12 text-center flex flex-col items-center justify-center space-y-4 max-w-md mx-auto">
    <div class="w-16 h-16 rounded-3xl bg-[#F5F6F8] border border-neutral-200/80 flex items-center justify-center text-2xl shadow-2xs">
        {!! $icon !!}
    </div>

    <div class="space-y-1">
        <h3 class="font-extrabold text-base text-neutral-900 tracking-tight font-display">
            {{ $title }}
        </h3>
        <p class="text-xs text-neutral-500 max-w-sm">
            {{ $description }}
        </p>
    </div>

    @if ($actionText)
        <div class="pt-2">
            @if ($actionUrl)
                <a href="{{ $actionUrl }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-[#091315] hover:bg-black text-[#D7FF53] font-bold text-xs shadow-sm transition-all">
                    <span>+</span>
                    <span>{{ $actionText }}</span>
                </a>
            @elseif ($actionClick)
                <button type="button" @click="{{ $actionClick }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-[#091315] hover:bg-black text-[#D7FF53] font-bold text-xs shadow-sm transition-all cursor-pointer">
                    <span>+</span>
                    <span>{{ $actionText }}</span>
                </button>
            @endif
        </div>
    @endif

    @if ($slot->isNotEmpty())
        <div class="pt-2">
            {{ $slot }}
        </div>
    @endif
</div>
