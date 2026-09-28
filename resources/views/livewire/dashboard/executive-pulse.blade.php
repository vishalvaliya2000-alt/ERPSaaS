<div class="space-y-4">
    <!-- ========================================================================= -->
    <!-- REACTIVE GLOBAL DASHBOARD FILTERS & QUICK DATE SELECTORS -->
    <!-- ========================================================================= -->
    <div class="bg-white p-4 lg:p-5 rounded-2xl sm:rounded-3xl border border-neutral-200/80 shadow-2xs space-y-3.5">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <!-- Filter Left: Preset Buttons -->
            <div class="flex items-center gap-1.5 flex-wrap overflow-x-auto touch-scroll max-w-full">
                <span class="text-xs font-bold text-neutral-500 mr-1.5 font-mono uppercase tracking-wider flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                    </svg>
                    Filter:
                </span>

                <button type="button" wire:click="setPeriod('this_month')"
                    class="px-3.5 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer {{ $period === 'this_month' ? 'bg-[#091315] text-[#D7FF53] shadow-xs' : 'bg-[#F5F6F8] text-neutral-700 hover:bg-neutral-200/70 border border-neutral-200/80' }}">
                    This Month
                </button>
                <button type="button" wire:click="setPeriod('last_month')"
                    class="px-3.5 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer {{ $period === 'last_month' ? 'bg-[#091315] text-[#D7FF53] shadow-xs' : 'bg-[#F5F6F8] text-neutral-700 hover:bg-neutral-200/70 border border-neutral-200/80' }}">
                    Last Month
                </button>
                <button type="button" wire:click="setPeriod('this_quarter')"
                    class="px-3.5 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer {{ $period === 'this_quarter' ? 'bg-[#091315] text-[#D7FF53] shadow-xs' : 'bg-[#F5F6F8] text-neutral-700 hover:bg-neutral-200/70 border border-neutral-200/80' }}">
                    This Quarter
                </button>
                <button type="button" wire:click="setPeriod('this_fy')"
                    class="px-3.5 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer {{ $period === 'this_fy' ? 'bg-[#091315] text-[#D7FF53] shadow-xs' : 'bg-[#F5F6F8] text-neutral-700 hover:bg-neutral-200/70 border border-neutral-200/80' }}">
                    This FY ({{ $analytics['filter_options']['current_fy'] ?? 'Current' }})
                </button>
                <button type="button" wire:click="setPeriod('last_fy')"
                    class="px-3.5 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer {{ $period === 'last_fy' ? 'bg-[#091315] text-[#D7FF53] shadow-xs' : 'bg-[#F5F6F8] text-neutral-700 hover:bg-neutral-200/70 border border-neutral-200/80' }}">
                    Last FY
                </button>
                <button type="button" wire:click="setPeriod('all')"
                    class="px-3.5 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer {{ $period === 'all' ? 'bg-[#091315] text-[#D7FF53] shadow-xs' : 'bg-[#F5F6F8] text-neutral-700 hover:bg-neutral-200/70 border border-neutral-200/80' }}">
                    All Time
                </button>
            </div>

            <!-- Filter Right: Dropdowns & Reset -->
            <div class="flex items-center gap-2.5 flex-wrap">
                <!-- Financial Year Selector -->
                <div class="relative">
                    <select wire:model.live="selectedFy"
                        class="px-3.5 py-1.5 pr-8 rounded-full text-xs font-bold bg-[#F5F6F8] text-neutral-800 border border-neutral-200/80 focus:ring-2 focus:ring-[#091315] focus:outline-hidden cursor-pointer">
                        @foreach($analytics['filter_options']['financial_years'] ?? [] as $fy)
                            <option value="{{ $fy['key'] }}">{{ $fy['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Customer Filter -->
                <div class="relative">
                    <select wire:model.live="selectedCustomer"
                        class="px-3.5 py-1.5 pr-8 rounded-full text-xs font-bold bg-[#F5F6F8] text-neutral-800 border border-neutral-200/80 focus:ring-2 focus:ring-[#091315] focus:outline-hidden max-w-[160px] truncate cursor-pointer">
                        <option value="all">All Clients</option>
                        @foreach($analytics['filter_options']['customers'] ?? [] as $c)
                            <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Product Filter -->
                <div class="relative">
                    <select wire:model.live="selectedProduct"
                        class="px-3.5 py-1.5 pr-8 rounded-full text-xs font-bold bg-[#F5F6F8] text-neutral-800 border border-neutral-200/80 focus:ring-2 focus:ring-[#091315] focus:outline-hidden max-w-[170px] truncate cursor-pointer">
                        <option value="all">All Products</option>
                        @foreach($analytics['filter_options']['products'] ?? [] as $p)
                            <option value="{{ $p['id'] }}">{{ $p['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Reset Button -->
                <button type="button" wire:click="resetFilters" title="Reset to default FY view"
                    class="px-3 py-1.5 rounded-full text-xs font-bold text-neutral-500 hover:text-neutral-900 hover:bg-neutral-100 transition-colors cursor-pointer flex items-center gap-1">
                    <span>↺</span>
                    <span>Reset</span>
                </button>

                <!-- Livewire Loading State Badge -->
                <span wire:loading class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-[#091315] text-[#D7FF53] shadow-xs">
                    <span class="w-2 h-2 rounded-full bg-[#D7FF53] animate-pulse"></span>
                    <span>Livewire Syncing...</span>
                </span>
            </div>
        </div>

        <!-- Active Filter Indicator Banner -->
        <div class="pt-2.5 border-t border-neutral-100 flex items-center justify-between text-xs text-neutral-500 font-mono flex-wrap gap-2">
            <div class="flex items-center gap-2">
                <span class="font-bold text-neutral-700">Active View:</span>
                <span class="px-2.5 py-0.5 rounded-md bg-[#F5F6F8] text-neutral-800 border border-neutral-200/60 font-semibold">
                    {{ $analytics['date_range']['label'] ?? 'All' }}
                </span>
                @if($selectedCustomer !== 'all')
                    <span class="px-2.5 py-0.5 rounded-md bg-blue-50 text-blue-800 border border-blue-200 font-semibold">Filtered Client</span>
                @endif
                @if($selectedProduct !== 'all')
                    <span class="px-2.5 py-0.5 rounded-md bg-purple-50 text-purple-800 border border-purple-200 font-semibold">Filtered Product</span>
                @endif
            </div>
            <div class="text-[11px] text-neutral-400">
                ⚡ TALL Stack Reactive Pulse · Multi-Tenant Isolated
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TOP KPI BENTO (6 Core Executive Metrics) -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4" wire:loading.class="opacity-60 transition-opacity">

        <!-- Card 1: Total Billed Sales -->
        <div class="bg-white p-5 rounded-[24px] border border-neutral-200/70 shadow-2xs flex flex-col justify-between space-y-3 hover:border-neutral-300 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider font-mono">Total Sales</span>
                <span class="w-8 h-8 rounded-xl bg-[#091315] text-[#D7FF53] flex items-center justify-center font-bold text-xs shadow-xs">
                    ₹
                </span>
            </div>
            <div>
                <span class="text-[11px] text-neutral-500 font-medium block">Period Revenue</span>
                <p class="text-xl lg:text-2xl font-black font-display text-neutral-900 tracking-tight">
                    {{ formatINR($analytics['kpis']['total_sales'] ?? 0) }}
                </p>
            </div>
            <div class="pt-2 border-t border-neutral-100 flex items-center justify-between text-[10px] font-mono text-neutral-500">
                <span>All-Time</span>
                <span class="font-bold text-neutral-800">{{ formatCompactINR($analytics['kpis']['all_time_sales'] ?? 0) }}</span>
            </div>
        </div>

        <!-- Card 2: Current Month Sales (with MoM %) -->
        <div class="bg-white p-5 rounded-[24px] border border-neutral-200/70 shadow-2xs flex flex-col justify-between space-y-3 hover:border-neutral-300 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider font-mono">This Month</span>
                @php
                    $mom = $analytics['kpis']['mom_growth_pct'] ?? 0;
                @endphp
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full font-mono {{ $mom >= 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                    {{ ($mom >= 0 ? '+' : '') . $mom . '% MoM' }}
                </span>
            </div>
            <div>
                <span class="text-[11px] text-neutral-500 font-medium block">{{ date('F Y') }}</span>
                <p class="text-xl lg:text-2xl font-black font-display text-neutral-900 tracking-tight">
                    {{ formatINR($analytics['kpis']['this_month_sales'] ?? 0) }}
                </p>
            </div>
            <div class="pt-2 border-t border-neutral-100 flex items-center justify-between text-[10px] font-mono text-neutral-500">
                <span>Last Month:</span>
                <span class="font-bold text-neutral-700">{{ formatCompactINR($analytics['kpis']['last_month_sales'] ?? 0) }}</span>
            </div>
        </div>

        <!-- Card 3: Outstanding AR (Receivables & Collections Pulse) -->
        <div class="bg-white p-5 rounded-[24px] border border-neutral-200/70 shadow-2xs flex flex-col justify-between space-y-3 hover:border-neutral-300 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider font-mono">Outstanding AR</span>
                <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200 font-mono">
                    Collections Pulse
                </span>
            </div>
            <div>
                <span class="text-[11px] text-neutral-500 font-medium block">Balance Due</span>
                @php
                    $outstanding = $analytics['kpis']['outstanding_ar'] ?? 0;
                @endphp
                <p class="text-xl lg:text-2xl font-black font-display tracking-tight {{ $outstanding > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                    {{ formatINR($outstanding) }}
                </p>
            </div>
            <div class="pt-2 border-t border-neutral-100 flex items-center justify-between text-[10px] font-mono">
                <span class="text-neutral-500">Overdue:</span>
                <span class="font-bold text-rose-600">{{ formatCompactINR($analytics['kpis']['overdue_ar'] ?? 0) }}</span>
            </div>
        </div>

        <!-- Card 4: Total Collections -->
        <div class="bg-white p-5 rounded-[24px] border border-neutral-200/70 shadow-2xs flex flex-col justify-between space-y-3 hover:border-neutral-300 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider font-mono">Collections</span>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                    {{ ($analytics['kpis']['collection_rate_pct'] ?? 0) }}% Rec
                </span>
            </div>
            <div>
                <span class="text-[11px] text-neutral-500 font-medium block">RTGS & Bank Credits</span>
                <p class="text-xl lg:text-2xl font-black font-display text-emerald-700 tracking-tight">
                    {{ formatINR($analytics['kpis']['total_collections'] ?? 0) }}
                </p>
            </div>
            <div class="pt-2 border-t border-neutral-100 flex items-center justify-between text-[10px] font-mono text-neutral-500">
                <span>All-Time Collected:</span>
                <span class="font-bold text-emerald-700">{{ formatCompactINR($analytics['kpis']['all_time_collections'] ?? 0) }}</span>
            </div>
        </div>

        <!-- Card 5: Active Sales Orders (Factory Velocity) -->
        <div class="bg-white p-5 rounded-[24px] border border-neutral-200/70 shadow-2xs flex flex-col justify-between space-y-3 hover:border-neutral-300 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider font-mono">Sales Orders</span>
                <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-[#F5F6F8] text-neutral-800 border border-neutral-200 font-mono">
                    Factory Velocity
                </span>
            </div>
            <div>
                <span class="text-[11px] text-neutral-500 font-medium block">
                    {{ $analytics['kpis']['active_orders_count'] ?? 0 }} Active Orders
                </span>
                <p class="text-xl lg:text-2xl font-black font-display text-neutral-900 tracking-tight">
                    {{ formatCompactINR($analytics['kpis']['active_orders_value'] ?? 0) }}
                </p>
            </div>
            <div class="pt-2 border-t border-neutral-100 flex items-center justify-between text-[10px] font-mono text-neutral-500">
                <span>Pending Qty:</span>
                <span class="font-bold text-neutral-800">{{ number_format($analytics['kpis']['pending_dispatch_qty'] ?? 0) }} KG</span>
            </div>
        </div>

        <!-- Card 6: Active Leads & Pipeline (Commercial) -->
        <div class="bg-white p-5 rounded-[24px] border border-neutral-200/70 shadow-2xs flex flex-col justify-between space-y-3 hover:border-neutral-300 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider font-mono">Active Deals</span>
                <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-[#F3FED4] text-[#091315] border border-[#D7FF53] font-mono">
                    Commercial
                </span>
            </div>
            <div>
                <span class="text-[11px] text-neutral-500 font-medium block">
                    {{ $analytics['kpis']['active_leads_count'] ?? 0 }} CRM Prospects
                </span>
                <p class="text-xl lg:text-2xl font-black font-display text-neutral-900 tracking-tight">
                    {{ formatCompactINR($analytics['kpis']['pipeline_value'] ?? 0) }}
                </p>
            </div>
            <div class="pt-2 border-t border-neutral-100 flex items-center justify-between text-[10px] font-mono text-neutral-500">
                <span>Samples in Trial:</span>
                <span class="font-bold text-neutral-800">{{ $analytics['kpis']['samples_in_trial_count'] ?? 0 }}</span>
            </div>
        </div>

    </div>
</div>
