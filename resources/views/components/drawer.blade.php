@props([
    'name',
    'title',
    'subtitle' => null,
    'icon' => null,
    'maxWidth' => 'lg',
])

@php
    $maxWidthClass = match($maxWidth) {
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        default => 'max-w-lg',
    };
@endphp

<div
    x-cloak
    x-show="{{ $name }}"
    @keydown.escape.window="{{ $name }} = false"
    class="fixed inset-0 z-50 overflow-hidden"
    role="dialog"
    aria-modal="true"
>
    <!-- Dark Backdrop -->
    <div
        x-show="{{ $name }}"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="{{ $name }} = false"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
    ></div>

    <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
        <div
            x-show="{{ $name }}"
            x-transition:enter="transform transition ease-in-out duration-300 sm:duration-400"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-300 sm:duration-400"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            @click.stop
            class="w-screen {{ $maxWidthClass }} bg-white shadow-2xl flex flex-col border-l border-neutral-200/80"
        >
            <!-- Header -->
            <div class="bg-[#091315] px-6 py-5 border-b border-neutral-800 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    @if ($icon)
                        <div class="w-10 h-10 rounded-2xl bg-[#112225] border border-neutral-700/60 text-[#D7FF53] flex items-center justify-center text-lg shrink-0">
                            {!! $icon !!}
                        </div>
                    @endif
                    <div>
                        <h3 class="font-extrabold text-base tracking-tight text-white font-display">
                            {{ $title }}
                        </h3>
                        @if ($subtitle)
                            <p class="text-xs text-[#D7FF53] font-medium mt-0.5">
                                {{ $subtitle }}
                            </p>
                        @endif
                    </div>
                </div>

                <button
                    type="button"
                    @click="{{ $name }} = false"
                    class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-neutral-300 hover:text-white flex items-center justify-center transition-colors cursor-pointer text-sm"
                >
                    ✕
                </button>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                {{ $slot }}
            </div>

            @if (isset($footer))
                <!-- Footer -->
                <div class="px-6 py-4 bg-[#F5F6F8] border-t border-neutral-200/80 flex items-center justify-end gap-3">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>
