@extends('layouts.app')

@section('title', "Today's Actions & Command Center")

@section('content')
<div class="space-y-6 pb-16" x-data="executiveCockpitApp(@js($analytics))" x-init="initCharts()">

    <!-- ========================================================================= -->
    <!-- 1. EXECUTIVE COCKPIT HERO BRIEFING (ERPSaaS Signature Hero Bento) -->
    <!-- ========================================================================= -->
    <div
        class="relative overflow-hidden rounded-[28px] bg-white p-6 lg:p-8 text-neutral-900 border border-neutral-200/70 shadow-[0_2px_12px_rgba(0,0,0,0.02)]">
        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="space-y-2.5">
                <div class="flex items-center gap-2.5 flex-wrap">
                    <span
                        class="px-3 py-1 rounded-full text-[10px] font-bold bg-[#F5F6F8] text-neutral-700 border border-neutral-200/80 flex items-center gap-1.5 shadow-2xs font-mono">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Executive War Room
                    </span>
                    <span class="text-xs text-neutral-300">·</span>
                    <span class="text-xs text-neutral-500 font-semibold">{{ date('l, d F Y') }}</span>
                </div>

                <h1 class="text-2xl lg:text-3xl font-extrabold tracking-tight text-neutral-900 font-display">
                    {{ $aiBriefing['greeting'] }}, {{ auth()->user()->name }}
                </h1>

                <p class="text-sm text-neutral-600 max-w-3xl leading-relaxed">
                    {{ $aiBriefing['summary'] }}
                </p>
            </div>

            <!-- Quick Action Hub (Pill-centric) -->
            <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
                <a href="{{ route('invoices.index') }}"
                    class="px-4 py-2.5 rounded-full bg-white hover:bg-neutral-50 text-neutral-800 font-bold text-xs border border-neutral-200/80 transition-all flex items-center gap-1.5 shadow-2xs">
                    <span>💰</span>
                    <span>Ledger</span>
                </a>
                <a href="{{ route('shipments.index') }}"
                    class="px-4 py-2.5 rounded-full bg-white hover:bg-neutral-50 text-neutral-800 font-bold text-xs border border-neutral-200/80 transition-all flex items-center gap-1.5 shadow-2xs">
                    <span>🚚</span>
                    <span>Dispatches</span>
                </a>
                <button @click="openDailyDigest()" type="button"
                    class="px-4 py-2.5 rounded-full bg-[#091315] hover:bg-black text-white font-bold text-xs transition-all flex items-center gap-1.5 cursor-pointer shadow-2xs active:scale-95"
                    title="7:00 PM Evening Executive Briefing & WhatsApp Digest">
                    <span>🌙</span>
                    <span>Daily Digest</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. TODAY'S PRIORITY DECISION QUEUE (Preserved Operational Feature) -->
    <!-- ========================================================================= -->
    <div class="bg-white rounded-3xl border border-neutral-200/80 shadow-2xs overflow-hidden">
        <!-- Section Header & Daily Completion Meter -->
        <div class="p-5 bg-[#091315] text-white flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2.5 flex-wrap">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#D7FF53] animate-pulse"></span>
                    <h2 class="font-extrabold text-base tracking-tight font-display">Today's Priority Decision Queue
                    </h2>
                    <span
                        class="px-2.5 py-0.5 rounded-full bg-neutral-800 text-[11px] font-bold text-[#D7FF53] border border-neutral-700 font-mono">
                        {{ count($decisionQueue) }} Pending Action
                    </span>

                    <form action="{{ route('dashboard.generate-actions') }}" method="POST" class="inline-block ml-1">
                        @csrf
                        <button type="submit"
                            title="Delete previous follow-ups and scan live invoices, shipments, and orders"
                            class="px-3 py-1 rounded-full bg-[#D7FF53]/20 hover:bg-[#D7FF53]/30 text-[#D7FF53] border border-[#D7FF53]/30 text-[11px] font-bold transition-all flex items-center gap-1.5 cursor-pointer shadow-2xs active:scale-95">
                            <span>⚡</span>
                            <span>Clean & Scan Today's Data</span>
                        </button>
                    </form>
                </div>
                <p class="text-xs text-neutral-300">
                    High-impact customer touchpoints, RTGS collections, and dispatch clearances requiring executive
                    decision today.
                </p>
            </div>

            <!-- Daily Progress Gauge -->
            <div class="w-full sm:w-56 space-y-1.5 shrink-0">
                <div class="flex items-center justify-between text-[11px] font-bold">
                    <span class="text-neutral-400 font-mono">Execution Progress</span>
                    <span
                        class="{{ $dailyProgressPct === 100 ? 'text-[#D7FF53]' : 'text-white' }} font-mono font-black">{{
                        $dailyProgressPct }}%</span>
                </div>
                <div class="w-full bg-neutral-800 h-2 rounded-full overflow-hidden border border-neutral-700">
                    <div class="bg-[#D7FF53] h-full rounded-full transition-all duration-500"
                        style="width: {{ $dailyProgressPct }}%"></div>
                </div>
            </div>
        </div>

        <!-- Decision Cards Stream -->
        <div class="p-5 divide-y divide-neutral-100 space-y-4">
            @forelse($decisionQueue as $item)
            <div
                class="pt-4 first:pt-0 flex flex-col lg:flex-row lg:items-center justify-between gap-4 group hover:bg-[#F5F6F8] p-3.5 rounded-2xl transition-colors">
                <!-- Left Context -->
                <div class="space-y-1.5 flex-1">
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <span class="font-extrabold text-sm text-neutral-900 font-display">{{ $item['customer_name']
                            }}</span>
                        @if($item['badge_color'] === 'rose')
                        <span
                            class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 uppercase tracking-wide font-mono">
                            {{ $item['badge'] }}
                        </span>
                        @else
                        <span
                            class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 uppercase tracking-wide font-mono">
                            {{ $item['badge'] }}
                        </span>
                        @endif

                        @if(!empty($item['amount']))
                        <span class="font-extrabold text-sm text-rose-600 ml-1 font-display">
                            {{ formatINR($item['amount']) }}
                        </span>
                        @endif
                    </div>

                    <p class="text-xs font-bold text-neutral-700">
                        {{ $item['title'] }}
                    </p>

                    <!-- Next Action Callout -->
                    <div
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#F5F6F8] text-neutral-700 text-xs font-medium border border-neutral-200/80">
                        <span class="text-amber-600 font-bold">👉 Next Step:</span>
                        <span>{{ $item['next_action'] }}</span>
                        <span class="text-[11px] font-bold text-rose-600 ml-2">({{ $item['due_date_text'] }})</span>
                    </div>
                </div>

                <!-- Right Micro-Actions (1-Tap Actions) -->
                <div class="flex items-center gap-2 shrink-0 flex-wrap">
                    <!-- 1-Tap WhatsApp -->
                    <button type="button" @click="openWhatsAppDrawer(@js($item))"
                        class="px-3.5 py-2 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200/80 transition-all flex items-center gap-1.5 cursor-pointer shadow-2xs">
                        <span>💬</span>
                        <span>WhatsApp Draft</span>
                    </button>

                    <!-- Call Client -->
                    @if(!empty($item['phone']))
                    <a href="tel:{{ $item['phone'] }}"
                        class="px-3.5 py-2 rounded-full text-xs font-bold bg-neutral-100 text-neutral-800 hover:bg-neutral-200 border border-neutral-200/80 transition-all flex items-center gap-1.5 shadow-2xs">
                        <span>📞</span>
                        <span>Call {{ $item['contact_person'] }}</span>
                    </a>
                    @endif

                    <!-- 1-Click Snooze Chips -->
                    <div class="flex items-center gap-1 bg-neutral-100 p-1 rounded-full border border-neutral-200/80">
                        <button type="button" @click="quickSnooze(@js($item), 1)" title="Snooze to Tomorrow"
                            class="px-2.5 py-1 rounded-full text-[10px] font-bold text-neutral-600 hover:bg-white hover:text-neutral-900 transition-all cursor-pointer shadow-2xs">
                            +1d
                        </button>
                        <button type="button" @click="quickSnooze(@js($item), 3)" title="Snooze to Monday / 3 Days"
                            class="px-2.5 py-1 rounded-full text-[10px] font-bold text-neutral-600 hover:bg-white hover:text-neutral-900 transition-all cursor-pointer shadow-2xs">
                            +3d
                        </button>
                    </div>

                    <!-- Complete & Schedule Next Action -->
                    <button type="button" @click="openCompleteModal(@js($item))"
                        class="px-4 py-2 rounded-full text-xs font-bold bg-[#091315] text-[#D7FF53] hover:bg-black transition-all flex items-center gap-1.5 shadow-2xs cursor-pointer">
                        <span>✓</span>
                        <span>Resolve</span>
                    </button>
                </div>
            </div>
            @empty
            <div class="p-10 text-center space-y-3">
                <div
                    class="w-16 h-16 mx-auto rounded-3xl bg-[#091315] text-[#D7FF53] flex items-center justify-center text-3xl shadow-lg border border-neutral-800">
                    ⚡
                </div>
                <h3 class="font-extrabold text-base text-neutral-900 font-display">All Decision Priorities Cleared!</h3>
                <p class="text-xs text-neutral-500 max-w-md mx-auto leading-relaxed">
                    Zero overdue items or unhandled check-ins pending for today. Any handled invoices and follow-ups
                    have been snoozed to their scheduled next dates.
                </p>
                <div class="pt-2">
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-[#F3FED4] text-[#091315] font-bold text-xs border border-[#D7FF53]">
                        <span>✨</span> Operations & Cash Flow 100% on schedule
                    </span>
                </div>
            </div>
            @endforelse
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 3 & 4. LIVEWIRE EXECUTIVE PULSE (TALL Stack Reactive Filters & Top KPIs) -->
    <!-- ========================================================================= -->
    <livewire:dashboard.executive-pulse />

    <!-- ========================================================================= -->
    <!-- 4. VISUAL BI ANALYTICS: ROW 1 (Monthly Performance & Yearly Growth) -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Chart 1: Monthly Sales Spline Chart (7 Columns) -->
        <div
            class="lg:col-span-7 bg-white p-6 rounded-3xl border border-neutral-200/80 shadow-2xs flex flex-col justify-between space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center gap-2.5">
                    <div
                        class="w-9 h-9 rounded-2xl bg-[#091315] text-[#D7FF53] font-bold flex items-center justify-center text-sm shadow-xs">
                        📈
                    </div>
                    <div>
                        <h3 class="font-extrabold text-sm text-neutral-900 font-display">Monthly Sales Performance</h3>
                        <p class="text-[11px] text-neutral-400 font-mono">Billed revenue across Indian Financial Year
                            (Apr–Mar)</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span
                        class="px-2.5 py-1 rounded-full bg-[#F5F6F8] text-neutral-800 font-mono font-bold text-[11px] border border-neutral-200/80"
                        x-text="analytics.monthly_sales.fy_label"></span>
                    <span
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-[#F3FED4] text-[#091315] text-[10px] font-bold border border-[#D7FF53]">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Current Month Active
                    </span>
                </div>
            </div>

            <!-- Canvas Container -->
            <div class="relative w-full h-72 sm:h-80">
                <canvas id="monthlySalesChart"></canvas>
            </div>

            <div
                class="pt-3 border-t border-neutral-100 flex items-center justify-between text-xs text-neutral-500 font-mono">
                <span>Peak Sales Month: <b class="text-neutral-900" x-text="getPeakMonthName()"></b></span>
                <span class="text-emerald-700 font-bold"
                    x-text="'Total FY Sales: ' + formatINR(analytics.kpis.total_sales)"></span>
            </div>
        </div>

        <!-- Chart 2: Yearly Historical Sales Trend (5 Columns) -->
        <div
            class="lg:col-span-5 bg-white p-6 rounded-3xl border border-neutral-200/80 shadow-2xs flex flex-col justify-between space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div
                        class="w-9 h-9 rounded-2xl bg-[#F5F6F8] text-[#091315] font-bold flex items-center justify-center text-sm border border-neutral-200/80 shadow-2xs">
                        📊
                    </div>
                    <div>
                        <h3 class="font-extrabold text-sm text-neutral-900 font-display">Yearly Sales Trend</h3>
                        <p class="text-[11px] text-neutral-400 font-mono">Turnover history by Financial Year</p>
                    </div>
                </div>
                <span class="text-[10px] font-bold text-neutral-400 font-mono uppercase">Multi-Year</span>
            </div>

            <!-- Canvas Container -->
            <div class="relative w-full h-72 sm:h-80">
                <canvas id="yearlySalesChart"></canvas>
            </div>

            <div
                class="pt-3 border-t border-neutral-100 flex items-center justify-between text-xs text-neutral-500 font-mono">
                <span>All-Time Invoiced:</span>
                <span class="font-black text-neutral-900" x-text="formatINR(analytics.kpis.all_time_sales)"></span>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- 5. VISUAL BI ANALYTICS: ROW 2 (Product Quantity & Top Revenue Products) -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Chart 3: Product-wise Monthly Sales Quantity in KG (7 Columns) -->
        <div
            class="lg:col-span-7 bg-white p-6 rounded-3xl border border-neutral-200/80 shadow-2xs flex flex-col justify-between space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <div
                        class="w-9 h-9 rounded-2xl bg-[#091315] text-[#D7FF53] font-bold flex items-center justify-center text-sm shadow-xs">
                        🧅
                    </div>
                    <div>
                        <h3 class="font-extrabold text-sm text-neutral-900 font-display">Product-wise Monthly Sales
                            Quantity</h3>
                        <p class="text-[11px] text-neutral-400 font-mono">Monthly dispatched volume in Kilograms (KG)
                        </p>
                    </div>
                </div>

                <!-- Product Switcher Dropdown for this Chart -->
                <div class="flex items-center gap-2">
                    <select x-model="selectedProductChartMode" @change="renderProductQtyChart()"
                        class="px-3 py-1.5 rounded-full text-xs font-bold bg-[#F5F6F8] text-neutral-800 border border-neutral-200/80 focus:ring-2 focus:ring-[#091315] focus:outline-hidden max-w-[200px] truncate cursor-pointer">
                        <option value="multi">Top Products Breakdown</option>
                        <option value="total">Total Aggregate Volume</option>
                        <template x-for="p in analytics.product_monthly_qty.products_list" :key="p.id">
                            <option :value="p.id" x-text="p.name"></option>
                        </template>
                    </select>
                </div>
            </div>

            <!-- Canvas Container -->
            <div class="relative w-full h-72 sm:h-80">
                <canvas id="productQtyChart"></canvas>
            </div>

            <div
                class="pt-3 border-t border-neutral-100 flex items-center justify-between text-xs text-neutral-500 font-mono">
                <span>Active Manufacturing SKUs: <b class="text-neutral-900"
                        x-text="analytics.product_monthly_qty.products_list.length"></b></span>
                <span class="text-neutral-800 font-bold">Standard UOM: Kilograms (KG)</span>
            </div>
        </div>

        <!-- Chart 4: Top Products by Revenue (5 Columns) -->
        <div
            class="lg:col-span-5 bg-white p-6 rounded-3xl border border-neutral-200/80 shadow-2xs flex flex-col justify-between space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div
                        class="w-9 h-9 rounded-2xl bg-[#F5F6F8] text-[#091315] font-bold flex items-center justify-center text-sm border border-neutral-200/80 shadow-2xs">
                        🏆
                    </div>
                    <div>
                        <h3 class="font-extrabold text-sm text-neutral-900 font-display">Top Products by Revenue</h3>
                        <p class="text-[11px] text-neutral-400 font-mono">Highest revenue contributing product lines</p>
                    </div>
                </div>
                <a href="{{ route('products.index') }}"
                    class="px-3 py-1 rounded-full bg-[#F5F6F8] hover:bg-neutral-100 text-neutral-800 text-[11px] font-bold border border-neutral-200/80 transition-all">
                    Product Master →
                </a>
            </div>

            <!-- Top Products Ranked List -->
            <div class="space-y-3.5 flex-1 pt-1">
                <template x-for="(prod, idx) in analytics.top_products.items" :key="prod.product_id">
                    <div
                        class="p-3.5 bg-[#F5F6F8] rounded-2xl border border-neutral-200/60 space-y-2 hover:bg-white hover:shadow-2xs transition-all">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 min-w-0 flex-1">
                                <span
                                    class="w-5 h-5 rounded-full bg-[#091315] text-[#D7FF53] text-[10px] font-black flex items-center justify-center shrink-0 font-mono"
                                    x-text="idx + 1"></span>
                                <span class="font-bold text-xs text-neutral-900 truncate font-display"
                                    x-text="prod.product_name"></span>
                            </div>
                            <span class="font-extrabold text-xs text-neutral-900 ml-2 font-display"
                                x-text="formatINR(prod.revenue)"></span>
                        </div>

                        <!-- Progress Share Bar -->
                        <div class="w-full bg-neutral-200/80 h-1.5 rounded-full overflow-hidden">
                            <div class="bg-[#091315] h-full rounded-full transition-all duration-500"
                                :style="'width: ' + prod.share_pct + '%'"></div>
                        </div>

                        <div class="flex items-center justify-between text-[10px] text-neutral-500 font-mono">
                            <span>Sold: <b class="text-neutral-800"
                                    x-text="formatQty(prod.quantity, prod.uom)"></b></span>
                            <span class="font-bold text-neutral-700" x-text="prod.share_pct + '% of sales'"></span>
                        </div>
                    </div>
                </template>

                <template x-if="!analytics.top_products.items || analytics.top_products.items.length === 0">
                    <div class="py-12 text-center text-xs text-neutral-400 font-mono">
                        No product sales recorded in the selected period.
                    </div>
                </template>
            </div>

            <div
                class="pt-3 border-t border-neutral-100 flex items-center justify-between text-xs text-neutral-500 font-mono">
                <span>Total Product Turnover:</span>
                <span class="font-black text-neutral-900"
                    x-text="formatINR(analytics.top_products.total_revenue)"></span>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- 6. VISUAL BI ANALYTICS: ROW 3 (Collections vs Outstanding & Aging) -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Chart 5: Cash Flow Health: Billed vs Collected vs Outstanding (7 Columns) -->
        <div
            class="lg:col-span-7 bg-white p-6 rounded-3xl border border-neutral-200/80 shadow-2xs flex flex-col justify-between space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center gap-2.5">
                    <div
                        class="w-9 h-9 rounded-2xl bg-[#091315] text-[#D7FF53] font-bold flex items-center justify-center text-sm shadow-xs">
                        ⚖️
                    </div>
                    <div>
                        <h3 class="font-extrabold text-sm text-neutral-900 font-display">Collections vs Outstanding Cash Flow</h3>
                        <p class="text-[11px] text-neutral-400 font-mono">Monthly Billed Amount vs Bank Receipts vs Balance</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 text-[11px] font-mono">
                    <span class="flex items-center gap-1 text-neutral-800">
                        <span class="w-2.5 h-2.5 rounded-sm bg-[#091315]"></span> Invoiced
                    </span>
                    <span class="flex items-center gap-1 text-emerald-700">
                        <span class="w-2.5 h-2.5 rounded-sm bg-[#10B981]"></span> Collected
                    </span>
                    <span class="flex items-center gap-1 text-rose-600">
                        <span class="w-2.5 h-2.5 rounded-sm bg-[#EF4444]"></span> Balance
                    </span>
                </div>
            </div>

            <!-- Canvas Container -->
            <div class="relative w-full h-72 sm:h-80">
                <canvas id="cashFlowChart"></canvas>
            </div>

            <div
                class="pt-3 border-t border-neutral-100 flex items-center justify-between text-xs text-neutral-500 font-mono">
                <span>Recovery Rate: <b class="text-emerald-700"
                        x-text="analytics.kpis.collection_rate_pct + '%'"></b></span>
                <a href="{{ route('invoices.index') }}"
                    class="text-xs font-bold text-neutral-800 hover:text-neutral-900">
                    Open AR Ledger →
                </a>
            </div>
        </div>

        <!-- Receivables Aging Analysis Buckets (5 Columns) -->
        <div
            class="lg:col-span-5 bg-white p-6 rounded-3xl border border-neutral-200/80 shadow-2xs flex flex-col justify-between space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div
                        class="w-9 h-9 rounded-2xl bg-[#F5F6F8] text-[#091315] font-bold flex items-center justify-center text-sm border border-neutral-200/80 shadow-2xs">
                        ⏳
                    </div>
                    <div>
                        <h3 class="font-extrabold text-sm text-neutral-900 font-display">Receivables Aging Analysis</h3>
                        <p class="text-[11px] text-neutral-400 font-mono">Overdue invoice buckets & risk breakdown</p>
                    </div>
                </div>
                <span
                    class="text-xs font-bold font-mono px-2.5 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200">
                    <span x-text="formatCompactINR(analytics.receivables_aging.total_outstanding)"></span> Total Due
                </span>
            </div>

            <!-- 5 Aging Buckets -->
            <div class="space-y-3 flex-1 pt-1">
                <template x-for="bucket in analytics.receivables_aging.buckets" :key="bucket.key">
                    <a :href="'{{ route('invoices.index') }}?aging=' + bucket.key"
                        class="p-3.5 bg-[#F5F6F8] hover:bg-white rounded-2xl border border-neutral-200/60 hover:border-neutral-300 hover:shadow-2xs transition-all block space-y-2 group">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold font-mono border"
                                    :class="bucket.badge_class" x-text="bucket.label"></span>
                                <span class="text-[10px] text-neutral-400 font-mono"
                                    x-text="bucket.count + ' Invoices'"></span>
                            </div>
                            <span
                                class="font-extrabold text-xs font-display text-neutral-900 group-hover:text-rose-600 transition-colors"
                                x-text="formatINR(bucket.amount)"></span>
                        </div>

                        <!-- Aging Share Bar -->
                        <div class="w-full bg-neutral-200/80 h-1.5 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500"
                                :style="'width: ' + bucket.share_pct + '%; background-color: ' + bucket.bar_color">
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-[10px] text-neutral-400 font-mono">
                            <span x-text="bucket.subtext"></span>
                            <span class="font-bold text-neutral-700" x-text="bucket.share_pct + '% of balance'"></span>
                        </div>
                    </a>
                </template>
            </div>

            <div
                class="pt-3 border-t border-neutral-100 flex items-center justify-between text-xs text-neutral-500 font-mono">
                <span>Pending Unpaid Invoices: <b class="text-neutral-900"
                        x-text="analytics.receivables_aging.total_invoices_count"></b></span>
                <a href="{{ route('invoices.index') }}"
                    class="text-xs font-bold text-neutral-800 hover:text-neutral-900">
                    Filter Unpaid →
                </a>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- 7. VISUAL BI ANALYTICS: ROW 4 (Top Customers & Sales Pipeline Funnel) -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Top Counterparties by Sales (7 Columns) -->
        <div
            class="lg:col-span-7 bg-white p-6 rounded-3xl border border-neutral-200/80 shadow-2xs flex flex-col justify-between space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div
                        class="w-9 h-9 rounded-2xl bg-[#091315] text-[#D7FF53] font-bold flex items-center justify-center text-sm shadow-xs">
                        👥
                    </div>
                    <div>
                        <h3 class="font-extrabold text-sm text-neutral-900 font-display">Top Customers by Sales Turnover
                        </h3>
                        <p class="text-[11px] text-neutral-400 font-mono">Leading commercial buyers, billing & recovery
                            stats</p>
                    </div>
                </div>
                <a href="{{ route('customers.index') }}"
                    class="px-3.5 py-1.5 rounded-full bg-[#F5F6F8] hover:bg-neutral-100 text-xs font-bold text-neutral-900 border border-neutral-200/80 transition-all">
                    View All Clients →
                </a>
            </div>

            <!-- Customer Ranked Cards -->
            <div class="space-y-3 flex-1 pt-1">
                <template x-for="(cust, idx) in analytics.top_customers.items" :key="cust.customer_id">
                    <a :href="'/customers/' + cust.customer_id"
                        class="p-4 bg-[#F5F6F8] hover:bg-white rounded-2xl border border-neutral-200/60 hover:border-neutral-300 hover:shadow-2xs transition-all block space-y-2.5 group">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 min-w-0">
                                <span
                                    class="w-6 h-6 rounded-full bg-[#091315] text-[#D7FF53] text-xs font-black flex items-center justify-center shrink-0 font-mono"
                                    x-text="idx + 1"></span>
                                <div>
                                    <h4 class="font-bold text-xs text-neutral-900 group-hover:text-neutral-700 font-display"
                                        x-text="cust.customer_name"></h4>
                                    <p class="text-[10px] text-neutral-400 font-mono"
                                        x-text="cust.city || 'Counterparty'"></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="font-extrabold text-sm text-neutral-900 font-display block"
                                    x-text="formatINR(cust.billed_amount)"></span>
                                <span class="text-[10px] font-bold text-emerald-700 font-mono"
                                    x-text="cust.recovery_pct + '% Recovered'"></span>
                            </div>
                        </div>

                        <!-- Progress Billed Share -->
                        <div class="w-full bg-neutral-200/80 h-1.5 rounded-full overflow-hidden">
                            <div class="bg-[#091315] h-full rounded-full transition-all duration-500"
                                :style="'width: ' + cust.share_pct + '%'"></div>
                        </div>

                        <div class="flex items-center justify-between text-[11px] font-mono">
                            <span class="text-neutral-500">Collected: <b class="text-emerald-700"
                                    x-text="formatINR(cust.received_amount)"></b></span>
                            <span class="text-neutral-500">Balance: <b
                                    :class="cust.balance_due > 0 ? 'text-rose-600' : 'text-neutral-800'"
                                    x-text="formatINR(cust.balance_due)"></b></span>
                        </div>
                    </a>
                </template>

                <template x-if="!analytics.top_customers.items || analytics.top_customers.items.length === 0">
                    <div class="py-12 text-center text-xs text-neutral-400 font-mono">
                        No customer billing data in this period.
                    </div>
                </template>
            </div>

            <div
                class="pt-3 border-t border-neutral-100 flex items-center justify-between text-xs text-neutral-500 font-mono">
                <span>Total Counterparty Billed:</span>
                <span class="font-black text-neutral-900"
                    x-text="formatINR(analytics.top_customers.total_billed)"></span>
            </div>
        </div>

        <!-- 5-Stage Sales Pipeline Funnel (5 Columns) -->
        <div
            class="lg:col-span-5 bg-white p-6 rounded-3xl border border-neutral-200/80 shadow-2xs flex flex-col justify-between space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div
                        class="w-9 h-9 rounded-2xl bg-[#F5F6F8] text-[#091315] font-bold flex items-center justify-center text-sm border border-neutral-200/80 shadow-2xs">
                        🎯
                    </div>
                    <div>
                        <h3 class="font-extrabold text-sm text-neutral-900 font-display">Sales Pipeline Funnel</h3>
                        <p class="text-[11px] text-neutral-400 font-mono">Lead to Client conversion velocity</p>
                    </div>
                </div>
                <a href="{{ route('pipeline.index') }}"
                    class="px-3 py-1 rounded-full bg-[#F5F6F8] hover:bg-neutral-100 text-neutral-800 text-[11px] font-bold border border-neutral-200/80 transition-all">
                    CRM Pipeline →
                </a>
            </div>

            <!-- Funnel Stages Ribbon -->
            <div class="space-y-2.5 flex-1 pt-1">
                <template x-for="(stage, sIdx) in analytics.pipeline_funnel.stages" :key="stage.stage_key">
                    <a :href="'{{ route('pipeline.index') }}?stage=' + stage.stage_key"
                        class="p-3 bg-[#F5F6F8] hover:bg-white rounded-2xl border border-neutral-200/60 hover:border-neutral-300 hover:shadow-2xs transition-all block space-y-1.5 group">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span
                                    class="w-5 h-5 rounded-full text-[10px] font-bold flex items-center justify-center text-white shrink-0 font-mono"
                                    :style="'background-color: ' + stage.color" x-text="sIdx + 1"></span>
                                <span
                                    class="font-bold text-xs text-neutral-900 group-hover:text-neutral-700 font-display"
                                    x-text="stage.name"></span>
                            </div>
                            <div class="text-right">
                                <span class="font-black text-xs text-neutral-900 font-display block"
                                    x-text="formatCompactINR(stage.value)"></span>
                                <span class="text-[10px] text-neutral-400 font-mono"
                                    x-text="stage.count + ' Deals'"></span>
                            </div>
                        </div>

                        <!-- Funnel Progression Bar -->
                        <div class="w-full bg-neutral-200/80 h-1.5 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500"
                                :style="'width: ' + (stage.share_pct || 5) + '%; background-color: ' + stage.color">
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-[10px] text-neutral-400 font-mono">
                            <span x-text="stage.description"></span>
                            <span class="font-bold text-neutral-700"
                                x-text="stage.conversion_rate + '% Conversion'"></span>
                        </div>
                    </a>
                </template>
            </div>

            <div
                class="pt-3 border-t border-neutral-100 flex items-center justify-between text-xs text-neutral-500 font-mono">
                <span>Active Pipeline Value:</span>
                <span class="font-black text-neutral-900"
                    x-text="formatINR(analytics.pipeline_funnel.total_pipeline_value)"></span>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- 8. OPERATIONAL ORDER & LOGISTICS STATUS SUMMARY -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Orders Operational Status -->
        <div class="bg-white p-5 rounded-3xl border border-neutral-200/80 shadow-2xs space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-base">📦</span>
                    <h3 class="font-extrabold text-sm text-neutral-900 font-display">Sales Orders Fulfillment Status
                    </h3>
                </div>
                <span class="text-xs font-bold font-mono text-neutral-500"
                    x-text="analytics.operational_status.total_orders + ' Total Orders'"></span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 pt-1">
                <div class="p-3 bg-[#F5F6F8] rounded-2xl border border-neutral-200/60 text-center space-y-0.5">
                    <span class="text-[10px] font-bold text-neutral-400 font-mono block">Pending</span>
                    <p class="text-lg font-black font-display text-neutral-800"
                        x-text="analytics.operational_status.orders.PENDING"></p>
                </div>
                <div class="p-3 bg-amber-50/70 rounded-2xl border border-amber-200/60 text-center space-y-0.5">
                    <span class="text-[10px] font-bold text-amber-600 font-mono block">In Production</span>
                    <p class="text-lg font-black font-display text-amber-800"
                        x-text="analytics.operational_status.orders.IN_PRODUCTION"></p>
                </div>
                <div class="p-3 bg-blue-50/70 rounded-2xl border border-blue-200/60 text-center space-y-0.5">
                    <span class="text-[10px] font-bold text-blue-600 font-mono block">Ready to Ship</span>
                    <p class="text-lg font-black font-display text-blue-800"
                        x-text="analytics.operational_status.orders.READY"></p>
                </div>
                <div class="p-3 bg-emerald-50/70 rounded-2xl border border-emerald-200/60 text-center space-y-0.5">
                    <span class="text-[10px] font-bold text-emerald-600 font-mono block">Completed</span>
                    <p class="text-lg font-black font-display text-emerald-800"
                        x-text="analytics.operational_status.orders.COMPLETED"></p>
                </div>
            </div>
        </div>

        <!-- Shipments Operational Status -->
        <div class="bg-white p-5 rounded-3xl border border-neutral-200/80 shadow-2xs space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-base">🚚</span>
                    <h3 class="font-extrabold text-sm text-neutral-900 font-display">Commercial Shipments Logistics</h3>
                </div>
                <span class="text-xs font-bold font-mono text-neutral-500"
                    x-text="analytics.operational_status.total_shipments + ' Consignments'"></span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 pt-1">
                <div class="p-3 bg-[#F5F6F8] rounded-2xl border border-neutral-200/60 text-center space-y-0.5">
                    <span class="text-[10px] font-bold text-neutral-400 font-mono block">Pending Dispatch</span>
                    <p class="text-lg font-black font-display text-neutral-800"
                        x-text="analytics.operational_status.shipments.PENDING_DISPATCH"></p>
                </div>
                <div class="p-3 bg-blue-50/70 rounded-2xl border border-blue-200/60 text-center space-y-0.5">
                    <span class="text-[10px] font-bold text-blue-600 font-mono block">In Transit</span>
                    <p class="text-lg font-black font-display text-blue-800"
                        x-text="analytics.operational_status.shipments.IN_TRANSIT"></p>
                </div>
                <div class="p-3 bg-emerald-50/70 rounded-2xl border border-emerald-200/60 text-center space-y-0.5">
                    <span class="text-[10px] font-bold text-emerald-600 font-mono block">Delivered</span>
                    <p class="text-lg font-black font-display text-emerald-800"
                        x-text="analytics.operational_status.shipments.DELIVERED"></p>
                </div>
                <div class="p-3 bg-rose-50/70 rounded-2xl border border-rose-200/60 text-center space-y-0.5">
                    <span class="text-[10px] font-bold text-rose-600 font-mono block">Delayed</span>
                    <p class="text-lg font-black font-display text-rose-800"
                        x-text="analytics.operational_status.shipments.DELAYED"></p>
                </div>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- 9. DOCUMENT INTELLIGENCE METRICS -->
    <!-- ========================================================================= -->
    <div class="space-y-6">
        <!-- Top Row: Document Intelligence Metric Pills / Bento Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Metric 1: POs This Month -->
            <div
                class="bg-white p-5 rounded-3xl border border-neutral-200/80 shadow-2xs space-y-2 hover:shadow-xs transition-all">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-neutral-500 uppercase tracking-wider font-mono">POs Received</span>
                    <span
                        class="w-8 h-8 rounded-xl bg-[#091315] text-[#D7FF53] flex items-center justify-center text-xs font-black shadow-2xs">PO</span>
                </div>
                <div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-extrabold font-display text-neutral-900">{{
                            $documentMetrics['pos_this_month_count'] }}</span>
                        <span class="text-xs text-neutral-400 font-mono">this month</span>
                    </div>
                    <p class="text-xs font-black text-emerald-700 font-display mt-0.5">
                        {{ formatINR($documentMetrics['pos_this_month_value']) }}
                    </p>
                </div>
                <div
                    class="pt-2 border-t border-neutral-100 flex items-center justify-between text-[11px] text-neutral-500 font-mono">
                    <span>{{ $documentMetrics['total_pos'] }} Total POs</span>
                    <a href="{{ route('orders.index') }}"
                        class="text-[#091315] hover:underline font-bold">View Orders →</a>
                </div>
            </div>

            <!-- Metric 2: Invoices Issued This Month -->
            <div
                class="bg-white p-5 rounded-3xl border border-neutral-200/80 shadow-2xs space-y-2 hover:shadow-xs transition-all">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-neutral-500 uppercase tracking-wider font-mono">Invoices Issued</span>
                    <span
                        class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center text-xs font-black">INV</span>
                </div>
                <div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-extrabold font-display text-neutral-900">{{
                            $documentMetrics['invoices_this_month_count'] }}</span>
                        <span class="text-xs text-neutral-400 font-mono">this month</span>
                    </div>
                    <p class="text-xs font-black text-neutral-900 font-display mt-0.5">
                        {{ formatINR($documentMetrics['invoices_this_month_value']) }}
                    </p>
                </div>
                <div
                    class="pt-2 border-t border-neutral-100 flex items-center justify-between text-[11px] text-neutral-500 font-mono">
                    <span>{{ $documentMetrics['total_invoices'] }} Total Invoices</span>
                    <a href="{{ route('invoices.index') }}"
                        class="text-[#091315] hover:underline font-bold">View Invoices →</a>
                </div>
            </div>

            <!-- Metric 3: Pending Invoicing / Dispatch -->
            <div
                class="bg-white p-5 rounded-3xl border border-neutral-200/80 shadow-2xs space-y-2 hover:shadow-xs transition-all">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-neutral-500 uppercase tracking-wider font-mono">Uninvoiced Dispatches</span>
                    <span
                        class="w-8 h-8 rounded-xl bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center text-xs font-black">LR</span>
                </div>
                <div>
                    <div class="flex items-baseline gap-2">
                        <span
                            class="text-2xl font-extrabold font-display {{ $documentMetrics['dispatched_not_invoiced_count'] > 0 ? 'text-amber-600' : 'text-neutral-900' }}">
                            {{ $documentMetrics['dispatched_not_invoiced_count'] }}
                        </span>
                        <span class="text-xs text-neutral-400 font-mono">awaiting bill</span>
                    </div>
                    <p class="text-xs text-neutral-500 font-mono mt-0.5">
                        {{ $documentMetrics['unshipped_pos_count'] }} POs pending shipment
                    </p>
                </div>
                <div
                    class="pt-2 border-t border-neutral-100 flex items-center justify-between text-[11px] text-neutral-500 font-mono">
                    <span>{{ $documentMetrics['total_lrs'] }} Consignments</span>
                    <a href="{{ route('shipments.index') }}"
                        class="text-[#091315] hover:underline font-bold">View Shipments →</a>
                </div>
            </div>

            <!-- Metric 4: Receivables & Overdue Invoices -->
            <div
                class="bg-white p-5 rounded-3xl border border-neutral-200/80 shadow-2xs space-y-2 hover:shadow-xs transition-all">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-neutral-500 uppercase tracking-wider font-mono">Overdue Documents</span>
                    <span
                        class="w-8 h-8 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 flex items-center justify-center text-xs font-black">⚠️</span>
                </div>
                <div>
                    <div class="flex items-baseline gap-2">
                        <span
                            class="text-2xl font-extrabold font-display {{ $documentMetrics['overdue_count'] > 0 ? 'text-rose-600' : 'text-neutral-900' }}">
                            {{ $documentMetrics['overdue_count'] }}
                        </span>
                        <span class="text-xs text-neutral-400 font-mono">overdue</span>
                    </div>
                    <p class="text-xs text-neutral-500 font-mono mt-0.5">
                        {{ $documentMetrics['pending_payment_count'] }} pending collection
                    </p>
                </div>
                <div
                    class="pt-2 border-t border-neutral-100 flex items-center justify-between text-[11px] text-neutral-500 font-mono">
                    <span>{{ $documentMetrics['delivered_pending_payment_count'] }} delivered & unpaid</span>
                    <a href="{{ route('invoices.index') }}" class="text-[#091315] hover:underline font-bold">Ledger
                        →</a>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 10. TWO-COLUMN RADAR: FACTORY PRODUCTION vs IN-TRANSIT LOGISTICS -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Column A: 🏭 Live Product-wise Dispatch Radar -->
        <div class="bg-white p-6 rounded-3xl border border-neutral-200/80 shadow-2xs space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div
                        class="w-9 h-9 rounded-2xl bg-[#F5F6F8] text-[#091315] font-bold flex items-center justify-center text-sm border border-neutral-200/80 shadow-2xs">
                        📦
                    </div>
                    <div>
                        <h3 class="font-extrabold text-sm text-neutral-900 font-display">Live Factory Dispatch Radar
                        </h3>
                        <p class="text-[11px] text-neutral-400 font-medium">Dehydrated Garlic & Onion fulfillment
                            progress</p>
                    </div>
                </div>
                <a href="{{ route('orders.index') }}"
                    class="px-3.5 py-1.5 rounded-full bg-[#F5F6F8] hover:bg-neutral-100 text-xs font-bold text-neutral-900 border border-neutral-200/80 transition-all">
                    View Orders →
                </a>
            </div>

            <div class="space-y-4 pt-1">
                @forelse($productDispatches as $prod)
                <div class="p-4 bg-[#F5F6F8] rounded-2xl border border-neutral-200/60 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="font-extrabold text-xs text-neutral-900 font-display">{{ $prod['product_name']
                            }}</span>
                        <span
                            class="text-xs font-bold font-mono {{ $prod['balance_qty'] > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                            {{ $prod['progress_pct'] }}% Completed
                        </span>
                    </div>

                    <!-- Progress Bar -->
                    <div class="w-full bg-neutral-200/80 h-2 rounded-full overflow-hidden">
                        <div class="bg-[#091315] h-full rounded-full transition-all duration-500"
                            style="width: {{ $prod['progress_pct'] }}%"></div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-neutral-500 font-medium font-mono">
                        <span>Ordered: <b class="text-neutral-900">{{ formatQuantity($prod['ordered_qty'], 'KG')
                                }}</b></span>
                        <span>Shipped: <b class="text-neutral-900">{{ formatQuantity($prod['shipped_qty'], 'KG')
                                }}</b></span>
                        <span>Pending: <b
                                class="{{ $prod['balance_qty'] > 0 ? 'text-rose-600' : 'text-neutral-900' }}">{{
                                formatQuantity($prod['balance_qty'], 'KG') }}</b></span>
                    </div>
                </div>
                @empty
                <p class="p-6 text-center text-xs text-neutral-400 font-mono">No active production or dispatch orders
                    yet.</p>
                @endforelse
            </div>
        </div>

        <!-- Column B: 🚚 Active Transporter & Consignment Feed -->
        <div class="bg-white p-6 rounded-3xl border border-neutral-200/80 shadow-2xs space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div
                        class="w-9 h-9 rounded-2xl bg-[#F5F6F8] text-[#091315] font-bold flex items-center justify-center text-sm border border-neutral-200/80 shadow-2xs">
                        🚚
                    </div>
                    <div>
                        <h3 class="font-extrabold text-sm text-neutral-900 font-display">Active Shipments In-Transit
                        </h3>
                        <p class="text-[11px] text-neutral-400 font-medium">Live road & freight consignments in transit
                        </p>
                    </div>
                </div>
                <a href="{{ route('shipments.index') }}"
                    class="px-3.5 py-1.5 rounded-full bg-[#F5F6F8] hover:bg-neutral-100 text-xs font-bold text-neutral-900 border border-neutral-200/80 transition-all">
                    All Shipments →
                </a>
            </div>

            <div class="space-y-3 pt-1">
                @forelse($activeShipments as $shp)
                <div
                    class="p-4 bg-[#F5F6F8] rounded-2xl border border-neutral-200/60 flex items-center justify-between gap-3 hover:bg-white hover:shadow-2xs transition-all">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="font-extrabold text-xs text-neutral-900 font-display">{{ $shp['title']
                                }}</span>
                            <span
                                class="px-2.5 py-0.5 rounded-full bg-neutral-200 text-neutral-800 font-bold text-[9px] uppercase font-mono">
                                {{ $shp['transporter'] }}
                            </span>
                        </div>
                        <p class="text-[11px] text-neutral-600 font-medium">
                            {{ $shp['qty_text'] }} · Destination: <b class="text-neutral-900">{{ $shp['destination']
                                }}</b>
                        </p>
                    </div>

                    <div class="flex items-center gap-1.5 shrink-0">
                        @if(!empty($shp['tracking_url']))
                        <button type="button"
                            @click="openLiveTracking({{ $shp['shipment_id'] }}, '{{ $shp['lr_number'] }}', '{{ addslashes($shp['transporter']) }}')"
                            class="px-3 py-1.5 rounded-full bg-neutral-100 hover:bg-neutral-200 border border-neutral-300/80 text-neutral-800 font-bold text-[11px] transition-colors flex items-center gap-1 shadow-2xs cursor-pointer active:scale-95"
                            title="View live shipment tracking details inside ERP">
                            <span>📍</span>
                            <span>Track</span>
                        </button>
                        @endif
                        <button type="button" @click="openWhatsAppDrawer(@js($shp))"
                            class="px-3 py-1.5 rounded-full bg-white border border-neutral-200/80 text-neutral-800 hover:bg-neutral-50 font-bold text-[11px] transition-colors cursor-pointer shadow-2xs">
                            💬 Send LR
                        </button>
                        <form action="{{ route('shipments.status', $shp['shipment_id']) }}" method="POST"
                            class="inline-block"
                            onsubmit="return confirm('Confirm LR {{ $shp['lr_number'] }} delivered to destination?');">
                            @csrf
                            <input type="hidden" name="status" value="DELIVERED">
                            <button type="submit"
                                class="px-3 py-1.5 rounded-full bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-800 font-bold text-[11px] transition-colors cursor-pointer shadow-2xs"
                                title="Mark Consignment as Delivered">
                                ✓ Delivered
                            </button>
                        </form>
                    </div>
                </div>
                @empty
                <p class="p-6 text-center text-xs text-neutral-400 font-mono">No active consignments currently
                    in-transit.</p>
                @endforelse
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- 11. MODALS & DRAWERS (Preserved 100%) -->
    <!-- ========================================================================= -->

    <!-- Multi-Template WhatsApp Drawer -->
    <template x-teleport="body">
        <div x-show="isWhatsAppOpen" x-cloak
            class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-4">
            <div @click.outside="isWhatsAppOpen = false"
                class="bg-white rounded-3xl shadow-2xl border border-neutral-200/80 w-full max-w-lg overflow-hidden text-xs">
                <div class="p-5 bg-[#091315] text-white flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="text-lg">💬</span>
                        <div>
                            <h3 class="font-extrabold text-sm font-display">Send WhatsApp Message</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono" x-text="whatsAppData.recipientName"></p>
                        </div>
                    </div>
                    <button @click="isWhatsAppOpen = false"
                        class="text-neutral-400 hover:text-white text-base cursor-pointer">✕</button>
                </div>

                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-neutral-700 mb-1">Recipient Phone Number</label>
                        <input type="text" x-model="whatsAppData.recipientPhone"
                            class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl font-mono text-xs focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700 mb-1">Message Content (Pre-formatted
                            Business Draft)</label>
                        <textarea x-model="whatsAppData.message" rows="5"
                            class="w-full p-3.5 border border-neutral-300 rounded-2xl text-xs leading-relaxed font-sans focus:ring-2 focus:ring-[#091315] focus:outline-hidden"></textarea>
                    </div>

                    <div class="pt-3 border-t border-neutral-100 flex justify-end gap-2">
                        <button type="button" @click="isWhatsAppOpen = false"
                            class="px-4 py-2 font-bold text-neutral-600 hover:bg-neutral-100 rounded-full cursor-pointer">Cancel</button>
                        <button type="button" @click="launchWhatsApp()"
                            class="px-5 py-2 font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs cursor-pointer flex items-center gap-1.5 border border-[#c8f043]">
                            <span>🚀 Launch WhatsApp Web/App</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Complete / Log Result Modal -->
    <template x-teleport="body">
        <div x-show="isCompleteOpen" x-cloak
            class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-4">
            <div @click.outside="isCompleteOpen = false"
                class="bg-white rounded-3xl shadow-2xl border border-neutral-200/80 w-full max-w-lg overflow-hidden text-xs">
                <div class="p-5 bg-[#091315] text-white flex items-center justify-between">
                    <div>
                        <h3 class="font-extrabold text-sm font-display">Resolve Action & Schedule Next Step</h3>
                        <p class="text-[11px] text-[#D7FF53] font-mono" x-text="taskData.customerName"></p>
                    </div>
                    <button @click="isCompleteOpen = false"
                        class="text-neutral-400 hover:text-white cursor-pointer">✕</button>
                </div>

                <form :action="'/tasks/' + taskData.id + '/complete'" method="POST" class="p-5 space-y-4">
                    @csrf
                    <div class="p-3.5 bg-[#F5F6F8] rounded-2xl border border-neutral-200/80 text-xs text-neutral-800">
                        <span class="font-bold block mb-0.5 text-neutral-900">Task Context:</span>
                        <span x-text="taskData.reason"></span>
                    </div>

                    <div x-data="{ outcomeText: '' }">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold text-neutral-700">
                                Outcome / Client Remarks <span class="text-rose-500">*</span>
                            </label>
                            <span class="text-[10px] text-neutral-400 font-medium">Auto-fill preset:</span>
                        </div>

                        <!-- Quick Remark Chips -->
                        <div class="flex flex-wrap gap-1.5 mb-2">
                            <button type="button"
                                @click="outcomeText = 'Will dispatch as per client requirement / buyer will update upcoming shipment schedule'"
                                class="px-3 py-1 rounded-full text-[10px] font-bold bg-[#F5F6F8] text-neutral-800 hover:bg-[#F3FED4] hover:text-[#091315] border border-neutral-200/80 transition-all cursor-pointer">
                                📦 Dispatch per client requirement
                            </button>
                            <button type="button"
                                @click="outcomeText = 'Spoke with buyer - will connect from their end when ready for loading'"
                                class="px-3 py-1 rounded-full text-[10px] font-bold bg-[#F5F6F8] text-neutral-800 hover:bg-[#F3FED4] hover:text-[#091315] border border-neutral-200/80 transition-all cursor-pointer">
                                📞 Buyer will connect for loading
                            </button>
                            <button type="button"
                                @click="outcomeText = 'Payment confirmed - RTGS reference / UTR will be shared shortly'"
                                class="px-3 py-1 rounded-full text-[10px] font-bold bg-[#F5F6F8] text-neutral-800 hover:bg-[#F3FED4] hover:text-[#091315] border border-neutral-200/80 transition-all cursor-pointer">
                                💰 RTGS payment confirmed
                            </button>
                        </div>

                        <textarea name="outcome_notes" rows="3" x-model="outcomeText"
                            placeholder="e.g. Spoke with buyer. Will dispatch as per client requirement when their godown is ready."
                            class="w-full px-3.5 py-2 border border-neutral-300 rounded-2xl text-xs leading-relaxed focus:ring-2 focus:ring-[#091315] focus:outline-hidden"
                            required></textarea>
                    </div>

                    <div class="pt-3 border-t border-neutral-100" x-data="{ sched: true }">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <span
                                    class="text-xs font-bold text-neutral-900 uppercase tracking-wider block font-mono">Schedule
                                    Next Action</span>
                                <span class="text-[10px] text-neutral-400">Every active record maintains a continuous
                                    next step.</span>
                            </div>
                            <input type="checkbox" name="needs_next_action" value="1" x-model="sched"
                                class="rounded text-[#091315] focus:ring-[#091315]">
                        </div>

                        <div x-show="sched" class="space-y-3 p-4 bg-[#F5F6F8] rounded-2xl border border-neutral-200/80">
                            <div>
                                <label class="block font-bold text-neutral-700 mb-1">Next Action</label>
                                <input type="text" name="next_action"
                                    placeholder="e.g. Verify bank RTGS credit and send official receipt"
                                    class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-neutral-700 mb-1">Due Date</label>
                                    <input type="date" name="next_due_date"
                                        value="{{ date('Y-m-d', strtotime('+3 days')) }}"
                                        class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                                </div>
                                <div>
                                    <label class="block font-bold text-neutral-700 mb-1">Priority</label>
                                    <select name="priority"
                                        class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                                        <option value="URGENT">🔴 Urgent</option>
                                        <option value="HIGH" selected>🟠 High</option>
                                        <option value="MEDIUM">🟡 Medium</option>
                                        <option value="LOW">⚪ Low</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-neutral-100 flex justify-end gap-2">
                        <button type="button" @click="isCompleteOpen = false"
                            class="px-4 py-2 font-bold text-neutral-600 hover:bg-neutral-100 rounded-full cursor-pointer">Cancel</button>
                        <button type="submit"
                            class="px-5 py-2.5 font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs cursor-pointer border border-[#c8f043]">
                            Complete & Update Timeline
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- Evening Daily Digest Modal -->
    <template x-teleport="body">
        <div x-show="isDigestOpen" x-cloak
            class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-4">
            <div @click.outside="isDigestOpen = false"
                class="bg-white rounded-3xl shadow-2xl border border-neutral-200/80 w-full max-w-2xl overflow-hidden text-xs max-h-[90vh] flex flex-col">
                <!-- Header -->
                <div class="p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div
                            class="w-9 h-9 rounded-2xl bg-[#D7FF53]/20 text-[#D7FF53] flex items-center justify-center text-lg font-bold border border-[#D7FF53]/30">
                            🌙
                        </div>
                        <div>
                            <h3
                                class="font-extrabold text-sm tracking-tight text-white flex items-center gap-2 font-display">
                                <span>Evening Executive Digest</span>
                                <span
                                    class="text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-neutral-800 text-[#D7FF53] border border-neutral-700 font-mono">7:00
                                    PM Pulse</span>
                            </h3>
                            <p class="text-[11px] text-neutral-400 font-mono"
                                x-text="digestData.date_formatted || '{{ date('l, d F Y') }}'"></p>
                        </div>
                    </div>
                    <button @click="isDigestOpen = false"
                        class="text-neutral-400 hover:text-white p-1 rounded-lg text-sm cursor-pointer">✕</button>
                </div>

                <!-- Content Body (Scrollable) -->
                <div class="p-6 space-y-5 overflow-y-auto flex-1">
                    <!-- Loading State -->
                    <div x-show="isDigestLoading" class="py-12 text-center space-y-3">
                        <div
                            class="w-8 h-8 border-3 border-[#091315] border-t-transparent rounded-full animate-spin mx-auto">
                        </div>
                        <p class="text-xs font-bold text-neutral-500">Compiling today's factory dispatches, collections,
                            and contract bookings...</p>
                    </div>

                    <div x-show="!isDigestLoading" class="space-y-5">
                        <!-- 4 Quick Stats Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div class="p-4 bg-[#F5F6F8] rounded-2xl border border-neutral-200/60">
                                <span
                                    class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider block font-mono">Dispatched
                                    Today</span>
                                <p class="text-base font-extrabold text-neutral-900 mt-0.5 font-display"
                                    x-text="(digestData.total_dispatched_kg || 0).toLocaleString() + ' KG'"></p>
                                <span class="text-[10px] text-neutral-500 font-medium"
                                    x-text="(digestData.shipments_count || 0) + ' trucks loaded'"></span>
                            </div>
                            <div class="p-4 bg-[#F5F6F8] rounded-2xl border border-neutral-200/60">
                                <span
                                    class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider block font-mono">RTGS
                                    Collections</span>
                                <p class="text-base font-extrabold text-emerald-700 mt-0.5 font-display"
                                    x-text="'₹' + (digestData.total_collected || 0).toLocaleString()"></p>
                                <span class="text-[10px] text-emerald-600 font-medium"
                                    x-text="(digestData.receipts_count || 0) + ' payments credited'"></span>
                            </div>
                            <div class="p-4 bg-[#F5F6F8] rounded-2xl border border-neutral-200/60">
                                <span
                                    class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider block font-mono">New
                                    Orders Booked</span>
                                <p class="text-base font-extrabold text-neutral-900 mt-0.5 font-display"
                                    x-text="'₹' + (digestData.total_order_value || 0).toLocaleString()"></p>
                                <span class="text-[10px] text-neutral-500 font-medium"
                                    x-text="(digestData.total_order_kg || 0).toLocaleString() + ' KG'"></span>
                            </div>
                            <div class="p-4 bg-[#F5F6F8] rounded-2xl border border-neutral-200/60">
                                <span
                                    class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider block font-mono">Tomorrow
                                    Due</span>
                                <p class="text-base font-extrabold text-rose-600 mt-0.5 font-display"
                                    x-text="'₹' + (digestData.total_due_tomorrow || 0).toLocaleString()"></p>
                                <span class="text-[10px] text-rose-500 font-medium">Overdue radar</span>
                            </div>
                        </div>

                        <!-- Formatted WhatsApp-Ready Preview -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-xs font-bold text-neutral-800 uppercase tracking-wider font-mono">Executive
                                    WhatsApp Format</span>
                                <button type="button"
                                    @click="navigator.clipboard.writeText(digestData.whatsapp_text); window.showToast('Executive Digest copied to clipboard!', 'success')"
                                    class="text-[11px] font-bold text-neutral-700 hover:text-neutral-900 cursor-pointer flex items-center gap-1">
                                    📋 Copy Text
                                </button>
                            </div>
                            <pre class="p-4 bg-[#091315] text-[#D7FF53] rounded-2xl text-[11px] font-mono whitespace-pre-wrap leading-relaxed border border-neutral-800 max-h-60 overflow-y-auto"
                                x-text="digestData.whatsapp_text"></pre>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="p-4 bg-[#F5F6F8] border-t border-neutral-200/80 flex items-center justify-between shrink-0">
                    <span class="text-[11px] text-neutral-500 font-mono">Auto-sent daily at 7:00 PM via scheduler</span>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="isDigestOpen = false"
                            class="px-4 py-2 font-bold text-neutral-600 hover:bg-neutral-200 rounded-full cursor-pointer">Close</button>
                        <button type="button" @click="shareDigestWhatsApp()"
                            class="px-5 py-2 font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs cursor-pointer flex items-center gap-1.5 border border-[#c8f043]">
                            <span>📲 Share on WhatsApp</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

