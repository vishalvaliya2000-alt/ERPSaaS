<div>
    <!-- Keyboard Shortcut Listener -->
    <div x-data="{
        init() {
            window.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                    e.preventDefault();
                    $wire.call('open');
                }
                if (e.key === 'Escape' && @js($isOpen)) {
                    $wire.call('close');
                }
            });
        }
    }"></div>

    <!-- Modal Backdrop -->
    @if($isOpen)
    <div class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-start justify-center pt-20 p-4 animate-in fade-in duration-150"
         @click.self="$wire.close()">
        <div class="bg-white rounded-3xl shadow-2xl border border-neutral-200/80 w-full max-w-2xl overflow-hidden flex flex-col max-h-[80vh]">
            
            <!-- Search Bar Header -->
            <div class="p-4 border-b border-neutral-100 flex items-center gap-3 bg-[#F5F6F8]">
                <svg class="w-5 h-5 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text"
                       wire:model.live.debounce.250ms="query"
                       autofocus
                       placeholder="Search Sales Orders, Invoices, Shipments, Clients... (Type min 2 chars)"
                       class="w-full bg-transparent border-none text-sm font-semibold text-neutral-900 placeholder-neutral-400 focus:outline-hidden">
                <div class="flex items-center gap-1.5 shrink-0">
                    <span wire:loading class="inline-block w-4 h-4 border-2 border-neutral-300 border-t-[#091315] rounded-full animate-spin"></span>
                    <button type="button" wire:click="close" class="text-xs font-mono font-bold px-2 py-0.5 rounded bg-white text-neutral-500 border border-neutral-200 hover:bg-neutral-100 cursor-pointer">
                        ESC
                    </button>
                </div>
            </div>

            <!-- Results Section -->
            <div class="overflow-y-auto p-3 space-y-4 max-h-[60vh]">
                @if(strlen(trim($query)) < 2)
                    <div class="p-2 space-y-3">
                        <div class="px-2 pt-1 flex items-center justify-between">
                            <span class="text-[10px] font-mono font-bold text-neutral-400 uppercase tracking-wider">⚡ Quick Jump Modules</span>
                            <span class="text-[10px] font-mono text-neutral-400">Press Esc to dismiss</span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
                            <a href="{{ route('dashboard') }}" wire:navigate wire:click="close" class="flex items-center gap-2.5 p-2.5 rounded-2xl hover:bg-[#F5F6F8] text-neutral-700 hover:text-neutral-900 font-semibold transition-all">
                                <span class="w-6 h-6 rounded-lg bg-[#091315] text-[#D7FF53] flex items-center justify-center text-xs">⚡</span>
                                <span>Dashboard</span>
                            </a>
                            <a href="{{ route('customers.index') }}" wire:navigate wire:click="close" class="flex items-center gap-2.5 p-2.5 rounded-2xl hover:bg-[#F5F6F8] text-neutral-700 hover:text-neutral-900 font-semibold transition-all">
                                <span class="w-6 h-6 rounded-lg bg-neutral-100 flex items-center justify-center text-xs">👥</span>
                                <span>Clients 360</span>
                            </a>
                            <a href="{{ route('pipeline.index') }}" wire:navigate wire:click="close" class="flex items-center gap-2.5 p-2.5 rounded-2xl hover:bg-[#F5F6F8] text-neutral-700 hover:text-neutral-900 font-semibold transition-all">
                                <span class="w-6 h-6 rounded-lg bg-neutral-100 flex items-center justify-center text-xs">📈</span>
                                <span>Leads / Pipeline</span>
                            </a>
                            <a href="{{ route('orders.index') }}" wire:navigate wire:click="close" class="flex items-center gap-2.5 p-2.5 rounded-2xl hover:bg-[#F5F6F8] text-neutral-700 hover:text-neutral-900 font-semibold transition-all">
                                <span class="w-6 h-6 rounded-lg bg-neutral-100 flex items-center justify-center text-xs">📦</span>
                                <span>Sales Orders</span>
                            </a>
                            <a href="{{ route('invoices.index') }}" wire:navigate wire:click="close" class="flex items-center gap-2.5 p-2.5 rounded-2xl hover:bg-[#F5F6F8] text-neutral-700 hover:text-neutral-900 font-semibold transition-all">
                                <span class="w-6 h-6 rounded-lg bg-neutral-100 flex items-center justify-center text-xs">📑</span>
                                <span>Invoices & AR</span>
                            </a>
                            <a href="{{ route('products.index') }}" wire:navigate wire:click="close" class="flex items-center gap-2.5 p-2.5 rounded-2xl hover:bg-[#F5F6F8] text-neutral-700 hover:text-neutral-900 font-semibold transition-all">
                                <span class="w-6 h-6 rounded-lg bg-neutral-100 flex items-center justify-center text-xs">🏷️</span>
                                <span>Products Catalog</span>
                            </a>
                            <a href="{{ route('shipments.index') }}" wire:navigate wire:click="close" class="flex items-center gap-2.5 p-2.5 rounded-2xl hover:bg-[#F5F6F8] text-neutral-700 hover:text-neutral-900 font-semibold transition-all">
                                <span class="w-6 h-6 rounded-lg bg-neutral-100 flex items-center justify-center text-xs">🚚</span>
                                <span>Shipments & LR</span>
                            </a>
                            <a href="{{ route('transporters.index') }}" wire:navigate wire:click="close" class="flex items-center gap-2.5 p-2.5 rounded-2xl hover:bg-[#F5F6F8] text-neutral-700 hover:text-neutral-900 font-semibold transition-all">
                                <span class="w-6 h-6 rounded-lg bg-neutral-100 flex items-center justify-center text-xs">🚛</span>
                                <span>Transporters</span>
                            </a>
                            <a href="{{ route('organization.settings') }}" wire:navigate wire:click="close" class="flex items-center gap-2.5 p-2.5 rounded-2xl hover:bg-[#F5F6F8] text-neutral-700 hover:text-neutral-900 font-semibold transition-all">
                                <span class="w-6 h-6 rounded-lg bg-neutral-100 flex items-center justify-center text-xs">⚙️</span>
                                <span>Settings</span>
                            </a>
                        </div>
                    </div>
                @elseif($totalResults === 0)
                    <div class="p-8 text-center text-neutral-400">
                        <span class="text-2xl block mb-2">🔍</span>
                        <p class="text-xs font-semibold">No records found matching "<span class="text-neutral-700 font-bold">{{ $query }}</span>".</p>
                    </div>
                @else
                    <!-- 1. Sales Orders -->
                    @if(count($results['orders']) > 0)
                    <div>
                        <div class="px-3 py-1 text-[10px] font-mono font-bold text-neutral-400 uppercase tracking-wider flex items-center gap-1.5">
                            <span>📦</span> Sales Orders ({{ count($results['orders']) }})
                        </div>
                        <div class="space-y-1 mt-1">
                            @foreach($results['orders'] as $order)
                            <a href="{{ route('orders.index') }}" wire:navigate wire:click="close"
                               class="flex items-center justify-between p-2.5 rounded-2xl hover:bg-[#F5F6F8] transition-colors group">
                                <div class="flex items-center gap-3">
                                    <span class="font-mono font-black text-xs text-neutral-900 group-hover:text-blue-600">{{ $order->order_number }}</span>
                                    <span class="text-xs font-medium text-neutral-600">{{ $order->customer?->company_name }}</span>
                                </div>
                                <div class="flex items-center gap-2 font-mono text-xs">
                                    <span class="font-bold text-neutral-900">{{ formatINR($order->total_amount) }}</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-neutral-100 text-neutral-700">{{ $order->status }}</span>
                                </div>
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- 2. Tax Invoices -->
                    @if(count($results['invoices']) > 0)
                    <div>
                        <div class="px-3 py-1 text-[10px] font-mono font-bold text-neutral-400 uppercase tracking-wider flex items-center gap-1.5">
                            <span>📑</span> Tax Invoices ({{ count($results['invoices']) }})
                        </div>
                        <div class="space-y-1 mt-1">
                            @foreach($results['invoices'] as $invoice)
                            <a href="{{ route('invoices.index') }}" wire:navigate wire:click="close"
                               class="flex items-center justify-between p-2.5 rounded-2xl hover:bg-[#F5F6F8] transition-colors group">
                                <div class="flex items-center gap-3">
                                    <span class="font-mono font-black text-xs text-neutral-900 group-hover:text-blue-600">{{ $invoice->invoice_number }}</span>
                                    <span class="text-xs font-medium text-neutral-600">{{ $invoice->customer?->company_name }}</span>
                                </div>
                                <div class="flex items-center gap-2 font-mono text-xs">
                                    <span class="font-bold text-neutral-900">{{ formatINR($invoice->total_amount) }}</span>
                                    @if($invoice->balance_due > 0)
                                        <span class="text-[10px] font-bold text-rose-600">Due: {{ formatINR($invoice->balance_due) }}</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700">PAID</span>
                                    @endif
                                </div>
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- 3. Consignments / Shipments -->
                    @if(count($results['shipments']) > 0)
                    <div>
                        <div class="px-3 py-1 text-[10px] font-mono font-bold text-neutral-400 uppercase tracking-wider flex items-center gap-1.5">
                            <span>🚚</span> Shipments & LR ({{ count($results['shipments']) }})
                        </div>
                        <div class="space-y-1 mt-1">
                            @foreach($results['shipments'] as $shipment)
                            <a href="{{ route('shipments.index') }}" wire:navigate wire:click="close"
                               class="flex items-center justify-between p-2.5 rounded-2xl hover:bg-[#F5F6F8] transition-colors group">
                                <div class="flex items-center gap-3">
                                    <span class="font-mono font-black text-xs text-neutral-900 group-hover:text-blue-600">LR {{ $shipment->lr_number ?: $shipment->shipment_number }}</span>
                                    <span class="text-xs font-medium text-neutral-600">{{ $shipment->transporter }} &bull; {{ $shipment->destination }}</span>
                                </div>
                                <div class="flex items-center gap-2 font-mono text-xs">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-neutral-100 text-neutral-700">{{ $shipment->status }}</span>
                                </div>
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- 4. Customers -->
                    @if(count($results['customers']) > 0)
                    <div>
                        <div class="px-3 py-1 text-[10px] font-mono font-bold text-neutral-400 uppercase tracking-wider flex items-center gap-1.5">
                            <span>👥</span> Clients ({{ count($results['customers']) }})
                        </div>
                        <div class="space-y-1 mt-1">
                            @foreach($results['customers'] as $customer)
                            <a href="{{ route('customers.index') }}" wire:navigate wire:click="close"
                               class="flex items-center justify-between p-2.5 rounded-2xl hover:bg-[#F5F6F8] transition-colors group">
                                <div class="flex items-center gap-3">
                                    <span class="font-bold text-xs text-neutral-900 group-hover:text-blue-600">{{ $customer->company_name }}</span>
                                    <span class="text-xs text-neutral-500">{{ $customer->primary_contact_person }} &bull; {{ $customer->city }}</span>
                                </div>
                                <span class="text-[10px] font-mono text-neutral-400">View Client &rarr;</span>
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif
                @endif
            </div>

            <!-- Footer -->
            <div class="p-3 border-t border-neutral-100 bg-[#F5F6F8] flex items-center justify-between text-[11px] text-neutral-500 font-mono">
                <span>Navigate with <kbd class="px-1 py-0.5 rounded bg-white border border-neutral-200">↑</kbd> <kbd class="px-1 py-0.5 rounded bg-white border border-neutral-200">↓</kbd></span>
                <span class="text-emerald-700 font-bold">⚡ TALL Stack Livewire Reactive Search</span>
            </div>
        </div>
    </div>
    @endif
</div>
