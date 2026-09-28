@extends('layouts.app')

@section('title', 'Sales Orders & Contract Revisions')

@section('content')
<div class="space-y-6 pb-12" x-data="ordersPageApp()">

    <!-- 1. EXECUTIVE HEADER & ACTIONS -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 sm:p-6 lg:p-7 rounded-2xl sm:rounded-3xl border border-neutral-200/80 shadow-[0_2px_12px_rgba(0,0,0,0.02)]">
        <div class="space-y-1">
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#F5F6F8] text-neutral-600 border border-neutral-200/80 font-mono uppercase tracking-wider">Contracts</span>
                <span class="text-xs text-neutral-300">·</span>
                <span class="text-xs text-neutral-500 font-medium">{{ $totalOrdersCount }} Total Contracts</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl bg-[#091315] text-[#D7FF53] flex items-center justify-center font-black text-base shrink-0 shadow-xs border border-neutral-800 font-display">
                    📑
                </div>
                <div>
                    <h2 class="text-2xl font-black text-neutral-900 tracking-tight font-display">Sales Orders</h2>
                    <p class="text-xs text-neutral-500 mt-0.5">
                        Multi-item buyer contracts, production dispatch schedules, and fulfillment tracking.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a
                href="{{ route('shipments.index') }}"
                class="px-4 py-2.5 rounded-full text-xs font-bold text-neutral-800 bg-[#F5F6F8] hover:bg-neutral-100 border border-neutral-200/80 transition-all flex items-center gap-1.5 shadow-2xs"
            >
                <span>🚚</span>
                <span>Commercial Shipments</span>
            </a>
            <a
                href="{{ route('invoices.index') }}"
                class="px-4 py-2.5 rounded-full text-xs font-bold text-neutral-800 bg-[#F5F6F8] hover:bg-neutral-100 border border-neutral-200/80 transition-all flex items-center gap-1.5 shadow-2xs"
            >
                <span>🧾</span>
                <span>Tax Invoices</span>
            </a>
            <button
                @click="openNewOrderModal()"
                class="px-5 py-2.5 rounded-full text-xs font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] border border-[#c8f043] shadow-2xs transition-all flex items-center gap-1.5 cursor-pointer active:scale-95"
            >
                <span class="text-[#091315] font-black">+</span>
                <span>Create Sales Order (PO)</span>
            </button>
        </div>
    </div>

    <!-- 2. EXECUTIVE FULFILLMENT KPI BAR -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- KPI 1: Total Order Book Value -->
        <div class="bg-white p-6 rounded-3xl border border-neutral-200/80 shadow-[0_2px_12px_rgba(0,0,0,0.02)] relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[10px] uppercase font-bold text-neutral-400 tracking-wider block font-mono">Total Order Book</span>
                    <h3 class="text-2xl font-black text-neutral-900 mt-1 font-display">{{ formatINR($totalCommittedValue) }}</h3>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-[#091315] text-[#D7FF53] flex items-center justify-center font-bold text-sm shadow-xs border border-neutral-800 font-display">
                    ₹
                </div>
            </div>
            <div class="pt-3.5 border-t border-neutral-100 flex items-center justify-between text-xs text-neutral-500 font-medium mt-3">
                <span>{{ $totalOrdersCount }} Total PO Contracts</span>
                <span class="font-bold text-neutral-800 font-mono">{{ formatWeightMT($totalCommittedQty) }} Booked</span>
            </div>
        </div>

        <!-- KPI 2: Dispatched Material Volume -->
        <div class="bg-white p-6 rounded-3xl border border-neutral-200/80 shadow-[0_2px_12px_rgba(0,0,0,0.02)] relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[10px] uppercase font-bold text-neutral-400 tracking-wider block font-mono">Dispatched Material</span>
                    <h3 class="text-2xl font-black text-emerald-600 mt-1 font-display">{{ formatWeightMT($totalShippedQty) }}</h3>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center text-base">
                    🚚
                </div>
            </div>
            <div class="space-y-1.5 pt-3.5 border-t border-neutral-100 mt-3">
                <div class="flex items-center justify-between text-[11px] font-bold">
                    <span class="text-neutral-500">Overall Fulfillment Rate</span>
                    <span class="text-emerald-700 font-mono font-black">{{ $overallFulfillmentPct }}%</span>
                </div>
                <div class="w-full bg-[#F5F6F8] rounded-full h-2 overflow-hidden border border-neutral-200/80">
                    <div class="h-full rounded-full bg-[#091315] transition-all duration-500" style="width: {{ $overallFulfillmentPct }}%"></div>
                </div>
            </div>
        </div>

        <!-- KPI 3: Pending Fulfillment Backlog -->
        <div class="bg-white p-6 rounded-3xl border border-neutral-200/80 shadow-[0_2px_12px_rgba(0,0,0,0.02)] relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[10px] uppercase font-bold text-rose-600 tracking-wider block font-mono">Pending Backlog</span>
                    <h3 class="text-2xl font-black text-rose-600 mt-1 font-display">{{ formatWeightMT($totalPendingQty) }}</h3>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-700 border border-rose-200 flex items-center justify-center text-base">
                    ⏳
                </div>
            </div>
            <div class="pt-3.5 border-t border-neutral-100 flex items-center justify-between text-xs font-medium mt-3">
                <span class="text-neutral-500">Awaiting Dispatch</span>
                <span class="px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200 font-bold text-[10px] font-mono">
                    {{ $pendingOrdersCount + $partialOrdersCount }} Active POs
                </span>
            </div>
        </div>

        <!-- KPI 4: Contract Pipeline Status -->
        <div class="bg-white p-6 rounded-3xl border border-neutral-200/80 shadow-[0_2px_12px_rgba(0,0,0,0.02)] relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[10px] uppercase font-bold text-neutral-400 tracking-wider block font-mono">Contract Status</span>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-xl font-black text-emerald-700 font-display">{{ $completedOrdersCount }} <span class="text-xs text-neutral-400 font-bold font-sans">Closed</span></span>
                        <span class="text-neutral-300">/</span>
                        <span class="text-xl font-black text-neutral-700 font-display">{{ $partialOrdersCount }} <span class="text-xs text-neutral-400 font-bold font-sans">Partial</span></span>
                    </div>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-[#F5F6F8] text-neutral-800 border border-neutral-200/80 flex items-center justify-center text-base">
                    🛡️
                </div>
            </div>
            <div class="pt-3.5 border-t border-neutral-100 flex items-center justify-between text-xs text-neutral-500 font-medium mt-3">
                <span>{{ $pendingOrdersCount }} Unshipped POs</span>
                <span class="text-[10px] font-bold text-emerald-700 font-mono">100% Lock Safeguard</span>
            </div>
        </div>
    </div>

    <!-- 3. SEARCH, STATUS TABS & CUSTOMER FILTER -->
    <div class="bg-white p-3.5 sm:p-5 rounded-2xl sm:rounded-3xl border border-neutral-200/80 shadow-[0_2px_12px_rgba(0,0,0,0.02)] space-y-3">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <!-- Status Tabs -->
            <div class="flex flex-wrap items-center gap-1.5">
                <button
                    type="button"
                    @click="activeTab = 'ALL'"
                    :class="activeTab === 'ALL' ? 'bg-[#091315] text-[#D7FF53] shadow-xs' : 'bg-[#F5F6F8] text-neutral-600 hover:text-neutral-900 border border-neutral-200/80'"
                    class="px-4 py-2 rounded-full text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5 font-display"
                >
                    <span>All Orders</span>
                    <span :class="activeTab === 'ALL' ? 'bg-neutral-800 text-[#D7FF53]' : 'bg-neutral-200 text-neutral-700'" class="px-2 py-0.5 rounded-full text-[10px] font-mono">{{ $totalOrdersCount }}</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'PENDING'"
                    :class="activeTab === 'PENDING' ? 'bg-[#091315] text-[#D7FF53] shadow-xs' : 'bg-[#F5F6F8] text-neutral-600 hover:text-neutral-900 border border-neutral-200/80'"
                    class="px-4 py-2 rounded-full text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5 font-display"
                >
                    <span>⏳ Pending Dispatch (0%)</span>
                    <span :class="activeTab === 'PENDING' ? 'bg-neutral-800 text-[#D7FF53]' : 'bg-rose-100 text-rose-800'" class="px-2 py-0.5 rounded-full text-[10px] font-mono">{{ $pendingOrdersCount }}</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'PARTIAL'"
                    :class="activeTab === 'PARTIAL' ? 'bg-[#091315] text-[#D7FF53] shadow-xs' : 'bg-[#F5F6F8] text-neutral-600 hover:text-neutral-900 border border-neutral-200/80'"
                    class="px-4 py-2 rounded-full text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5 font-display"
                >
                    <span>🚚 In Progress / Partial</span>
                    <span :class="activeTab === 'PARTIAL' ? 'bg-neutral-800 text-[#D7FF53]' : 'bg-blue-100 text-blue-800'" class="px-2 py-0.5 rounded-full text-[10px] font-mono">{{ $partialOrdersCount }}</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'COMPLETED'"
                    :class="activeTab === 'COMPLETED' ? 'bg-[#091315] text-[#D7FF53] shadow-xs' : 'bg-[#F5F6F8] text-neutral-600 hover:text-neutral-900 border border-neutral-200/80'"
                    class="px-4 py-2 rounded-full text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5 font-display"
                >
                    <span>✅ 100% Fulfilled & Closed</span>
                    <span :class="activeTab === 'COMPLETED' ? 'bg-neutral-800 text-[#D7FF53]' : 'bg-emerald-100 text-emerald-800'" class="px-2 py-0.5 rounded-full text-[10px] font-mono">{{ $completedOrdersCount }}</span>
                </button>
            </div>

            <!-- Search and Customer Filter -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full lg:max-w-md lg:ml-auto">
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-neutral-400 text-xs">🔍</span>
                    <input
                        type="text"
                        x-model="searchQuery"
                        placeholder="Search PO#, Customer, City, Product Cut..."
                        class="w-full pl-8 pr-7 py-2 border border-neutral-200/80 rounded-full text-xs bg-[#F5F6F8] focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-[#091315] transition-all placeholder:text-neutral-400"
                    >
                    <button
                        x-show="searchQuery.length > 0"
                        @click="searchQuery = ''"
                        class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-neutral-400 hover:text-neutral-700 cursor-pointer text-xs"
                    >✕</button>
                </div>

                <select
                    x-model="customerFilter"
                    class="px-4 py-2 border border-neutral-200/80 rounded-full text-xs bg-[#F5F6F8] focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-[#091315] font-medium w-full sm:max-w-[160px]"
                >
                    <option value="">All Clients</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- 4. ORDERS MASTER-DETAIL LIST -->
    <div class="space-y-4">
        @forelse($orders as $order)
            @php
                $totalOrderQty = (float) $order->items->sum('order_qty');
                $totalShippedQty = (float) $order->items->sum('shipped_qty');
                $totalBalanceQty = (float) $order->items->sum('balance_qty');
                $pct = $totalOrderQty > 0 ? round(($totalShippedQty / $totalOrderQty) * 100) : 0;
                $isDelivered = ($totalBalanceQty <= 0 && $totalOrderQty > 0) || $order->status === 'COMPLETED';
                $isUnshipped = ($totalShippedQty == 0);
                $isPartial = ($totalShippedQty > 0 && $totalBalanceQty > 0);
                $statusCategory = $isDelivered ? 'COMPLETED' : ($isPartial ? 'PARTIAL' : 'PENDING');
                $hasShipments = $order->shipments->count() > 0 || $totalShippedQty > 0;
                $hasInvoices = $order->invoices->count() > 0;

                // Search keywords string for client-side instant filtering
                $productNames = $order->items->map(fn($it) => $it->product->product_name . ' ' . $it->product->product_code)->join(' ');
                $searchKeywords = strtolower("{$order->order_number} {$order->po_number} {$order->customer->company_name} {$order->customer->city} {$order->customer->state} {$productNames}");
            @endphp

            <div
                x-show="matchesFilter('{{ $statusCategory }}', '{{ $order->customer_id }}', '{{ addslashes($searchKeywords) }}')"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 transform scale-98"
                x-transition:enter-end="opacity-100 transform scale-100"
                class="bg-white rounded-2xl sm:rounded-3xl border border-neutral-200/80 hover:border-neutral-400 shadow-[0_2px_12px_rgba(0,0,0,0.02)] transition-all p-4 sm:p-6 lg:p-7 space-y-4 relative overflow-hidden group"
            >
                <!-- TOP CONTRACT BADGE & CLIENT HEADER -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-neutral-100 pb-4">
                    <div class="flex items-start sm:items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-[#091315] text-[#D7FF53] font-black flex flex-col items-center justify-center shrink-0 shadow-xs border border-neutral-800 font-display">
                            <span class="text-xs tracking-wider">PO</span>
                            <span class="text-[8px] uppercase font-bold font-mono text-[#D7FF53]/80">{{ $order->items->count() }} Cuts</span>
                        </div>

                        <div class="space-y-0.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-black text-base text-neutral-900 tracking-tight font-display">{{ $order->order_number }}</h3>
                                @if($order->po_number && $order->po_number !== $order->order_number)
                                    <span class="px-2.5 py-0.5 rounded-full bg-neutral-100 text-neutral-600 font-mono font-bold text-[10px] border border-neutral-200/80">Buyer PO: {{ $order->po_number }}</span>
                                @endif
                                @if($order->revision_number > 0)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-900 border border-amber-300 font-bold text-[10px] font-mono shadow-2xs" title="{{ $order->last_revision_reason ?? 'Revised Order' }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        <span>Rev {{ $order->revision_number }}</span>
                                        @if($order->last_revised_at)
                                            <span class="font-normal text-amber-700">({{ $order->last_revised_at->format('d M') }})</span>
                                        @endif
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full bg-neutral-100 text-neutral-500 font-bold text-[10px] font-mono border border-neutral-200/80">
                                        Rev 0
                                    </span>
                                @endif

                                @php $poDoc = $order->poDocument; @endphp
                                @if($poDoc)
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-blue-50 text-blue-900 border border-blue-200 text-[10px] font-mono shadow-2xs">
                                        <span class="font-bold">📄 PO Document (v{{ $poDoc->current_version }})</span>
                                        <a href="{{ route('documents.preview', $poDoc->id) }}" target="_blank" class="font-bold text-blue-700 hover:underline hover:text-blue-950 ml-0.5">View</a>
                                        <span class="text-blue-300">|</span>
                                        <a href="{{ route('documents.download', $poDoc->id) }}" class="font-bold text-blue-700 hover:underline hover:text-blue-950">Download</a>
                                    </div>
                                @endif
                                <span class="text-xs text-neutral-400 font-medium">· Contract Date: <b class="text-neutral-700">{{ $order->order_date->format('d M Y') }}</b></span>
                            </div>

                            <div class="flex items-center gap-2 text-xs">
                                <a href="{{ route('customers.show', $order->customer_id) }}" class="font-black text-neutral-900 hover:text-[#091315] hover:underline font-display">
                                    {{ $order->customer->company_name }}
                                </a>
                                <span class="text-neutral-300">•</span>
                                <span class="text-neutral-500 font-medium">{{ $order->customer->city }}, {{ $order->customer->state }}</span>
                                <span class="px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-600 font-mono text-[10px] font-bold border border-neutral-200/80">{{ $order->customer->customer_code }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Financials & Fulfillment Status Badge -->
                    <div class="flex items-center gap-4 sm:text-right flex-wrap">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-neutral-400 block tracking-wider font-mono">Contract Value</span>
                            <span class="font-black text-lg text-neutral-900 font-display">{{ formatINR($order->total_amount) }}</span>
                            @if((float)$order->advance_received > 0)
                                <span class="text-[10px] font-mono text-emerald-700 font-bold block">Advance: {{ formatINR($order->advance_received) }}</span>
                            @elseif((float)$order->advance_required > 0)
                                <span class="text-[10px] font-mono text-amber-700 font-bold block">Adv Req: {{ formatINR($order->advance_required) }}</span>
                            @endif
                        </div>

                        <div class="flex items-center gap-2 flex-wrap">
                            @if($order->status === 'ADVANCE_PENDING' && (float)$order->advance_required > (float)$order->advance_received)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider bg-amber-50 text-amber-900 border border-amber-300 font-mono shadow-2xs">
                                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                    Advance Pending: {{ formatINR((float)$order->advance_required - (float)$order->advance_received) }}
                                </span>
                            @elseif((float)$order->advance_received > 0)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-50 text-emerald-800 border border-emerald-200 font-mono shadow-2xs">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    Advance Paid: {{ formatINR($order->advance_received) }}
                                </span>
                            @endif

                            @if($isDelivered)
                                <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-50 text-emerald-800 border border-emerald-200 font-mono shadow-2xs">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    100% Fulfilled
                                </span>
                            @elseif($isPartial)
                                <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider bg-[#F3FED4] text-[#091315] border border-[#D7FF53] font-mono shadow-2xs">
                                    <span class="w-2 h-2 rounded-full bg-[#091315] animate-pulse"></span>
                                    {{ $pct }}% Dispatched
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider bg-neutral-100 text-neutral-700 border border-neutral-300 font-mono shadow-2xs">
                                    <span class="w-2 h-2 rounded-full bg-neutral-400"></span>
                                    Pending (0%)
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- PROGRESS & VOLUME FULFILLMENT METER -->
                <div class="p-3.5 bg-[#F5F6F8] rounded-2xl border border-neutral-200/60 space-y-2">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between text-xs gap-1">
                        <div class="flex items-center gap-2 font-bold text-neutral-800">
                            <span>Fulfillment: <span class="font-mono font-black {{ $pct === 100 ? 'text-emerald-700' : 'text-[#091315]' }}">{{ $pct }}%</span></span>
                            <span class="text-neutral-300 font-normal">|</span>
                            <span class="text-neutral-600 font-medium">Shipped: <b class="text-emerald-700 font-mono">{{ formatWeightMT($totalShippedQty) }}</b> of <b class="font-mono">{{ formatWeightMT($totalOrderQty) }}</b></span>
                        </div>
                        <div class="text-[11px] font-bold">
                            @if($totalBalanceQty > 0)
                                <span class="text-rose-700 bg-rose-50 border border-rose-200 px-2.5 py-0.5 rounded-full font-mono">⏳ Balance Pending: <b>{{ formatWeightMT($totalBalanceQty) }}</b></span>
                            @else
                                <span class="text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full font-mono">✓ All Lots Dispatched</span>
                            @endif
                        </div>
                    </div>

                    <div class="w-full bg-white rounded-full h-2 overflow-hidden border border-neutral-200/80">
                        <div
                            class="h-2 rounded-full transition-all duration-500 bg-[#091315]"
                            style="width: {{ $pct }}%"
                        ></div>
                    </div>
                </div>

                <!-- LINE ITEMS TABLE -->
                <div class="overflow-x-auto rounded-2xl border border-neutral-200/80">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-neutral-50/80 text-neutral-600 border-b border-neutral-200/80 font-bold font-mono text-[10px] uppercase tracking-wider">
                                <th class="py-2.5 px-3.5">Product Cut / SKU</th>
                                <th class="py-2.5 px-3.5 text-right">Contract Qty</th>
                                <th class="py-2.5 px-3.5 text-right">Rate / KG</th>
                                <th class="py-2.5 px-3.5 text-right">Line Total</th>
                                <th class="py-2.5 px-3.5 text-right text-emerald-800">Shipped Qty</th>
                                <th class="py-2.5 px-3.5 text-right text-amber-800">Pending Qty</th>
                                <th class="py-2.5 px-3.5 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100 bg-white">
                            @foreach($order->items as $it)
                                <tr class="hover:bg-[#F5F6F8]/80 transition-colors">
                                    <td class="py-2.5 px-3.5 font-bold text-neutral-900">
                                        {{ $it->product->product_name }}
                                        <span class="text-[10px] text-neutral-400 font-mono block">{{ $it->product->product_code }}</span>
                                    </td>
                                    <td class="py-2.5 px-3.5 text-right font-mono font-bold text-neutral-900">{{ formatQuantity($it->order_qty) }}</td>
                                    <td class="py-2.5 px-3.5 text-right font-mono text-neutral-600">₹{{ number_format($it->rate, 2) }}</td>
                                    <td class="py-2.5 px-3.5 text-right font-mono font-bold text-neutral-900">{{ formatINR($it->order_qty * $it->rate) }}</td>
                                    <td class="py-2.5 px-3.5 text-right font-mono font-bold text-emerald-600">{{ formatQuantity($it->shipped_qty) }}</td>
                                    <td class="py-2.5 px-3.5 text-right font-mono font-bold {{ $it->balance_qty > 0 ? 'text-amber-600' : 'text-neutral-400' }}">{{ formatQuantity($it->balance_qty) }}</td>
                                    <td class="py-2.5 px-3.5 text-center">
                                        @if($it->balance_qty <= 0)
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 font-mono uppercase">Shipped</span>
                                        @elseif($it->shipped_qty > 0)
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-blue-50 text-blue-800 border border-blue-200 font-mono uppercase">Partial</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-800 border border-amber-200 font-mono uppercase">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- CONNECTED DOCUMENTS (SHIPMENT LRS & INVOICES) -->
                @if($order->shipments->count() > 0 || $order->invoices->count() > 0)
                    <div class="p-3.5 bg-[#F5F6F8] rounded-2xl border border-neutral-200/60 flex flex-wrap items-center justify-between gap-3 text-xs">
                        <!-- Linked Shipments -->
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-bold text-neutral-700 flex items-center gap-1 font-display">
                                <span>🚚</span>
                                <span>Dispatches (LRs):</span>
                            </span>
                            @forelse($order->shipments as $shp)
                                <a
                                    href="{{ route('shipments.index') }}"
                                    class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-white border border-neutral-200/80 hover:border-[#091315] text-neutral-800 font-mono font-bold text-[11px] shadow-2xs transition-all"
                                    title="{{ $shp->transporter_name }} · {{ $shp->shipment_date->format('d M Y') }}"
                                >
                                    <span>LR-{{ $shp->lr_number }}</span>
                                    <span class="text-[10px] text-neutral-400 font-normal">({{ $shp->shipment_date->format('d M') }})</span>
                                </a>
                            @empty
                                <span class="text-neutral-400 italic text-xs">No dispatches logged</span>
                            @endforelse
                        </div>

                        <!-- Linked Invoices -->
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-bold text-neutral-700 flex items-center gap-1 font-display">
                                <span>🧾</span>
                                <span>Tax Invoices:</span>
                            </span>
                            @forelse($order->invoices as $inv)
                                <a
                                    href="{{ route('invoices.index') }}"
                                    class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-white border border-neutral-200/80 hover:border-[#091315] text-neutral-800 font-mono font-bold text-[11px] shadow-2xs transition-all"
                                    title="Invoice Date: {{ $inv->invoice_date->format('d M Y') }}"
                                >
                                    <span>{{ $inv->invoice_number }}</span>
                                    <span class="text-[10px] text-emerald-700 font-bold font-mono">({{ formatINR($inv->total_amount) }})</span>
                                </a>
                            @empty
                                <span class="text-neutral-400 italic text-xs">No invoices issued</span>
                            @endforelse
                        </div>
                    </div>
                @endif

                <!-- FOOTER: TERMS, REMARKS & ONE-CLICK ACTIONS -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-neutral-100">
                    <div class="flex flex-wrap items-center gap-3 text-xs text-neutral-500">
                        <span>Payment Terms: <b class="text-neutral-800 font-bold">{{ $order->payment_terms ?? '30 Days Credit' }}</b></span>
                        @if($order->notes)
                            <span class="text-neutral-300">•</span>
                            <span class="text-neutral-600 truncate max-w-sm font-medium" title="{{ $order->notes }}">📝 {{ $order->notes }}</span>
                        @endif
                    </div>

                    <!-- ACTION BUTTONS -->
                    <div class="flex flex-wrap items-center gap-2">
                        @if((float)$order->advance_received < (float)$order->total_amount && !$isDelivered)
                            <!-- Record Advance Payment Trigger -->
                            <button
                                type="button"
                                @click="openRecordAdvanceModal(@js($order))"
                                class="px-3.5 py-1.5 rounded-full text-xs font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] border border-[#c8f043] transition-all flex items-center gap-1 cursor-pointer shadow-2xs active:scale-95"
                                title="Record advance payment received against this PO"
                            >
                                <span class="font-black">+</span>
                                <span>Record Advance</span>
                            </button>
                        @endif

                        <!-- WhatsApp Update -->
                        <button
                            type="button"
                            @click="openWhatsAppDrawer(@js($order))"
                            class="px-3.5 py-1.5 rounded-full text-xs font-bold text-emerald-900 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200/80 transition-all flex items-center gap-1.5 cursor-pointer shadow-2xs"
                            title="Send professional WhatsApp order status to buyer"
                        >
                            <span>💬</span>
                            <span>WhatsApp Status</span>
                        </button>

                        @if($order->revisions->count() > 0)
                            <!-- Revision History & Diff Trigger -->
                            <button
                                type="button"
                                @click="openRevisionHistoryModal({{ $order->id }})"
                                class="px-3.5 py-1.5 rounded-full text-xs font-bold text-neutral-800 bg-[#F5F6F8] hover:bg-neutral-100 border border-neutral-200/80 transition-all flex items-center gap-1.5 cursor-pointer shadow-2xs font-mono"
                                title="View previous revision snapshots and side-by-side changes"
                            >
                                <span>🕒</span>
                                <span>Revisions ({{ $order->revisions->count() }})</span>
                            </button>
                        @endif

                        @if($isDelivered)
                            <!-- 100% Fulfilled: Edit & Delete Locked -->
                            <span class="inline-flex items-center gap-1 px-3.5 py-1.5 rounded-full text-xs font-bold text-neutral-500 bg-[#F5F6F8] border border-neutral-200/80 font-mono" title="Fully delivered contracts are locked to preserve accounting integrity">
                                🔒 Fulfilled & Locked
                            </span>
                        @else
                            <!-- Active PO: Edit Allowed -->
                            <button
                                type="button"
                                @click="openEditOrderModal(@js($order))"
                                class="px-3.5 py-1.5 rounded-full text-xs font-bold text-neutral-800 bg-[#F5F6F8] hover:bg-neutral-100 border border-neutral-200/80 transition-all flex items-center gap-1 cursor-pointer shadow-2xs"
                            >
                                <span>✏️</span>
                                <span>Edit PO</span>
                            </button>

                            @if(!$hasShipments && !$hasInvoices)
                                <!-- No dispatches or invoices: Delete Allowed -->
                                <form action="{{ route('orders.destroy', $order->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete PO {{ $order->order_number }}?');">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="px-3.5 py-1.5 rounded-full text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-all cursor-pointer flex items-center gap-1 shadow-2xs"
                                    >
                                        <span>🗑️</span>
                                        <span>Delete</span>
                                    </button>
                                </form>
                            @endif

                            <!-- Quick Dispatch Material Trigger -->
                            <a
                                href="{{ route('shipments.index') }}"
                                class="px-4 py-1.5 rounded-full text-xs font-bold text-white bg-[#091315] hover:bg-black shadow-2xs transition-all flex items-center gap-1.5 active:scale-95"
                            >
                                <span>🚚</span>
                                <span>Dispatch Material →</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-3xl border border-neutral-200/80 p-12 text-center shadow-[0_2px_12px_rgba(0,0,0,0.02)] space-y-3">
                <div class="w-14 h-14 rounded-3xl bg-[#F5F6F8] text-[#091315] border border-neutral-200/80 flex items-center justify-center text-2xl mx-auto font-black shadow-inner">
                    📑
                </div>
                <h3 class="text-base font-black text-neutral-900 font-display">No Purchase Orders Found</h3>
                <p class="text-xs text-neutral-500 max-w-md mx-auto">
                    Create buyer purchase orders to track fulfillment, multi-item dispatches, and tax invoicing.
                </p>
                <button
                    @click="openNewOrderModal()"
                    class="px-5 py-2.5 rounded-full text-xs font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] border border-[#c8f043] shadow-2xs transition-all cursor-pointer active:scale-95 inline-flex items-center gap-1.5"
                >
                    <span>+</span>
                    <span>Create First Sales Order (PO)</span>
                </button>
            </div>
        @endforelse
    </div>

    <!-- 5. MULTI-ITEM CREATE SALES ORDER MODAL -->
    <template x-teleport="body">
        <div x-show="isNewOrderOpen" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-2 sm:p-4">
            <div @click.outside="isNewOrderOpen = false" class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-neutral-200/80 w-full max-w-3xl overflow-hidden max-h-[92vh] flex flex-col text-xs">
                <div class="p-4 sm:p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-2xl bg-[#D7FF53]/20 border border-[#D7FF53]/30 text-[#D7FF53] flex items-center justify-center text-lg font-bold">
                            📑
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm tracking-tight text-white font-display">Create New Sales Order (PO)</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono">Multi-item buyer contract with automated fulfillment tracking</p>
                        </div>
                    </div>
                    <button @click="isNewOrderOpen = false" class="text-neutral-400 hover:text-white cursor-pointer">✕</button>
                </div>

                <form action="{{ route('orders.store') }}" method="POST" enctype="multipart/form-data" class="p-4 sm:p-5 space-y-4 text-xs overflow-y-auto flex-1" x-data="{ selectedPoFile: null }">
                    @csrf

                    <!-- Customer & PO Number -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Customer Account *</label>
                            <select name="customer_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-medium" required>
                                <option value="">-- Choose Buyer Account --</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->company_name }} ({{ $c->customer_code }}) · {{ $c->city }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Buyer Purchase Order (PO) Number *</label>
                            <input type="text" name="po_number" value="{{ old('po_number') }}" placeholder="e.g. PO-EV-2026-09" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-mono font-bold" required>
                        </div>
                    </div>

                    @if(session('requires_duplicate_confirmation'))
                        <div class="p-3 bg-red-50 border border-red-200 rounded-xl">
                            <p class="text-red-700 font-bold mb-2">{{ session('error') }}</p>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="confirm_duplicate" value="1" class="w-4 h-4 text-red-600 border-red-300 rounded focus:ring-red-500">
                                <span class="font-bold text-red-900">Confirm Duplicate: Create this order anyway</span>
                            </label>
                        </div>
                    @endif

                    <!-- Order Date, Payment Terms & Advance Required -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Order Contract Date *</label>
                            <input type="date" name="order_date" value="{{ date('Y-m-d') }}" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-medium" required>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Payment Terms</label>
                            <input type="text" name="payment_terms" value="30 Days Credit" placeholder="e.g. 50% Adv, 50% Delivery" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-medium">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Advance Required (₹)</label>
                            <input type="number" step="0.01" min="0" name="advance_required" value="{{ old('advance_required', 0) }}" placeholder="0 for standard credit" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-mono font-medium">
                        </div>
                    </div>

                    <!-- Multi-Product Line Items Section -->
                    <div class="space-y-3 pt-2">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="font-black text-sm text-neutral-900 font-display">Product Line Items</span>
                                <p class="text-[11px] text-neutral-500">Specify product cuts, quantities in KG, and agreed rates</p>
                            </div>
                            <button
                                type="button"
                                @click="addItemRow()"
                                class="px-3.5 py-1.5 rounded-full text-xs font-bold text-neutral-800 bg-[#F5F6F8] hover:bg-neutral-100 border border-neutral-200/80 flex items-center gap-1 cursor-pointer transition-all shadow-2xs"
                            >
                                + Add Line Item
                            </button>
                        </div>

                        <div class="space-y-2.5">
                            <template x-for="(item, idx) in newItems" :key="idx">
                                <div class="p-3.5 bg-[#F5F6F8] rounded-2xl border border-neutral-200/60 space-y-2 relative">
                                    <button
                                        type="button"
                                        x-show="newItems.length > 1"
                                        @click="removeItemRow(idx)"
                                        class="absolute top-2.5 right-2.5 text-neutral-400 hover:text-rose-600 font-black cursor-pointer text-sm"
                                        title="Remove Line Item"
                                    >
                                        ✕
                                    </button>

                                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-end pr-0 sm:pr-5">
                                        <div class="sm:col-span-5">
                                            <label class="block font-bold text-neutral-700 mb-1 text-[11px]" x-text="'Item #' + (idx + 1) + ' Product Cut *'"></label>
                                            <select :name="'items[' + idx + '][product_id]'" x-model="item.product_id" @change="onProductSelect(item, $event)" class="w-full px-2.5 py-1.5 border border-neutral-300 rounded-xl font-medium text-xs bg-white focus:ring-2 focus:ring-[#091315] focus:outline-hidden" required>
                                                @foreach($products as $p)
                                                    <option value="{{ $p->id }}" data-rate="{{ $p->standard_rate }}">{{ $p->product_name }} ({{ $p->product_code }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="sm:col-span-3">
                                            <label class="block font-bold text-neutral-700 mb-1 text-[11px]">Order Qty (KG) *</label>
                                            <input type="number" step="1" min="1" :name="'items[' + idx + '][order_qty]'" x-model="item.order_qty" placeholder="e.g. 5000" class="w-full px-2.5 py-1.5 border border-neutral-300 rounded-xl font-bold text-xs bg-white text-right focus:ring-2 focus:ring-[#091315] focus:outline-hidden" required>
                                        </div>
                                        <div class="sm:col-span-4">
                                            <label class="block font-bold text-neutral-700 mb-1 text-[11px]">Agreed Rate / KG (₹) *</label>
                                            <input type="number" step="0.5" min="1" :name="'items[' + idx + '][rate]'" x-model="item.rate" placeholder="120" class="w-full px-2.5 py-1.5 border border-neutral-300 rounded-xl font-bold text-xs bg-white text-right focus:ring-2 focus:ring-[#091315] focus:outline-hidden" required>
                                        </div>
                                    </div>

                                    <div class="flex justify-between items-center pt-1 text-[11px] text-neutral-500 font-medium px-1">
                                        <span x-text="'Weight: ' + ((parseFloat(item.order_qty) || 0) / 1000).toFixed(2) + ' MT (' + (parseFloat(item.order_qty) || 0).toLocaleString('en-IN') + ' KG)'"></span>
                                        <span>
                                            Line Total: <b class="text-neutral-900 font-black ml-1 font-mono" x-text="'₹' + ((parseFloat(item.order_qty) || 0) * (parseFloat(item.rate) || 0)).toLocaleString('en-IN')"></b>
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Live Total Weight & Value Summary -->
                    <div class="p-4 bg-[#091315] text-white rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-inner border border-neutral-800">
                        <div>
                            <span class="text-[10px] text-[#D7FF53] font-bold uppercase tracking-wider block font-mono">Estimated Total PO Value</span>
                            <p class="text-xl font-black text-white font-display" x-text="'₹' + calculateTotal(newItems).toLocaleString('en-IN')"></p>
                        </div>
                        <div class="sm:text-right">
                            <span class="text-[10px] text-neutral-400 font-bold uppercase tracking-wider block font-mono">Total Physical Volume</span>
                            <p class="text-sm font-bold text-[#D7FF53] font-mono" x-text="calculateTotalWeight(newItems)"></p>
                        </div>
                    </div>

                    <!-- Packaging / Delivery Notes -->
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Packaging & Delivery Specifications</label>
                        <textarea name="notes" rows="2" placeholder="e.g. 25 KG Poly-lined Corrugated Boxes. Moisture limit < 5.0%. Dispatch in weekly partial lots." class="w-full p-2.5 border border-neutral-300 rounded-xl text-xs focus:ring-2 focus:ring-[#091315] focus:outline-hidden"></textarea>
                    </div>

                    <!-- Customer PO Document Upload -->
                    <div class="p-3.5 bg-[#F5F6F8] rounded-2xl border border-neutral-200/80 space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block font-bold text-neutral-800 text-xs">Customer PO Document</label>
                            <span class="text-[10px] font-mono text-neutral-500">PDF, JPG, PNG, WEBP (Max 25 MB)</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="px-4 py-2 bg-white hover:bg-neutral-50 text-neutral-800 border border-neutral-300 rounded-xl font-bold text-xs cursor-pointer shadow-2xs inline-flex items-center gap-1.5 transition-all">
                                <span>📎</span>
                                <span>Choose File</span>
                                <input 
                                    type="file" 
                                    name="po_document" 
                                    accept=".pdf,.jpg,.jpeg,.png,.webp" 
                                    class="hidden" 
                                    @change="selectedPoFile = $event.target.files[0]"
                                >
                            </label>
                            <template x-if="selectedPoFile">
                                <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-xl border border-neutral-200 text-xs shadow-2xs">
                                    <span class="font-mono text-neutral-700 font-bold truncate max-w-[220px]" x-text="selectedPoFile.name"></span>
                                    <span class="text-[10px] text-neutral-400 font-mono" x-text="'(' + (selectedPoFile.size / 1024 / 1024).toFixed(2) + ' MB)'"></span>
                                    <button type="button" @click="selectedPoFile = null; $el.closest('.p-3.5').querySelector('input[type=file]').value = ''" class="text-neutral-400 hover:text-rose-600 font-bold ml-1 cursor-pointer" title="Remove selected file">✕</button>
                                </div>
                            </template>
                            <template x-if="!selectedPoFile">
                                <span class="text-[11px] text-neutral-400 italic">No file selected (optional)</span>
                            </template>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-neutral-100 flex justify-end gap-2 shrink-0">
                        <button type="button" @click="isNewOrderOpen = false" class="px-4 py-2 font-bold text-neutral-600 hover:bg-neutral-100 rounded-full cursor-pointer text-xs">Cancel</button>
                        <button type="submit" class="px-5 py-2 font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs cursor-pointer transition-all border border-[#c8f043] text-xs">
                            Create Sales Order (PO)
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- 6. MULTI-ITEM EDIT SALES ORDER MODAL -->
    <template x-teleport="body">
        <div x-show="isEditOrderOpen" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-2 sm:p-4">
            <div @click.outside="isEditOrderOpen = false" class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-neutral-200/80 w-full max-w-3xl overflow-hidden max-h-[92vh] flex flex-col text-xs">
                <div class="p-4 sm:p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-2xl bg-[#D7FF53]/20 border border-[#D7FF53]/30 text-[#D7FF53] flex items-center justify-center text-lg font-bold">
                            ✏️
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm tracking-tight text-white font-display">Edit Sales Order (PO)</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono" x-text="'Editing: ' + (editOrder.order_number || '')"></p>
                        </div>
                    </div>
                    <button @click="isEditOrderOpen = false" class="text-neutral-400 hover:text-white cursor-pointer">✕</button>
                </div>

                <form :action="'/orders/' + editOrder.id + '/update'" method="POST" class="p-5 space-y-4 text-xs overflow-y-auto flex-1">
                    @csrf

                    <!-- PO Revision & Snapshot Protection Alert -->
                    <div class="p-3 bg-amber-50 rounded-2xl border border-amber-200 text-amber-950 flex items-start gap-2.5">
                        <span class="text-base">📑</span>
                        <div class="space-y-0.5 flex-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-xs">PO Revision & Snapshot Protection Active</span>
                                <span class="px-2 py-0.5 rounded-full bg-amber-200/80 text-amber-900 font-bold text-[10px]" x-text="'Current Version: Rev ' + (editOrder.revision_number || 0)"></span>
                            </div>
                            <p class="text-[11px] text-amber-800 leading-relaxed">
                                Submitting changes will archive the current state to an immutable historical snapshot (<b x-text="'Rev ' + (editOrder.revision_number || 0)"></b>). All previous dispatches, LRs, and invoices will remain intact.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Customer Account</label>
                            <input type="text" :value="editOrder.customer?.company_name" class="w-full px-3 py-2 border border-slate-200 bg-slate-100 rounded-xl font-bold text-slate-700" readonly>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Buyer Purchase Order (PO) Number *</label>
                            <input type="text" name="po_number" x-model="editOrder.po_number" class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono font-bold text-xs" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Order Contract Date *</label>
                            <input type="date" name="order_date" x-model="editOrderDate" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-medium" required>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Payment Terms</label>
                            <input type="text" name="payment_terms" x-model="editOrder.payment_terms" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-medium">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Advance Required (₹)</label>
                            <input type="number" step="0.01" min="0" name="advance_required" x-model="editOrder.advance_required" placeholder="0 for standard credit" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-mono font-medium">
                        </div>
                    </div>

                    <!-- Multi-Product Line Items in Edit -->
                    <div class="space-y-3 pt-2">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="font-black text-sm text-slate-900">Product Line Items</span>
                                <p class="text-[11px] text-slate-500">Items already shipped cannot have quantity lowered below dispatched amount</p>
                            </div>
                            <button
                                type="button"
                                @click="addEditItemRow()"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold text-brand-700 bg-brand-50 hover:bg-brand-100 border border-brand-200 flex items-center gap-1 cursor-pointer transition-all shadow-2xs"
                            >
                                + Add Product Item
                            </button>
                        </div>

                        <div class="space-y-2.5">
                            <template x-for="(item, idx) in editItems" :key="idx">
                                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200 space-y-2 relative">
                                    <input type="hidden" :name="'items[' + idx + '][id]'" :value="item.id || ''">
                                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-end pr-0 sm:pr-5">
                                        <div class="sm:col-span-5">
                                            <label class="block font-bold text-slate-700 mb-1 text-[11px]" x-text="'Item #' + (idx + 1) + ' Product Cut'"></label>
                                            <select :name="'items[' + idx + '][product_id]'" x-model="item.product_id" class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg font-medium text-xs bg-white" required>
                                                @foreach($products as $p)
                                                    <option value="{{ $p->id }}">{{ $p->product_name }} ({{ $p->product_code }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="sm:col-span-3">
                                            <label class="block font-bold text-slate-700 mb-1 text-[11px]">Order Qty (KG) *</label>
                                            <input type="number" step="1" :name="'items[' + idx + '][order_qty]'" x-model="item.order_qty" class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg font-bold text-xs bg-white text-right" required>
                                        </div>
                                        <div class="sm:col-span-4">
                                            <label class="block font-bold text-slate-700 mb-1 text-[11px]">Rate / KG (₹) *</label>
                                            <input type="number" step="0.5" :name="'items[' + idx + '][rate]'" x-model="item.rate" class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg font-bold text-xs bg-white text-right" required>
                                        </div>
                                    </div>

                                    <div class="flex justify-between items-center pt-1 text-[11px] text-slate-500 font-medium px-1">
                                        <div>
                                            <span x-show="item.shipped_qty > 0" class="text-emerald-700 font-bold">Already Dispatched: <b x-text="Number(item.shipped_qty).toLocaleString('en-IN') + ' KG'"></b></span>
                                            <span x-show="!item.shipped_qty || item.shipped_qty <= 0" class="text-slate-400">Unshipped Item</span>
                                        </div>
                                        <span>
                                            Line Total: <b class="text-slate-900 font-black ml-1" x-text="'₹' + ((parseFloat(item.order_qty) || 0) * (parseFloat(item.rate) || 0)).toLocaleString('en-IN')"></b>
                                        </span>
                                    </div>

                                    <!-- Delete Item Button (Only if 0 shipped) -->
                                    <button
                                        type="button"
                                        x-show="editItems.length > 1 && (!item.shipped_qty || item.shipped_qty <= 0)"
                                        @click="removeEditItemRow(idx)"
                                        class="absolute top-2 right-2 text-slate-400 hover:text-rose-600 font-black cursor-pointer text-sm"
                                        title="Remove Line Item"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Live Total Order Value Preview -->
                    <div class="p-4 bg-slate-900 text-white rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-inner">
                        <div>
                            <span class="text-[11px] text-slate-400 font-medium uppercase tracking-wider block">Updated Total PO Value</span>
                            <p class="text-xl font-black text-amber-400" x-text="'₹' + calculateTotal(editItems).toLocaleString('en-IN')"></p>
                        </div>
                        <div class="sm:text-right">
                            <span class="text-[11px] text-slate-400 font-medium uppercase tracking-wider block">Total Physical Volume</span>
                            <p class="text-sm font-bold text-white" x-text="calculateTotalWeight(editItems)"></p>
                        </div>
                    </div>

                    <!-- Reason for Revision / Amendment -->
                    <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block font-bold text-slate-800 text-xs">
                                Reason for Amendment / Revision <span class="text-amber-600 font-bold">*</span>
                            </label>
                            <span class="text-[10px] text-slate-400 font-medium">Permanently stored in version history</span>
                        </div>
                        <input
                            type="text"
                            name="revision_reason"
                            x-model="editRevisionReason"
                            placeholder="e.g. Buyer revised PO to conclude trial lot at delivered 500 KG / Price revision"
                            class="w-full px-3 py-2 border border-neutral-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-[#091315] focus:outline-hidden bg-white"
                        >
                        <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                            <span class="text-[10px] text-slate-400">Quick reasons:</span>
                            <button type="button" @click="editRevisionReason = 'Customer revised PO to match delivered trial lot'" class="px-2 py-0.5 rounded-md bg-white hover:bg-slate-100 border border-slate-200 text-slate-600 text-[10px] font-medium transition-colors cursor-pointer">
                                + Trial Lot Closed
                            </button>
                            <button type="button" @click="editRevisionReason = 'Raw material market price update'" class="px-2 py-0.5 rounded-md bg-white hover:bg-slate-100 border border-slate-200 text-slate-600 text-[10px] font-medium transition-colors cursor-pointer">
                                + Market Price Surge
                            </button>
                            <button type="button" @click="editRevisionReason = 'Buyer amended delivery lot schedule'" class="px-2 py-0.5 rounded-md bg-white hover:bg-slate-100 border border-slate-200 text-slate-600 text-[10px] font-medium transition-colors cursor-pointer">
                                + Schedule Rescheduled
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Packaging & Delivery Specifications</label>
                        <textarea name="notes" x-model="editOrder.notes" rows="2" class="w-full p-2.5 border border-slate-300 rounded-xl text-xs"></textarea>
                    </div>

                    <div class="pt-3 border-t border-neutral-100 flex justify-end gap-2 shrink-0">
                        <button type="button" @click="isEditOrderOpen = false" class="px-4 py-2 font-bold text-neutral-600 hover:bg-neutral-100 rounded-full cursor-pointer text-xs">Cancel</button>
                        <button type="submit" class="px-5 py-2 font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs cursor-pointer transition-all border border-[#c8f043] text-xs">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- 7. WHATSAPP PO STATUS DRAWER -->
    <template x-teleport="body">
        <div x-show="isWhatsAppOpen" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-2 sm:p-4">
            <div @click.outside="isWhatsAppOpen = false" class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-neutral-200/80 w-full max-w-lg overflow-hidden text-xs flex flex-col max-h-[92vh]">
                <div class="p-4 sm:p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5">
                        <span class="text-lg">💬</span>
                        <div>
                            <h3 class="font-extrabold text-sm font-display">Send WhatsApp Order Update</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono" x-text="whatsAppData.recipientName + ' (' + (whatsAppData.orderNumber || '') + ')'"></p>
                        </div>
                    </div>
                    <button @click="isWhatsAppOpen = false" class="text-neutral-400 hover:text-white cursor-pointer">✕</button>
                </div>

                <div class="p-5 space-y-4">
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Recipient Mobile Number</label>
                        <input
                            type="text"
                            x-model="whatsAppData.recipientPhone"
                            placeholder="+91 98260 11223"
                            class="w-full px-3 py-2 border border-neutral-300 rounded-xl font-mono font-bold focus:ring-2 focus:ring-[#091315]"
                        >
                    </div>

                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Pre-Composed Message</label>
                        <textarea
                            x-model="whatsAppData.message"
                            rows="9"
                            class="w-full p-3 border border-neutral-300 rounded-xl font-mono text-[11px] leading-relaxed focus:ring-2 focus:ring-[#091315]"
                        ></textarea>
                    </div>

                    <div class="p-3.5 bg-[#F5F6F8] rounded-2xl border border-neutral-200/80 text-neutral-800 text-[11px]">
                        💡 <b>Tip:</b> Clicking <i>"Send via WhatsApp"</i> will open WhatsApp Web or Desktop with this pre-formatted message ready to send with 1 click.
                    </div>

                    <div class="pt-2 flex justify-end gap-2 border-t border-neutral-100">
                        <button type="button" @click="isWhatsAppOpen = false" class="px-4 py-2 font-bold text-neutral-600 hover:bg-neutral-100 rounded-full cursor-pointer">Cancel</button>
                        <button
                            type="button"
                            @click="launchWhatsApp()"
                            class="px-5 py-2 font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs flex items-center gap-1.5 cursor-pointer border border-[#c8f043]"
                        >
                            <span>🚀 Send via WhatsApp</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- 8. REVISION HISTORY & AUDIT DIFF MODAL -->
    <template x-teleport="body">
        <div x-show="isRevisionModalOpen" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-2 sm:p-4">
            <div @click.outside="isRevisionModalOpen = false" class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-neutral-200/80 w-full max-w-4xl overflow-hidden max-h-[92vh] flex flex-col text-xs">
                <!-- Modal Header -->
                <div class="p-4 sm:p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-2xl bg-[#D7FF53]/20 border border-[#D7FF53]/30 text-[#D7FF53] flex items-center justify-center text-lg font-bold">
                            🕒
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-extrabold text-sm tracking-tight text-white font-display" x-text="'PO Revision History: ' + (revisionData?.order_number || '')"></h3>
                                <template x-if="revisionData?.po_number && revisionData?.po_number !== revisionData?.order_number">
                                    <span class="px-2 py-0.5 rounded-full bg-neutral-800 text-[#D7FF53] font-mono text-[10px]" x-text="'Buyer PO: ' + revisionData?.po_number"></span>
                                </template>
                            </div>
                            <p class="text-[11px] text-[#D7FF53] font-mono">Immutable version timeline, quantity amendments & price change audit</p>
                        </div>
                    </div>
                    <button @click="isRevisionModalOpen = false" class="text-neutral-400 hover:text-white cursor-pointer text-base">✕</button>
                </div>

                <!-- Modal Body -->
                <div class="p-5 overflow-y-auto flex-1 space-y-5">
                    <!-- Loading state -->
                    <div x-show="revisionLoading" class="py-16 text-center space-y-3">
                        <div class="w-8 h-8 border-3 border-amber-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
                        <p class="text-slate-500 font-medium">Loading revision history & diff snapshot...</p>
                    </div>

                    <!-- Content state -->
                    <div x-show="!revisionLoading && revisionData" class="space-y-5">
                        <!-- Version Selection Tabs -->
                        <div>
                            <div class="text-[11px] uppercase font-bold text-slate-400 tracking-wider mb-2">Version Timeline</div>
                            <div class="flex flex-wrap gap-2">
                                <!-- Current Active Version Tab -->
                                <button
                                    type="button"
                                    @click="selectedRevId = 'current'"
                                    :class="selectedRevId === 'current' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200'"
                                    class="px-3.5 py-2 rounded-xl font-bold flex items-center gap-2 cursor-pointer transition-all"
                                >
                                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                    <span x-text="'Rev ' + (revisionData?.current_revision || 0) + ' (Active / Current)'"></span>
                                </button>

                                <!-- Historical Snapshots -->
                                <template x-for="(hist, hIdx) in (revisionData?.history || [])" :key="hist.id">
                                    <button
                                        type="button"
                                        @click="selectedRevId = hist.id"
                                        :class="selectedRevId === hist.id ? 'bg-amber-600 text-white shadow-xs' : 'bg-amber-50 text-amber-900 hover:bg-amber-100 border border-amber-200'"
                                        class="px-3.5 py-2 rounded-xl font-bold flex items-center gap-2 cursor-pointer transition-all"
                                    >
                                        <span>📜</span>
                                        <span x-text="'Rev ' + hist.revision_number + ' Snapshot'"></span>
                                        <span class="text-[10px] opacity-75" x-text="'(' + (hist.created_at || '') + ')'"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Selected Version Details & Reason -->
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200 pb-3">
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Selected Version</span>
                                    <h4 class="font-black text-sm text-slate-900" x-text="getSelectedVersionTitle()"></h4>
                                </div>
                                <div class="sm:text-right">
                                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Archived / Effective Date</span>
                                    <span class="font-bold text-slate-700 text-xs" x-text="getSelectedVersionDate()"></span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="p-3 bg-white rounded-xl border border-slate-200">
                                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Total Physical Volume</span>
                                    <span class="font-black text-slate-900 text-base" x-text="Number(getSelectedTotalQty()).toLocaleString('en-IN') + ' KG'"></span>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-slate-200">
                                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Total PO Value</span>
                                    <span class="font-black text-amber-600 text-base" x-text="'₹' + Number(getSelectedTotalAmount()).toLocaleString('en-IN')"></span>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-slate-200">
                                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Contract Status</span>
                                    <span class="font-bold text-slate-800 text-xs" x-text="getSelectedStatus()"></span>
                                </div>
                            </div>

                            <div class="p-3 bg-amber-50/70 rounded-xl border border-amber-200/80 text-xs">
                                <span class="font-bold text-amber-900">Reason for Amendment / Revision:</span>
                                <p class="text-amber-900 mt-0.5 italic font-medium" x-text="getSelectedReason()"></p>
                            </div>
                        </div>

                        <!-- Side-by-Side Diff Table if comparing a historical snapshot against current -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <h4 class="font-bold text-slate-900 text-xs flex items-center gap-1.5">
                                    <span>📊</span>
                                    <span x-text="selectedRevId === 'current' ? 'Current Line Items' : 'Comparison: Snapshot vs Current Active Order'"></span>
                                </h4>
                                <template x-if="selectedRevId !== 'current'">
                                    <span class="text-[11px] text-slate-500">
                                        Comparing <b class="text-amber-700" x-text="'Rev ' + (getSelectedSnapshot()?.revision_number || 0)"></b> with <b class="text-slate-900" x-text="'Rev ' + (revisionData?.current_revision || 0)"></b>
                                    </span>
                                </template>
                            </div>

                            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead>
                                        <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                            <th class="py-2.5 px-3">Product Cut</th>
                                            <template x-if="selectedRevId !== 'current'">
                                                <th class="py-2.5 px-3 text-right">Snapshot Qty</th>
                                            </template>
                                            <th class="py-2.5 px-3 text-right" x-text="selectedRevId !== 'current' ? 'Current Qty' : 'Order Qty'"></th>
                                            <template x-if="selectedRevId !== 'current'">
                                                <th class="py-2.5 px-3 text-right">Qty Diff</th>
                                            </template>
                                            <template x-if="selectedRevId !== 'current'">
                                                <th class="py-2.5 px-3 text-right">Snapshot Rate</th>
                                            </template>
                                            <th class="py-2.5 px-3 text-right" x-text="selectedRevId !== 'current' ? 'Current Rate' : 'Rate / KG'"></th>
                                            <th class="py-2.5 px-3 text-right">Line Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <template x-for="item in getComparisonItems()" :key="item.product_id">
                                            <tr class="hover:bg-slate-50/70 transition-colors">
                                                <td class="py-2.5 px-3">
                                                    <div class="font-bold text-slate-900" x-text="item.product_name"></div>
                                                    <div class="text-[10px] text-slate-400 font-mono" x-text="item.product_code"></div>
                                                </td>
                                                <template x-if="selectedRevId !== 'current'">
                                                    <td class="py-2.5 px-3 text-right font-mono text-slate-600" x-text="Number(item.snap_qty || 0).toLocaleString('en-IN') + ' KG'"></td>
                                                </template>
                                                <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900" x-text="Number(item.curr_qty || 0).toLocaleString('en-IN') + ' KG'"></td>
                                                <template x-if="selectedRevId !== 'current'">
                                                    <td class="py-2.5 px-3 text-right font-mono font-bold">
                                                        <span :class="item.qty_diff < 0 ? 'text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded' : (item.qty_diff > 0 ? 'text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded' : 'text-slate-400')" x-text="(item.qty_diff > 0 ? '+' : '') + Number(item.qty_diff || 0).toLocaleString('en-IN') + ' KG'"></span>
                                                    </td>
                                                </template>
                                                <template x-if="selectedRevId !== 'current'">
                                                    <td class="py-2.5 px-3 text-right font-mono text-slate-600" x-text="'₹' + Number(item.snap_rate || 0).toLocaleString('en-IN')"></td>
                                                </template>
                                                <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900" x-text="'₹' + Number(item.curr_rate || 0).toLocaleString('en-IN')"></td>
                                                <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900" x-text="'₹' + Number(item.curr_total || 0).toLocaleString('en-IN')"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="p-3 bg-slate-50 border-t border-slate-200 flex justify-between items-center text-xs">
                    <span class="text-slate-400">Immutable audit log · Compliant with GST & commercial accounting standards</span>
                    <button type="button" @click="isRevisionModalOpen = false" class="px-4 py-2 font-bold text-slate-700 hover:bg-slate-200 bg-slate-100 rounded-xl cursor-pointer">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- 8. RECORD ADVANCE PAYMENT MODAL -->
    <template x-teleport="body">
        <div x-show="isRecordAdvanceOpen" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-2 sm:p-4">
            <div @click.outside="isRecordAdvanceOpen = false" class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-neutral-200/80 w-full max-w-lg overflow-hidden flex flex-col text-xs max-h-[92vh]">
                <!-- Modal Header -->
                <div class="p-4 sm:p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-2xl bg-[#D7FF53]/20 border border-[#D7FF53]/30 text-[#D7FF53] flex items-center justify-center text-lg font-bold font-display">
                            ₹
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm tracking-tight text-white font-display">Record Advance Payment</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono" x-text="'PO #' + (advanceOrder.order_number || '') + ' · ' + (advanceOrder.customer?.company_name || '')"></p>
                        </div>
                    </div>
                    <button @click="isRecordAdvanceOpen = false" class="text-neutral-400 hover:text-white cursor-pointer p-1">✕</button>
                </div>

                <!-- Form -->
                <form :action="'/orders/' + advanceOrder.id + '/advance'" method="POST" class="p-4 sm:p-5 space-y-4 text-xs overflow-y-auto flex-1">
                    @csrf

                    <!-- PO Financial Context Card -->
                    <div class="p-3.5 bg-[#F5F6F8] rounded-2xl border border-neutral-200/60 grid grid-cols-1 sm:grid-cols-3 gap-2 text-center">
                        <div class="bg-white p-2.5 rounded-xl border border-neutral-200/80">
                            <span class="text-[10px] uppercase font-bold text-neutral-400 block font-mono">Contract Total</span>
                            <p class="font-black text-xs text-neutral-900 font-display mt-0.5" x-text="'₹' + Number(advanceOrder.total_amount || 0).toLocaleString('en-IN')"></p>
                        </div>
                        <div class="bg-white p-2.5 rounded-xl border border-neutral-200/80">
                            <span class="text-[10px] uppercase font-bold text-neutral-400 block font-mono">Advance Paid</span>
                            <p class="font-black text-xs text-emerald-600 font-display mt-0.5" x-text="'₹' + Number(advanceOrder.advance_received || 0).toLocaleString('en-IN')"></p>
                        </div>
                        <div class="bg-white p-2.5 rounded-xl border border-neutral-200/80">
                            <span class="text-[10px] uppercase font-bold text-neutral-400 block font-mono">Contract Bal</span>
                            <p class="font-black text-xs text-neutral-900 font-display mt-0.5" x-text="'₹' + Math.max(0, Number((advanceOrder.total_amount || 0) - (advanceOrder.advance_received || 0))).toLocaleString('en-IN')"></p>
                        </div>
                    </div>

                    <!-- Amount & Receipt Date -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Advance Amount (₹) *</label>
                            <input type="number" step="0.01" min="0.01" name="amount_received" x-model="advanceAmount" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl font-mono font-bold focus:ring-2 focus:ring-[#091315] focus:outline-hidden text-sm" required>
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Payment Receipt Date *</label>
                            <input type="date" name="receipt_date" x-model="advanceDate" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden font-medium" required>
                        </div>
                    </div>

                    <!-- Payment Mode & Reference Number -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Payment Mode *</label>
                            <select name="payment_mode" x-model="advanceMode" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden font-medium">
                                <option value="RTGS / Bank Transfer">RTGS / Bank Transfer</option>
                                <option value="NEFT">NEFT</option>
                                <option value="Cheque">Cheque</option>
                                <option value="UPI / Digital">UPI / Digital Transfer</option>
                                <option value="Cash">Cash</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Bank Reference / UTR No.</label>
                            <input type="text" name="reference_number" x-model="advanceRef" placeholder="e.g. UTR1234567890 / Chq #889" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl font-mono focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                    </div>

                    <!-- Notes / Remarks -->
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Remarks</label>
                        <input type="text" name="remarks" x-model="advanceRemarks" placeholder="e.g. 50% advance for garlic processing" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                    </div>

                    <div class="p-3 bg-emerald-50 rounded-2xl border border-emerald-200 text-emerald-900 space-y-0.5">
                        <span class="font-bold text-[11px] block">✓ Automatic Tax Invoice Offset</span>
                        <p class="text-[10px] text-emerald-800 leading-relaxed">
                            This advance will update the PO balance immediately and will be automatically deducted from the Tax Invoice when goods are dispatched.
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-3 border-t border-neutral-100 flex justify-end gap-2 shrink-0">
                        <button type="button" @click="isRecordAdvanceOpen = false" class="px-4 py-2 font-bold text-neutral-600 hover:bg-neutral-100 rounded-full cursor-pointer text-xs">Cancel</button>
                        <button type="submit" class="px-5 py-2 font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs cursor-pointer transition-all border border-[#c8f043] text-xs active:scale-95">
                            Confirm & Record Advance
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

</div>

<script>
    function ordersPageApp() {
        return {
            activeTab: 'ALL',
            searchQuery: '',
            customerFilter: '',
            isNewOrderOpen: {{ session('requires_duplicate_confirmation') || $errors->any() ? 'true' : 'false' }},
            isEditOrderOpen: false,
            isRecordAdvanceOpen: false,
            advanceOrder: {},
            advanceAmount: '',
            advanceMode: 'RTGS / Bank Transfer',
            advanceRef: '',
            advanceDate: '{{ date("Y-m-d") }}',
            advanceRemarks: '',
            isWhatsAppOpen: false,
            isRevisionModalOpen: false,
            revisionLoading: false,
            revisionData: null,
            selectedRevId: 'current',
            editRevisionReason: '',
            editOrder: {},
            editOrderDate: '',
            firstProductId: '{{ $products->first()?->id ?? "" }}',
            firstProductRate: {{ $products->first()?->standard_rate ?? 120 }},
            newItems: [],
            editItems: [],
            whatsAppData: {
                recipientName: '',
                recipientPhone: '',
                orderNumber: '',
                message: ''
            },

            openRecordAdvanceModal(order) {
                this.advanceOrder = order;
                const req = parseFloat(order.advance_required || 0);
                const rec = parseFloat(order.advance_received || 0);
                const total = parseFloat(order.total_amount || 0);
                const remainingAdv = Math.max(0, req - rec);
                const remainingBal = Math.max(0, total - rec);
                const defaultAmt = remainingAdv > 0 ? remainingAdv : remainingBal;
                this.advanceAmount = defaultAmt > 0 ? defaultAmt : '';
                this.advanceDate = '{{ date("Y-m-d") }}';
                this.advanceMode = 'RTGS / Bank Transfer';
                this.advanceRef = '';
                this.advanceRemarks = `Advance payment against PO #${order.order_number}`;
                this.isRecordAdvanceOpen = true;
            },

            matchesFilter(statusCategory, customerId, keywords) {
                // Tab status check
                if (this.activeTab !== 'ALL' && this.activeTab !== statusCategory) {
                    return false;
                }

                // Customer dropdown check
                if (this.customerFilter && this.customerFilter !== '' && String(this.customerFilter) !== String(customerId)) {
                    return false;
                }

                // Search query check
                if (this.searchQuery.trim() !== '') {
                    const q = this.searchQuery.toLowerCase().trim();
                    if (!keywords.includes(q)) {
                        return false;
                    }
                }

                return true;
            },

            openNewOrderModal() {
                this.newItems = [
                    { product_id: this.firstProductId, order_qty: 5000, rate: this.firstProductRate }
                ];
                this.isNewOrderOpen = true;
            },

            addItemRow() {
                this.newItems.push({
                    product_id: this.firstProductId,
                    order_qty: 2000,
                    rate: this.firstProductRate
                });
            },

            removeItemRow(idx) {
                if (this.newItems.length > 1) {
                    this.newItems.splice(idx, 1);
                }
            },

            openEditOrderModal(order) {
                this.editOrder = order;
                this.editRevisionReason = '';
                this.editOrderDate = order.order_date ? order.order_date.substring(0, 10) : '{{ date("Y-m-d") }}';
                this.editItems = order.items.map(it => ({
                    id: it.id,
                    product_id: it.product_id,
                    order_qty: parseFloat(it.order_qty),
                    rate: parseFloat(it.rate),
                    shipped_qty: parseFloat(it.shipped_qty || 0)
                }));
                this.isEditOrderOpen = true;
            },

            addEditItemRow() {
                this.editItems.push({
                    product_id: this.firstProductId,
                    order_qty: 1000,
                    rate: this.firstProductRate,
                    shipped_qty: 0
                });
            },

            removeEditItemRow(idx) {
                if (this.editItems.length > 1) {
                    this.editItems.splice(idx, 1);
                }
            },

            onProductSelect(item, event) {
                const opt = event.target.selectedOptions[0];
                if (opt && opt.getAttribute('data-rate')) {
                    item.rate = parseFloat(opt.getAttribute('data-rate'));
                }
            },

            calculateTotal(items) {
                return items.reduce((sum, it) => sum + ((parseFloat(it.order_qty) || 0) * (parseFloat(it.rate) || 0)), 0);
            },

            calculateTotalWeight(items) {
                const totalKg = items.reduce((sum, it) => sum + (parseFloat(it.order_qty) || 0), 0);
                if (totalKg >= 1000) {
                    return (totalKg / 1000).toFixed(2) + ' MT (' + totalKg.toLocaleString('en-IN') + ' KG)';
                }
                return totalKg.toLocaleString('en-IN') + ' KG';
            },

            openWhatsAppDrawer(order) {
                const cust = order.customer || {};
                const contactPerson = cust.contact_person || cust.company_name || 'Valued Client';
                const phone = cust.phone || cust.mobile || '+919826011223';
                const orderNum = order.order_number;

                const totalKg = order.items.reduce((sum, it) => sum + parseFloat(it.order_qty), 0);
                const shippedKg = order.items.reduce((sum, it) => sum + parseFloat(it.shipped_qty), 0);
                const balanceKg = order.items.reduce((sum, it) => sum + parseFloat(it.balance_qty), 0);
                const pct = totalKg > 0 ? Math.round((shippedKg / totalKg) * 100) : 0;

                let linesSummary = '';
                order.items.forEach(it => {
                    const pName = it.product ? it.product.product_name : 'Product';
                    linesSummary += `• ${pName}: ${Number(it.shipped_qty).toLocaleString('en-IN')}/${Number(it.order_qty).toLocaleString('en-IN')} KG (${it.balance_qty <= 0 ? 'Fulfilled ✓' : Number(it.balance_qty).toLocaleString('en-IN') + ' KG Balance'})\n`;
                });

                const msg = `Hi ${contactPerson},\n\nSharing the dispatch fulfillment update for Purchase Order *${orderNum}*:\n\n📦 *Order Volume:* ${(totalKg/1000).toFixed(2)} MT (${Number(totalKg).toLocaleString('en-IN')} KG)\n🚚 *Dispatched to Date:* ${(shippedKg/1000).toFixed(2)} MT (${pct}% Fulfilled)\n⏳ *Pending Balance:* ${(balanceKg/1000).toFixed(2)} MT (${Number(balanceKg).toLocaleString('en-IN')} KG)\n\n*Product Breakdown:*\n${linesSummary}\nPlease let me know your preferred delivery schedule for the remaining lots.`;

                this.whatsAppData = {
                    recipientName: cust.company_name,
                    recipientPhone: phone,
                    orderNumber: orderNum,
                    message: msg
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

            openRevisionHistoryModal(orderId) {
                this.isRevisionModalOpen = true;
                this.revisionLoading = true;
                this.selectedRevId = 'current';
                this.revisionData = null;

                fetch(`/orders/${orderId}/revisions`)
                    .then(res => {
                        if (!res.ok) throw new Error('Failed to fetch revisions');
                        return res.json();
                    })
                    .then(data => {
                        this.revisionData = data;
                        if (data.history && data.history.length > 0) {
                            this.selectedRevId = data.history[0].id;
                        }
                        this.revisionLoading = false;
                    })
                    .catch(err => {
                        console.error(err);
                        alert('Could not load revision history.');
                        this.revisionLoading = false;
                        this.isRevisionModalOpen = false;
                    });
            },

            getSelectedSnapshot() {
                if (!this.revisionData || !this.revisionData.history) return null;
                return this.revisionData.history.find(h => h.id === this.selectedRevId) || null;
            },

            getSelectedVersionTitle() {
                if (this.selectedRevId === 'current') {
                    return 'Rev ' + (this.revisionData?.current_revision || 0) + ' (Active Order)';
                }
                const snap = this.getSelectedSnapshot();
                return 'Rev ' + (snap ? snap.revision_number : 0) + ' Historical Snapshot (Archived)';
            },

            getSelectedVersionDate() {
                if (this.selectedRevId === 'current') {
                    return this.revisionData?.current?.last_revised_at || 'Current Active';
                }
                const snap = this.getSelectedSnapshot();
                return snap ? snap.created_at : 'Archived';
            },

            getSelectedReason() {
                if (this.selectedRevId === 'current') {
                    return this.revisionData?.current?.last_revision_reason || 'Initial order creation / active state';
                }
                const snap = this.getSelectedSnapshot();
                return snap ? snap.revision_reason : 'Amendment';
            },

            getSelectedTotalQty() {
                if (this.selectedRevId === 'current') {
                    return this.revisionData?.current?.total_qty || 0;
                }
                const snap = this.getSelectedSnapshot();
                return snap?.snapshot?.total_qty || 0;
            },

            getSelectedTotalAmount() {
                if (this.selectedRevId === 'current') {
                    return this.revisionData?.current?.total_amount || 0;
                }
                const snap = this.getSelectedSnapshot();
                return snap?.snapshot?.total_amount || 0;
            },

            getSelectedStatus() {
                if (this.selectedRevId === 'current') {
                    return this.revisionData?.current?.status || 'CONFIRMED';
                }
                const snap = this.getSelectedSnapshot();
                return snap?.snapshot?.status || 'ARCHIVED';
            },

            getComparisonItems() {
                if (!this.revisionData) return [];

                if (this.selectedRevId === 'current') {
                    return (this.revisionData.current?.items || []).map(it => ({
                        product_id: it.product_id,
                        product_name: it.product_name,
                        product_code: it.product_code,
                        curr_qty: parseFloat(it.order_qty) || 0,
                        curr_rate: parseFloat(it.rate) || 0,
                        curr_total: (parseFloat(it.order_qty) || 0) * (parseFloat(it.rate) || 0),
                        snap_qty: 0,
                        snap_rate: 0,
                        qty_diff: 0,
                        rate_diff: 0,
                    }));
                }

                const snap = this.getSelectedSnapshot();
                const snapItems = (snap && snap.snapshot && snap.snapshot.items) ? snap.snapshot.items : [];
                const currItems = (this.revisionData.current && this.revisionData.current.items) ? this.revisionData.current.items : [];

                const map = {};
                snapItems.forEach(it => {
                    const pId = it.product_id;
                    map[pId] = {
                        product_id: pId,
                        product_name: it.product_name || 'Product',
                        product_code: it.product_code || '',
                        snap_qty: parseFloat(it.order_qty) || 0,
                        snap_rate: parseFloat(it.rate) || 0,
                        curr_qty: 0,
                        curr_rate: 0,
                    };
                });

                currItems.forEach(it => {
                    const pId = it.product_id;
                    if (!map[pId]) {
                        map[pId] = {
                            product_id: pId,
                            product_name: it.product_name || 'Product',
                            product_code: it.product_code || '',
                            snap_qty: 0,
                            snap_rate: 0,
                            curr_qty: 0,
                            curr_rate: 0,
                        };
                    }
                    map[pId].curr_qty = parseFloat(it.order_qty) || 0;
                    map[pId].curr_rate = parseFloat(it.rate) || 0;
                    if (!map[pId].product_name || map[pId].product_name === 'Product') {
                        map[pId].product_name = it.product_name;
                    }
                });

                return Object.values(map).map(row => {
                    row.qty_diff = row.curr_qty - row.snap_qty;
                    row.rate_diff = row.curr_rate - row.snap_rate;
                    row.curr_total = row.curr_qty * row.curr_rate;
                    return row;
                });
            }
        }
    }
</script>
@endsection