<!-- ========================================================================= -->
<!-- 12. ALPINE.JS & CHART.JS CONTROLLER SCRIPT -->
<!-- ========================================================================= -->
<script>
    function executiveCockpitApp(initialAnalytics) {
        return {
            analytics: initialAnalytics || {},
            activePeriod: initialAnalytics?.date_range?.period || 'this_fy',
            selectedFy: initialAnalytics?.filter_options?.current_fy || '2026-27',
            selectedCustomer: 'all',
            selectedProduct: 'all',
            selectedProductChartMode: 'multi',
            isLoading: false,

            // Modal States
            isWhatsAppOpen: false,
            isCompleteOpen: false,
            isDigestOpen: false,
            isDigestLoading: false,
            digestData: {},
            whatsAppData: {
                recipientName: '',
                recipientPhone: '',
                message: ''
            },
            taskData: {
                id: '',
                customerName: '',
                reason: ''
            },

            // Chart instances dictionary
            charts: {},

            initCharts() {
                window.addEventListener('analytics-filter-changed', (e) => {
                    if (e.detail?.period) this.activePeriod = e.detail.period;
                    if (e.detail?.fy) this.selectedFy = e.detail.fy;
                    if (e.detail?.customer_id) this.selectedCustomer = e.detail.customer_id;
                    if (e.detail?.product_id) this.selectedProduct = e.detail.product_id;
                    this.fetchFilteredAnalytics();
                });
                this.$nextTick(() => {
                    this.renderMonthlySalesChart();
                    this.renderYearlySalesChart();
                    this.renderProductQtyChart();
                    this.renderCashFlowChart();
                });
            },

            // Formatting Helpers
            formatINR(val) {
                if (val === null || val === undefined || isNaN(val)) return '₹0';
                return '₹' + Number(val).toLocaleString('en-IN', { maximumFractionDigits: 0 });
            },

            formatCompactINR(val) {
                if (!val || isNaN(val)) return '₹0';
                const num = Math.abs(Number(val));
                if (num >= 10000000) return '₹' + (num / 10000000).toFixed(2) + ' Cr';
                if (num >= 100000) return '₹' + (num / 100000).toFixed(2) + ' L';
                if (num >= 1000) return '₹' + (num / 1000).toFixed(1) + ' k';
                return '₹' + num.toLocaleString('en-IN');
            },

            formatQty(val, uom = 'KG') {
                if (val === null || val === undefined || isNaN(val)) return '0 ' + uom;
                return Number(val).toLocaleString('en-IN', { maximumFractionDigits: 1 }) + ' ' + uom;
            },

            getPeakMonthName() {
                if (!this.analytics.monthly_sales || !this.analytics.monthly_sales.values) return '—';
                const vals = this.analytics.monthly_sales.values;
                const maxVal = Math.max(...vals);
                if (maxVal <= 0) return 'N/A';
                const maxIdx = vals.indexOf(maxVal);
                return this.analytics.monthly_sales.labels[maxIdx] + ' (' + this.formatCompactINR(maxVal) + ')';
            },

            // Preset & Filter Controls
            async setPeriod(period) {
                this.activePeriod = period;
                await this.fetchFilteredAnalytics();
            },

            async onFilterChange() {
                await this.fetchFilteredAnalytics();
            },

            async resetFilters() {
                this.activePeriod = 'this_fy';
                this.selectedFy = this.analytics?.filter_options?.current_fy || '2026-27';
                this.selectedCustomer = 'all';
                this.selectedProduct = 'all';
                this.selectedProductChartMode = 'multi';
                await this.fetchFilteredAnalytics();
            },

            async fetchFilteredAnalytics() {
                this.isLoading = true;
                try {
                    const params = new URLSearchParams({
                        period: this.activePeriod,
                        fy: this.selectedFy,
                        customer_id: this.selectedCustomer,
                        product_id: this.selectedProduct,
                    });

                    const res = await fetch(`{{ route('dashboard.analytics') }}?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!res.ok) throw new Error('Network error');
                    const data = await res.json();
                    this.analytics = data;

                    // Re-render all charts with fresh data
                    this.$nextTick(() => {
                        this.renderMonthlySalesChart();
                        this.renderYearlySalesChart();
                        this.renderProductQtyChart();
                        this.renderCashFlowChart();
                    });
                } catch (e) {
                    console.error('Failed to fetch dashboard analytics:', e);
                } finally {
                    this.isLoading = false;
                }
            },

            // 1. Monthly Sales Chart (Spline Line Chart with Current Month Glow)
            renderMonthlySalesChart() {
                const canvas = document.getElementById('monthlySalesChart');
                if (!canvas || !window.Chart) return;

                if (this.charts.monthly) {
                    this.charts.monthly.destroy();
                }

                const ctx = canvas.getContext('2d');
                const data = this.analytics.monthly_sales || {};
                const labels = data.labels || [];
                const values = data.values || [];
                const currIdx = data.current_month_index ?? -1;

                // Create gradient for under the curve
                const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(215, 255, 83, 0.45)');
                gradient.addColorStop(0.6, 'rgba(215, 255, 83, 0.08)');
                gradient.addColorStop(1, 'rgba(215, 255, 83, 0.0)');

                // Custom point styles: highlight current month
                const pointRadius = labels.map((_, i) => (i === currIdx ? 7 : 4));
                const pointBgColor = labels.map((_, i) => (i === currIdx ? '#091315' : '#D7FF53'));
                const pointBorderColor = labels.map((_, i) => (i === currIdx ? '#D7FF53' : '#091315'));
                const pointBorderWidth = labels.map((_, i) => (i === currIdx ? 3 : 2));

                this.charts.monthly = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Monthly Invoiced Sales',
                            data: values,
                            borderColor: '#091315',
                            borderWidth: 2.8,
                            backgroundColor: gradient,
                            fill: true,
                            tension: 0.35,
                            pointBackgroundColor: pointBgColor,
                            pointBorderColor: pointBorderColor,
                            pointBorderWidth: pointBorderWidth,
                            pointRadius: pointRadius,
                            pointHoverRadius: 8,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index',
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#091315',
                                titleColor: '#D7FF53',
                                bodyColor: '#FFFFFF',
                                borderColor: 'rgba(255, 255, 255, 0.1)',
                                borderWidth: 1,
                                padding: 12,
                                boxPadding: 6,
                                cornerRadius: 14,
                                displayColors: false,
                                callbacks: {
                                    title: (items) => items[0].label + ' Sales',
                                    label: (item) => {
                                        return 'Invoiced: ' + this.formatINR(item.raw);
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { font: { family: 'IBM Plex Mono', size: 11 }, color: '#64748B' }
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: '#F1F5F9' },
                                ticks: {
                                    font: { family: 'IBM Plex Mono', size: 10 },
                                    color: '#64748B',
                                    callback: (val) => this.formatCompactINR(val)
                                }
                            }
                        }
                    }
                });
            },

            // 2. Yearly Sales Historical Bar Chart
            renderYearlySalesChart() {
                const canvas = document.getElementById('yearlySalesChart');
                if (!canvas || !window.Chart) return;

                if (this.charts.yearly) {
                    this.charts.yearly.destroy();
                }

                const ctx = canvas.getContext('2d');
                const data = this.analytics.yearly_sales || {};
                const labels = data.labels || [];
                const values = data.values || [];

                this.charts.yearly = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Annual Billed Sales',
                            data: values,
                            backgroundColor: labels.map((l, i) => i === labels.length - 1 ? '#091315' : '#E2E8F0'),
                            hoverBackgroundColor: '#D7FF53',
                            borderRadius: 12,
                            borderSkipped: false,
                            barPercentage: 0.55,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#091315',
                                titleColor: '#D7FF53',
                                bodyColor: '#FFFFFF',
                                padding: 12,
                                cornerRadius: 14,
                                displayColors: false,
                                callbacks: {
                                    label: (item) => 'Turnover: ' + this.formatINR(item.raw)
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { font: { family: 'IBM Plex Mono', size: 11 }, color: '#64748B' }
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: '#F1F5F9' },
                                ticks: {
                                    font: { family: 'IBM Plex Mono', size: 10 },
                                    color: '#64748B',
                                    callback: (val) => this.formatCompactINR(val)
                                }
                            }
                        }
                    }
                });
            },

            // 3. Product-wise Monthly Sales Quantity in KG
            renderProductQtyChart() {
                const canvas = document.getElementById('productQtyChart');
                if (!canvas || !window.Chart) return;

                if (this.charts.productQty) {
                    this.charts.productQty.destroy();
                }

                const ctx = canvas.getContext('2d');
                const data = this.analytics.product_monthly_qty || {};
                const labels = data.labels || [];
                const mode = this.selectedProductChartMode;

                let datasets = [];

                if (mode === 'total') {
                    // Single aggregate bar chart
                    datasets = [{
                        label: 'Total Dispatched (KG)',
                        data: data.total_monthly_qty || [],
                        backgroundColor: '#091315',
                        hoverBackgroundColor: '#D7FF53',
                        borderRadius: 10,
                        barPercentage: 0.6,
                    }];
                } else if (mode === 'multi') {
                    // Top products line datasets
                    datasets = (data.datasets || []).map(ds => ({
                        label: ds.label,
                        data: ds.data,
                        borderColor: ds.borderColor,
                        backgroundColor: ds.backgroundColor,
                        borderWidth: 2.2,
                        tension: 0.3,
                        pointRadius: 3,
                    }));
                } else {
                    // Specific product selected
                    const p = (data.products_list || []).find(it => String(it.id) === String(mode));
                    const ds = (data.datasets || []).find(it => it.label === p?.name);
                    datasets = [{
                        label: p ? p.name + ' (KG)' : 'Product Qty (KG)',
                        data: ds ? ds.data : (data.total_monthly_qty || []),
                        borderColor: '#091315',
                        backgroundColor: 'rgba(215, 255, 83, 0.4)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#D7FF53',
                        pointBorderColor: '#091315',
                        pointRadius: 5,
                    }];
                }

                this.charts.productQty = new Chart(ctx, {
                    type: mode === 'total' ? 'bar' : 'line',
                    data: {
                        labels: labels,
                        datasets: datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index',
                        },
                        plugins: {
                            legend: {
                                display: (mode === 'multi' && datasets.length > 1),
                                position: 'top',
                                labels: {
                                    font: { family: 'Outfit', size: 11, weight: 'bold' },
                                    boxWidth: 10,
                                    usePointStyle: true
                                }
                            },
                            tooltip: {
                                backgroundColor: '#091315',
                                titleColor: '#D7FF53',
                                bodyColor: '#FFFFFF',
                                padding: 12,
                                cornerRadius: 14,
                                callbacks: {
                                    label: (item) => `${item.dataset.label}: ${this.formatQty(item.raw, 'KG')}`
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { font: { family: 'IBM Plex Mono', size: 11 }, color: '#64748B' }
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: '#F1F5F9' },
                                ticks: {
                                    font: { family: 'IBM Plex Mono', size: 10 },
                                    color: '#64748B',
                                    callback: (val) => Number(val).toLocaleString('en-IN') + ' KG'
                                }
                            }
                        }
                    }
                });
            },

            // 4. Cash Flow Health: Collections vs Outstanding Multi-Bar Chart
            renderCashFlowChart() {
                const canvas = document.getElementById('cashFlowChart');
                if (!canvas || !window.Chart) return;

                if (this.charts.cashFlow) {
                    this.charts.cashFlow.destroy();
                }

                const ctx = canvas.getContext('2d');
                const data = this.analytics.collections_vs_outstanding || {};
                const labels = data.labels || [];

                this.charts.cashFlow = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Billed',
                                data: data.invoiced || [],
                                backgroundColor: '#091315',
                                borderRadius: 6,
                                barPercentage: 0.8,
                            },
                            {
                                label: 'Collected',
                                data: data.collected || [],
                                backgroundColor: '#10B981',
                                borderRadius: 6,
                                barPercentage: 0.8,
                            },
                            {
                                label: 'Balance Due',
                                data: data.outstanding || [],
                                backgroundColor: '#EF4444',
                                borderRadius: 6,
                                barPercentage: 0.8,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index',
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#091315',
                                titleColor: '#D7FF53',
                                bodyColor: '#FFFFFF',
                                padding: 12,
                                cornerRadius: 14,
                                callbacks: {
                                    label: (item) => `${item.dataset.label}: ${this.formatINR(item.raw)}`
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { font: { family: 'IBM Plex Mono', size: 11 }, color: '#64748B' }
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: '#F1F5F9' },
                                ticks: {
                                    font: { family: 'IBM Plex Mono', size: 10 },
                                    color: '#64748B',
                                    callback: (val) => this.formatCompactINR(val)
                                }
                            }
                        }
                    }
                });
            },

            // Operational & WhatsApp Actions
            async openDailyDigest() {
                this.isDigestOpen = true;
                this.isDigestLoading = true;
                try {
                    const res = await fetch('{{ route("dashboard.daily-digest") }}');
                    this.digestData = await res.json();
                } catch (e) {
                    if (window.showToast) {
                        window.showToast('Could not compile daily digest', 'error');
                    }
                } finally {
                    this.isDigestLoading = false;
                }
            },

            shareDigestWhatsApp() {
                if (!this.digestData.whatsapp_text) return;
                const encoded = encodeURIComponent(this.digestData.whatsapp_text);
                window.open(`https://wa.me/?text=${encoded}`, '_blank');
            },

            openWhatsAppDrawer(item) {
                const name = item.contact_person || item.customer_name || 'there';
                this.whatsAppData = {
                    recipientName: name,
                    recipientPhone: item.phone || '+919826011223',
                    message: item.suggested_message || `Hi ${name}, following up regarding our recent discussion. Please let me know how you would like to proceed.`
                };
                this.isWhatsAppOpen = true;
            },

            launchWhatsApp() {
                const phone = this.whatsAppData.recipientPhone.replace(/[^0-9]/g, '');
                const target = phone.length === 10 ? '91' + phone : phone;
                const encoded = encodeURIComponent(this.whatsAppData.message);
                window.open(`https://wa.me/${target}?text=${encoded}`, '_blank');
                this.isWhatsAppOpen = false;
            },

            openCompleteModal(item) {
                this.taskData = {
                    id: item.task_id || item.id,
                    customerName: item.customer_name || item.title,
                    reason: item.title || item.next_action
                };
                this.isCompleteOpen = true;
            },

            async quickSnooze(item, days) {
                const id = item.task_id || item.id;
                const date = new Date();
                date.setDate(date.getDate() + days);
                const dateStr = date.toISOString().split('T')[0];

                try {
                    const res = await fetch(`/tasks/${id}/complete`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            outcome_notes: `Snoozed for ${days} days by Managing Director`,
                            needs_next_action: true,
                            next_action: item.next_action || 'Follow up with client',
                            next_due_date: dateStr,
                            priority: 'HIGH'
                        })
                    });
                    window.location.reload();
                } catch (e) {
                    alert('Could not snooze item. Please try again.');
                }
            }
        }
    }
</script>
@endsection