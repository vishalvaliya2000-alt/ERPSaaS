@props([
    'type' => 'card',
    'count' => 1,
])

<div {{ $attributes->merge(['class' => 'animate-pulse space-y-3']) }}>
    @if ($type === 'card')
        @for ($i = 0; $i < $count; $i++)
            <div class="bg-white p-6 rounded-3xl border border-neutral-200/80 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-neutral-200"></div>
                        <div class="space-y-1.5">
                            <div class="h-3 w-20 bg-neutral-200 rounded-full"></div>
                            <div class="h-4 w-32 bg-neutral-200 rounded-full"></div>
                        </div>
                    </div>
                    <div class="h-6 w-16 bg-neutral-200 rounded-full"></div>
                </div>
                <div class="h-8 w-40 bg-neutral-200 rounded-lg"></div>
                <div class="h-2 w-full bg-neutral-100 rounded-full"></div>
            </div>
        @endfor
    @elseif ($type === 'table')
        <div class="bg-white rounded-3xl border border-neutral-200/80 p-6 space-y-4">
            <div class="flex items-center justify-between pb-4 border-b border-neutral-100">
                <div class="h-8 w-64 bg-neutral-200 rounded-full"></div>
                <div class="h-8 w-32 bg-neutral-200 rounded-full"></div>
            </div>
            @for ($i = 0; $i < $count; $i++)
                <div class="flex items-center justify-between py-3 border-b border-neutral-100 last:border-0">
                    <div class="h-4 w-40 bg-neutral-200 rounded-full"></div>
                    <div class="h-4 w-28 bg-neutral-200 rounded-full"></div>
                    <div class="h-4 w-20 bg-neutral-200 rounded-full"></div>
                    <div class="h-6 w-16 bg-neutral-200 rounded-full"></div>
                </div>
            @endfor
        </div>
    @elseif ($type === 'line')
        @for ($i = 0; $i < $count; $i++)
            <div class="h-4 bg-neutral-200 rounded-full w-full"></div>
        @endfor
    @endif
</div>
