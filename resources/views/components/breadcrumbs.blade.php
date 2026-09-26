@props([
    'items' => [],
])

<nav class="flex items-center gap-1.5 text-xs text-neutral-500 font-medium" aria-label="Breadcrumb">
    <a href="{{ route('dashboard') }}" class="hover:text-neutral-900 transition-colors flex items-center">
        <svg class="w-3.5 h-3.5 text-neutral-400 hover:text-neutral-700" viewBox="0 0 24 24" fill="currentColor">
            <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
        </svg>
    </a>

    @foreach ($items as $item)
        <span class="text-neutral-300 select-none">/</span>
        @if (isset($item['url']) && $item['url'])
            <a href="{{ $item['url'] }}" class="hover:text-neutral-900 transition-colors">
                {{ $item['label'] }}
            </a>
        @else
            <span class="text-neutral-900 font-bold truncate max-w-xs">
                {{ $item['label'] }}
            </span>
        @endif
    @endforeach
</nav>
