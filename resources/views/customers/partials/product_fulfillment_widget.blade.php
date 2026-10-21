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

    <!-- Product Summary Content -->
    @if($totalItemsCount === 0)
        <div class="py-10 text-center space-y-2 bg-white rounded-2xl border border-dashed border-neutral-200">
            <div class="text-3xl">📦</div>
            <p class="font-bold text-sm text-neutral-800 font-display">No product orders recorded yet</p>
            <p class="text-xs text-neutral-500 max-w-sm mx-auto">
                Once Sales Orders are booked for this client, a live product-wise breakdown of ordered, shipped, and pending quantities will appear here.
            </p>
        </div>
    @else
        <div class="overflow-x-auto rounded-2xl border border-neutral-200/80 bg-white shadow-2xs">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#F5F6F8] text-neutral-600 border-b border-neutral-200/80 uppercase text-[10px] tracking-wider font-mono">
                        <th class="py-3 px-4 font-bold">Product / Grade</th>
                        <th class="py-3 px-3 font-bold text-center">Orders</th>
                        <th class="py-3 px-4 font-bold text-right">Total Ordered</th>
                        <th class="py-3 px-4 font-bold text-right">Shipped / Dispatched</th>
                        <th class="py-3 px-4 font-bold text-right">Pending to Supply</th>
                        <th class="py-3 px-4 font-bold text-center min-w-[140px]">Fulfillment Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach($productSummary as $item)
                        @php
                            $isComplete = $item->status === 'COMPLETED';
                            $isPartial = $item->status === 'PARTIAL';
                            $uom = $item->uom ?: 'KG';
                        @endphp
                        <tr class="hover:bg-neutral-50/70 transition-colors">
                            <!-- Product Name & Identifiers -->
                            <td class="py-3 px-4">
                                <div class="font-bold text-neutral-900 font-display text-sm">
                                    {{ $item->product_name }}
                                </div>
                                <div class="flex items-center gap-2 mt-0.5 text-neutral-400 text-[11px] font-sans">
                                    @if(!empty($item->product_code))
                                        <span class="font-mono text-[10px] font-semibold px-1.5 py-0.2 rounded bg-neutral-100 text-neutral-600 border border-neutral-200/70">
                                            {{ $item->product_code }}
                                        </span>
                                    @endif
                                    <span>Unit: <b class="text-neutral-700 font-mono">{{ $uom }}</b></span>
                                </div>
                            </td>

                            <!-- Order Count -->
                            <td class="py-3 px-3 text-center whitespace-nowrap">
                                <span class="font-mono text-xs font-semibold px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-700">
                                    {{ $item->orders_count }} {{ Str::plural('PO', $item->orders_count) }}
                                </span>
                            </td>

                            <!-- Total Ordered -->
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <div class="font-mono font-bold text-neutral-900 text-xs">
                                    {{ number_format($item->total_ordered) }} <span class="text-[10px] font-normal text-neutral-500">{{ $uom }}</span>
                                </div>
                                @if(strtoupper($uom) === 'KG' && $item->total_ordered >= 1000)
                                    <div class="text-[10px] text-neutral-400 font-mono font-medium">
                                        {{ number_format(round($item->total_ordered / 1000, 2), 2) }} MT
                                    </div>
                                @endif
                            </td>

                            <!-- Total Shipped -->
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <div class="font-mono font-bold text-emerald-600 text-xs">
                                    {{ number_format($item->total_shipped) }} <span class="text-[10px] font-normal text-emerald-500">{{ $uom }}</span>
                                </div>
                                @if(strtoupper($uom) === 'KG' && $item->total_shipped >= 1000)
                                    <div class="text-[10px] text-emerald-500 font-mono font-medium">
                                        {{ number_format(round($item->total_shipped / 1000, 2), 2) }} MT
                                    </div>
                                @endif
                            </td>

                            <!-- Pending Balance -->
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                @if($item->total_pending > 0)
                                    <div class="inline-flex flex-col items-end">
                                        <span class="font-mono font-black text-amber-600 text-xs px-2 py-0.5 rounded-md bg-amber-50 border border-amber-200/80">
                                            {{ number_format($item->total_pending) }} {{ $uom }}
                                        </span>
                                        @if(strtoupper($uom) === 'KG' && $item->total_pending >= 1000)
                                            <span class="text-[10px] text-amber-700 font-mono font-medium mt-0.5">
                                                ({{ number_format(round($item->total_pending / 1000, 2), 2) }} MT)
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="font-mono font-bold text-emerald-700 text-xs px-2 py-0.5 rounded-md bg-emerald-50 border border-emerald-200/80 inline-flex items-center gap-1">
                                        <span>✓</span>
                                        <span>0 {{ $uom }}</span>
                                    </span>
                                @endif
                            </td>

                            <!-- Progress & Status -->
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <div class="flex flex-col items-center gap-1">
                                    <div class="flex items-center justify-between w-full max-w-[130px] text-[10px] font-mono">
                                        <span class="font-bold {{ $isComplete ? 'text-emerald-700' : 'text-neutral-700' }}">
                                            {{ $item->fulfillment_percentage }}%
                                        </span>
                                        <span class="text-[9px] uppercase font-bold tracking-tight {{ $isComplete ? 'text-emerald-700' : ($isPartial ? 'text-amber-700' : 'text-neutral-500') }}">
                                            {{ $isComplete ? 'Completed' : ($isPartial ? 'Partial' : 'Pending') }}
                                        </span>
                                    </div>
                                    <div class="w-full max-w-[130px] h-2 bg-neutral-100 rounded-full overflow-hidden p-0.5 border border-neutral-200/60">
                                        <div
                                            class="h-full rounded-full transition-all duration-500 {{ $isComplete ? 'bg-emerald-500' : ($isPartial ? 'bg-amber-500' : 'bg-neutral-300') }}"
                                            style="width: {{ $item->fulfillment_percentage }}%"
                                        ></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                @if($totalItemsCount > 1)
                    <tfoot>
                        <tr class="bg-[#F9FAFB] border-t-2 border-neutral-200 font-bold text-xs text-neutral-900">
                            <td class="py-3 px-4 font-display">
                                Total ({{ $totalItemsCount }} Products)
                            </td>
                            <td class="py-3 px-3 text-center font-mono">
                                {{ $productSummary->sum('orders_count') }} POs
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-black">
                                {{ number_format($totalOrdered) }} KG
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-black text-emerald-600">
                                {{ number_format($totalShipped) }} KG
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-black">
                                <span class="{{ $totalPending > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                                    {{ number_format($totalPending) }} KG
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center font-mono text-[11px]">
                                <span class="{{ $overallPct >= 100 ? 'text-emerald-700 font-bold' : 'text-neutral-700' }}">
                                    {{ $overallPct }}% Fulfilled
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    @endif
</div>
