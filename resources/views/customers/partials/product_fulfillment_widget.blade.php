@php
    $productSummary = $customer->product_fulfillment_summary;
    $totalItemsCount = count($productSummary);
    $totalOrdered = $productSummary->sum('total_ordered');
    $totalShipped = $productSummary->sum('total_shipped');
    $totalPending = $productSummary->sum('total_pending');
    $overallPct = $totalOrdered > 0 ? min(100.0, round(($totalShipped / $totalOrdered) * 100, 1)) : 100.0;
@endphp

<div class="bg-[#FAFAFA] rounded-2xl sm:rounded-3xl border border-neutral-200/80 p-4 sm:p-6 shadow-2xs space-y-4">
    <!-- Header with Title & Summary Metrics -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-3 border-b border-neutral-200/70">
        <div>
            <div class="flex items-center gap-2.5 flex-wrap">
                <span class="text-lg">📦</span>
                <h4 class="font-extrabold text-sm sm:text-base text-neutral-900 font-display tracking-tight">
                    Product-Wise Orders & Fulfillment
                </h4>
                <span class="font-mono text-[11px] font-bold px-2 py-0.5 rounded-full bg-white text-neutral-700 border border-neutral-200/80 shadow-2xs">
                    {{ $totalItemsCount }} {{ Str::plural('Product', $totalItemsCount) }}
                </span>
                @if($totalPending > 0)
                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200/80 flex items-center gap-1 font-mono">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        {{ number_format($totalPending) }} KG Pending Supply
                    </span>
                @elseif($totalItemsCount > 0)
                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200/80 flex items-center gap-1 font-mono">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        100% All Fulfilled
                    </span>
                @endif
            </div>
            <p class="text-xs text-neutral-500 mt-1 font-medium">
                Consolidated client requirements, dispatched volume, and outstanding balances across all active Sales Orders.
            </p>
        </div>

        @if($totalItemsCount > 0)
            <div class="flex items-center gap-2 text-center shrink-0">
                <div class="bg-white px-3 py-1.5 rounded-xl border border-neutral-200/80 shadow-2xs">
                    <span class="text-[9px] font-bold text-neutral-400 uppercase font-mono block">Ordered</span>
                    <span class="text-xs font-black text-neutral-900 font-display">{{ number_format($totalOrdered) }} <span class="text-[9px] font-normal text-neutral-500">KG</span></span>
                </div>
                <div class="bg-white px-3 py-1.5 rounded-xl border border-neutral-200/80 shadow-2xs">
                    <span class="text-[9px] font-bold text-emerald-600 uppercase font-mono block">Shipped</span>
                    <span class="text-xs font-black text-emerald-600 font-display">{{ number_format($totalShipped) }} <span class="text-[9px] font-normal text-emerald-500">KG</span></span>
                </div>
                <div class="bg-white px-3 py-1.5 rounded-xl border border-neutral-200/80 shadow-2xs">
                    <span class="text-[9px] font-bold {{ $totalPending > 0 ? 'text-amber-700' : 'text-neutral-400' }} uppercase font-mono block">Pending</span>
                    <span class="text-xs font-black {{ $totalPending > 0 ? 'text-amber-600' : 'text-neutral-400' }} font-display">{{ number_format($totalPending) }} <span class="text-[9px] font-normal text-neutral-400">KG</span></span>
                </div>
            </div>
        @endif
    </div>

    <!-- Product Summary Content (Zero-Table Card Grid) -->
    @if($totalItemsCount === 0)
        <div class="py-10 text-center space-y-2 bg-white rounded-2xl border border-dashed border-neutral-200">
            <div class="text-3xl">📦</div>
            <p class="font-bold text-sm text-neutral-800 font-display">No product orders recorded yet</p>
            <p class="text-xs text-neutral-500 max-w-sm mx-auto">
                Once Sales Orders are booked for this client, a live product-wise breakdown of ordered, shipped, and pending quantities will appear here.
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($productSummary as $item)
                @php
                    $isComplete = $item->status === 'COMPLETED';
                    $isPartial = $item->status === 'PARTIAL';
                    $uom = $item->uom ?: 'KG';
                @endphp
                <div class="bg-white rounded-2xl border border-neutral-200/80 p-4 shadow-2xs hover:border-neutral-300 transition-all space-y-3">
                    <!-- Product Top Header -->
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h5 class="font-bold text-sm text-neutral-900 font-display">{{ $item->product_name }}</h5>
                            <div class="flex items-center gap-2 mt-0.5 text-neutral-400 text-[11px]">
                                @if(!empty($item->product_code))
                                    <span class="font-mono text-[10px] font-semibold px-1.5 py-0.5 rounded bg-neutral-100 text-neutral-600 border border-neutral-200/70">
                                        {{ $item->product_code }}
                                    </span>
                                @endif
                                <span class="font-mono">{{ $item->orders_count }} {{ Str::plural('Order', $item->orders_count) }}</span>
                                <span>·</span>
                                <span>Unit: <b class="text-neutral-700 font-mono">{{ $uom }}</b></span>
                            </div>
                        </div>

                        <span class="px-2.5 py-1 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider shrink-0 {{ $isComplete ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : ($isPartial ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-neutral-100 text-neutral-700 border border-neutral-200') }}">
                            {{ $isComplete ? '✓ Fulfilled' : ($isPartial ? '⚡ Partial' : '⏳ Pending') }}
                        </span>
                    </div>

                    <!-- Progress Bar -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-[11px] font-mono">
                            <span class="text-neutral-500 font-medium">Supply Progress:</span>
                            <span class="font-bold {{ $isComplete ? 'text-emerald-700' : 'text-neutral-800' }}">{{ $item->fulfillment_percentage }}%</span>
                        </div>
                        <div class="w-full h-2 bg-neutral-100 rounded-full overflow-hidden border border-neutral-200/60 p-0.5">
                            <div
                                class="h-full rounded-full transition-all duration-500 {{ $isComplete ? 'bg-emerald-500' : ($isPartial ? 'bg-amber-500' : 'bg-neutral-300') }}"
                                style="width: {{ $item->fulfillment_percentage }}%"
                            ></div>
                        </div>
                    </div>

                    <!-- 3-Column Metrics -->
                    <div class="grid grid-cols-3 gap-2 text-center text-xs font-mono pt-1">
                        <div class="bg-[#F5F6F8] p-2 rounded-xl border border-neutral-200/60">
                            <span class="text-[9px] uppercase font-bold text-neutral-400 block">Ordered</span>
                            <span class="font-black text-neutral-900 text-xs mt-0.5 block">{{ number_format($item->total_ordered) }} <span class="text-[9px] font-normal text-neutral-400">{{ $uom }}</span></span>
                        </div>
                        <div class="bg-[#F5F6F8] p-2 rounded-xl border border-neutral-200/60">
                            <span class="text-[9px] uppercase font-bold text-emerald-600 block">Shipped</span>
                            <span class="font-black text-emerald-600 text-xs mt-0.5 block">{{ number_format($item->total_shipped) }} <span class="text-[9px] font-normal text-emerald-500">{{ $uom }}</span></span>
                        </div>
                        <div class="p-2 rounded-xl border {{ $item->total_pending > 0 ? 'bg-amber-50/60 border-amber-200/80' : 'bg-[#F5F6F8] border-neutral-200/60' }}">
                            <span class="text-[9px] uppercase font-bold {{ $item->total_pending > 0 ? 'text-amber-700' : 'text-neutral-400' }} block">Pending</span>
                            <span class="font-black text-xs mt-0.5 block {{ $item->total_pending > 0 ? 'text-amber-600' : 'text-neutral-400' }}">{{ number_format($item->total_pending) }} <span class="text-[9px] font-normal text-neutral-400">{{ $uom }}</span></span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
