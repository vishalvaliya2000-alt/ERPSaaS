@props([
    'name',
    'title' => 'Confirm Action',
    'message' => 'Are you sure you want to proceed with this action? This cannot be undone.',
    'confirmText' => 'Confirm',
    'confirmAction' => null,
    'cancelText' => 'Cancel',
])

<div
    x-cloak
    x-show="{{ $name }}"
    @keydown.escape.window="{{ $name }} = false"
    class="fixed inset-0 z-50 overflow-y-auto"
>
    <div
        x-show="{{ $name }}"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        @click="{{ $name }} = false"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm"
    ></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div
            x-show="{{ $name }}"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            @click.stop
            class="relative w-full max-w-md bg-white rounded-3xl p-6 shadow-2xl border border-neutral-200/80 space-y-4"
        >
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl font-bold">
                ⚠️
            </div>

            <div class="space-y-1">
                <h3 class="font-extrabold text-base text-neutral-900 tracking-tight font-display">{{ $title }}</h3>
                <p class="text-xs text-neutral-500">{{ $message }}</p>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2.5">
                <button
                    type="button"
                    @click="{{ $name }} = false"
                    class="px-4 py-2 rounded-full bg-neutral-100 hover:bg-neutral-200 text-neutral-800 text-xs font-bold transition-all cursor-pointer"
                >
                    {{ $cancelText }}
                </button>
                <button
                    type="button"
                    @if($confirmAction) @click="{{ $confirmAction }}" @endif
                    class="px-4 py-2 rounded-full bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition-all cursor-pointer shadow-sm"
                >
                    {{ $confirmText }}
                </button>
            </div>
        </div>
    </div>
</div>
