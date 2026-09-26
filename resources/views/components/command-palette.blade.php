<div
    x-cloak
    x-show="isCommandPaletteOpen"
    @keydown.escape.window="isCommandPaletteOpen = false"
    @keydown.window.prevent.ctrl.k="isCommandPaletteOpen = true; $nextTick(() => $refs.commandInput.focus())"
    @keydown.window.prevent.meta.k="isCommandPaletteOpen = true; $nextTick(() => $refs.commandInput.focus())"
    class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 md:p-20"
    role="dialog"
    aria-modal="true"
>
    <!-- Backdrop -->
    <div
        x-show="isCommandPaletteOpen"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="isCommandPaletteOpen = false"
        class="fixed inset-0 bg-black/60 backdrop-blur-md transition-opacity"
    ></div>

    <div
        x-show="isCommandPaletteOpen"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.stop
        class="relative mx-auto max-w-2xl transform divide-y divide-neutral-100 overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-black/5 transition-all border border-neutral-200/80"
    >
        <!-- Search Input -->
        <div class="relative flex items-center px-4">
            <svg class="pointer-events-none absolute left-5 h-5 w-5 text-neutral-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
            </svg>
            <input
                type="text"
                x-ref="commandInput"
                x-model="commandQuery"
                @input.debounce.300ms="executeSearch()"
                class="h-14 w-full border-0 bg-transparent pl-9 pr-12 text-neutral-900 placeholder:text-neutral-400 focus:ring-0 focus:outline-hidden text-sm font-medium"
                placeholder="Type a command or search customers, orders, invoices, products... (ESC to close)"
            >
            <kbd class="hidden sm:inline-block px-2 py-1 text-[10px] font-mono font-bold text-neutral-400 bg-neutral-100 rounded-md border border-neutral-200">ESC</kbd>
        </div>

        <!-- Quick Links / Results -->
        <div class="max-h-96 overflow-y-auto p-4 space-y-4">
            <!-- Quick Navigation Modules -->
            <div x-show="!commandQuery">
                <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider font-mono block px-3 mb-2">
                    Quick Jump Modules
                </span>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
                    <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2.5 p-2.5 rounded-2xl hover:bg-[#F5F6F8] text-neutral-700 hover:text-neutral-900 font-semibold transition-all">
                        <span class="w-6 h-6 rounded-lg bg-[#091315] text-[#D7FF53] flex items-center justify-center text-xs">⚡</span>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('customers.index') }}" wire:navigate class="flex items-center gap-2.5 p-2.5 rounded-2xl hover:bg-[#F5F6F8] text-neutral-700 hover:text-neutral-900 font-semibold transition-all">
                        <span class="w-6 h-6 rounded-lg bg-neutral-100 flex items-center justify-center text-xs">👥</span>
                        <span>Clients 360</span>
                    </a>
                    <a href="{{ route('pipeline.index') }}" wire:navigate class="flex items-center gap-2.5 p-2.5 rounded-2xl hover:bg-[#F5F6F8] text-neutral-700 hover:text-neutral-900 font-semibold transition-all">
                        <span class="w-6 h-6 rounded-lg bg-neutral-100 flex items-center justify-center text-xs">📈</span>
                        <span>Leads / Pipeline</span>
                    </a>
                    <a href="{{ route('orders.index') }}" wire:navigate class="flex items-center gap-2.5 p-2.5 rounded-2xl hover:bg-[#F5F6F8] text-neutral-700 hover:text-neutral-900 font-semibold transition-all">
                        <span class="w-6 h-6 rounded-lg bg-neutral-100 flex items-center justify-center text-xs">📦</span>
                        <span>Sales Orders</span>
                    </a>
                    <a href="{{ route('invoices.index') }}" wire:navigate class="flex items-center gap-2.5 p-2.5 rounded-2xl hover:bg-[#F5F6F8] text-neutral-700 hover:text-neutral-900 font-semibold transition-all">
                        <span class="w-6 h-6 rounded-lg bg-neutral-100 flex items-center justify-center text-xs">📑</span>
                        <span>Invoices & AR</span>
                    </a>
                    <a href="{{ route('products.index') }}" wire:navigate class="flex items-center gap-2.5 p-2.5 rounded-2xl hover:bg-[#F5F6F8] text-neutral-700 hover:text-neutral-900 font-semibold transition-all">
                        <span class="w-6 h-6 rounded-lg bg-neutral-100 flex items-center justify-center text-xs">🏷️</span>
                        <span>Products Catalog</span>
                    </a>
                    <a href="{{ route('shipments.index') }}" wire:navigate class="flex items-center gap-2.5 p-2.5 rounded-2xl hover:bg-[#F5F6F8] text-neutral-700 hover:text-neutral-900 font-semibold transition-all">
                        <span class="w-6 h-6 rounded-lg bg-neutral-100 flex items-center justify-center text-xs">🚚</span>
                        <span>Shipments & LR</span>
                    </a>
                    <a href="{{ route('transporters.index') }}" wire:navigate class="flex items-center gap-2.5 p-2.5 rounded-2xl hover:bg-[#F5F6F8] text-neutral-700 hover:text-neutral-900 font-semibold transition-all">
                        <span class="w-6 h-6 rounded-lg bg-neutral-100 flex items-center justify-center text-xs">🚛</span>
                        <span>Transporters</span>
                    </a>
                    <a href="{{ route('organization.settings') }}" wire:navigate class="flex items-center gap-2.5 p-2.5 rounded-2xl hover:bg-[#F5F6F8] text-neutral-700 hover:text-neutral-900 font-semibold transition-all">
                        <span class="w-6 h-6 rounded-lg bg-neutral-100 flex items-center justify-center text-xs">⚙️</span>
                        <span>Settings</span>
                    </a>
                </div>
            </div>

            <!-- Dynamic Search Results -->
            <div x-show="commandQuery && searchResults.length > 0" class="space-y-1">
                <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider font-mono block px-3 mb-2">
                    Search Results (<span x-text="searchResults.length"></span>)
                </span>
                <template x-for="item in searchResults" :key="item.url">
                    <a :href="item.url" class="flex items-center justify-between p-3 rounded-2xl hover:bg-[#F5F6F8] transition-colors group">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 rounded-xl bg-neutral-100 flex items-center justify-center text-xs font-bold text-neutral-600" x-text="item.icon || '📄'"></span>
                            <div>
                                <p class="text-xs font-bold text-neutral-900 group-hover:text-black" x-text="item.title"></p>
                                <p class="text-[11px] text-neutral-400" x-text="item.subtitle"></p>
                            </div>
                        </div>
                        <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-600 group-hover:bg-[#091315] group-hover:text-[#D7FF53] transition-colors" x-text="item.type"></span>
                    </a>
                </template>
            </div>

            <div x-show="commandQuery && searchResults.length === 0 && !isSearching" class="text-center py-8 text-neutral-400 text-xs">
                No matching records found for "<span class="font-bold text-neutral-700" x-text="commandQuery"></span>"
            </div>

            <div x-show="isSearching" class="text-center py-8 text-neutral-400 text-xs flex items-center justify-center gap-2">
                <span class="w-4 h-4 border-2 border-neutral-300 border-t-[#091315] rounded-full animate-spin"></span>
                <span>Searching ERP database...</span>
            </div>
        </div>

        <!-- Footer Shortcuts -->
        <div class="px-6 py-3 bg-[#F5F6F8]/80 text-[11px] text-neutral-500 flex items-center justify-between font-mono">
            <span>Press <kbd class="px-1.5 py-0.5 bg-white border border-neutral-300 rounded font-bold">↵</kbd> to select</span>
            <span><kbd class="px-1.5 py-0.5 bg-white border border-neutral-300 rounded font-bold">ESC</kbd> to dismiss</span>
        </div>
    </div>
</div>
