@props([
    'headers' => [],
])

<div class="bg-white rounded-3xl border border-neutral-200/80 shadow-[0_2px_12px_rgba(0,0,0,0.02)] overflow-hidden">
    @if (isset($toolbar))
        <div class="p-4 border-b border-neutral-100 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            {{ $toolbar }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
            @if (count($headers) > 0 || isset($thead))
                <thead>
                    <tr class="bg-[#F5F6F8]/70 border-b border-neutral-200/80 text-[10px] font-bold text-neutral-500 uppercase tracking-wider font-mono">
                        @if (isset($thead))
                            {{ $thead }}
                        @else
                            @foreach ($headers as $header)
                                <th scope="col" class="py-3 px-4 font-bold">
                                    {{ $header }}
                                </th>
                            @endforeach
                        @endif
                    </tr>
                </thead>
            @endif

            <tbody class="divide-y divide-neutral-100 bg-white text-neutral-800">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @if (isset($pagination))
        <div class="px-6 py-4 border-t border-neutral-100 bg-[#F5F6F8]/50 flex items-center justify-between text-xs">
            {{ $pagination }}
        </div>
    @endif
</div>
