@props([
    'active' => 'overview',
    'tabs' => [],
])

<div class="border-b border-neutral-200/80 bg-white rounded-t-3xl px-6 pt-3 flex items-center gap-2 overflow-x-auto">
    @foreach ($tabs as $tab)
        <button
            type="button"
            @click="activeTab = '{{ $tab['id'] }}'"
            class="px-4 py-3 border-b-2 font-bold text-xs flex items-center gap-2 transition-all cursor-pointer whitespace-nowrap"
            :class="activeTab === '{{ $tab['id'] }}'
                ? 'border-[#091315] text-[#091315]'
                : 'border-transparent text-neutral-500 hover:text-neutral-800 hover:border-neutral-300'"
        >
            @if (isset($tab['icon']))
                <span>{!! $tab['icon'] !!}</span>
            @endif
            <span>{{ $tab['label'] }}</span>
            @if (isset($tab['count']))
                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-extrabold"
                      :class="activeTab === '{{ $tab['id'] }}' ? 'bg-[#091315] text-[#D7FF53]' : 'bg-neutral-100 text-neutral-600'">
                    {{ $tab['count'] }}
                </span>
            @endif
        </button>
    @endforeach
</div>
