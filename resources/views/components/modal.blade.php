@props([
    'name',
    'title',
    'subtitle' => null,
    'icon' => null,
    'maxWidth' => '2xl',
])

@php
    $maxWidthClass = match($maxWidth) {
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
        '3xl' => 'sm:max-w-3xl',
        '4xl' => 'sm:max-w-4xl',
        '5xl' => 'sm:max-w-5xl',
        default => 'sm:max-w-2xl',
    };
@endphp

<div
    x-cloak
    x-show="{{ $name }}"
    @keydown.escape.window="{{ $name }} = false"
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
>
    <!-- Dark Backdrop -->
    <div
        x-show="{{ $name }}"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="{{ $name }} = false"
        class="fixed inset-0 bg-black/60 backdrop-blur-md transition-opacity"
    ></div>

    <!-- Modal Dialog Positioner -->
    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-6">
        <div
            x-show="{{ $name }}"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            @click.stop
            class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all w-full {{ $maxWidthClass }} border border-neutral-200/80 my-8"
        >
            <!-- Signature Obsidian Header -->
            <div class="bg-[#091315] px-6 py-5 border-b border-neutral-800 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    @if ($icon)
                        <div class="w-10 h-10 rounded-2xl bg-[#112225] border border-neutral-700/60 text-[#D7FF53] flex items-center justify-center text-lg shrink-0">
                            {!! $icon !!}
                        </div>
                    @endif
                    <div>
                        <h3 class="font-extrabold text-base tracking-tight text-white font-display" id="modal-title">
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

            <!-- Modal Content Body -->
            <div class="p-6">
                {{ $slot }}
            </div>

            @if (isset($footer))
                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-[#F5F6F8]/80 border-t border-neutral-100 flex items-center justify-end gap-3">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>
