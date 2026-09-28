@extends('layouts.app')

@section('title', 'Production Batches & Raw Material Consumption')

@section('content')
<div class="space-y-6" x-data="productionPageApp()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-neutral-200/80 shadow-xs">
        <div>
            <h2 class="text-xl font-black text-neutral-900">Production Batches & Godown Stocks</h2>
            <p class="text-xs text-neutral-500 mt-1">
                Real-time stock tracking across Garlic and Onion products, moisture QA test logs, and batch availability.
            </p>
        </div>

        <button
            @click="isNewBatchOpen = true"
            class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-[#f53003] hover:bg-[#c42602] shadow-xs transition-colors cursor-pointer shrink-0"
        >
            + Register Production Batch
        </button>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-slate-200 shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Finished Goods</span>
            <p class="text-2xl font-black text-slate-900 mt-1">{{ number_format($totalStockKg) }} KG</p>
            <p class="text-[11px] text-emerald-600 font-bold mt-0.5">Stored in Bhavnagar Plant Godowns</p>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-slate-200 shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Active QA Batches</span>
            <p class="text-2xl font-black text-brand-600 mt-1">{{ count($batches) }} Batches</p>
            <p class="text-[11px] text-slate-500 font-bold mt-0.5">Export Grade A+ Quality Approved</p>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-slate-200 shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Low Stock Threshold Alerts</span>
            <p class="text-2xl font-black {{ $lowStockCount > 0 ? 'text-amber-600' : 'text-emerald-600' }} mt-1">{{ $lowStockCount }} SKUs</p>
            <p class="text-[11px] text-slate-500 font-bold mt-0.5">Threshold: 5,000 KG min</p>
        </div>
    </div>

    <!-- Inventory Stocks Table -->
    <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex items-center justify-between">
            <h3 class="font-bold text-sm text-slate-900">Current Godown Inventory Stock by Product</h3>
            <span class="text-xs text-slate-500">Live stock balance</span>
        </div>
        <div class="overflow-x-auto touch-scroll">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-slate-700 border-b border-slate-200">
                        <th class="p-3.5 font-bold">SKU Code</th>
                        <th class="p-3.5 font-bold">Product Cut / Granulation</th>
                        <th class="p-3.5 font-bold">Category</th>
                        <th class="p-3.5 font-bold">Godown Location</th>
                        <th class="p-3.5 font-bold text-right">Available Stock</th>
                        <th class="p-3.5 font-bold text-right">Standard Rate / KG</th>
                        <th class="p-3.5 font-bold text-center">Stock Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($stocks as $st)
                        <tr class="hover:bg-amber-50/40 transition-colors">
                            <td class="p-3.5 font-mono font-bold text-slate-900">{{ $st->product->product_code }}</td>
                            <td class="p-3.5 font-bold text-slate-900">{{ $st->product->product_name }}</td>
                            <td class="p-3.5">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase {{ $st->product->category?->slug === 'garlic' ? 'bg-amber-100 text-amber-900' : 'bg-rose-100 text-rose-900' }}">
                                    {{ $st->product->category?->name ?? 'Dehydrated' }}
                                </span>
                            </td>
                            <td class="p-3.5 text-slate-600">{{ $st->godown_location }}</td>
                            <td class="p-3.5 text-right font-black text-slate-900 text-sm">
                                {{ number_format($st->current_stock_qty) }} KG
                            </td>
                            <td class="p-3.5 text-right text-slate-700 font-medium">
                                {{ formatINR($st->product->standard_rate) }}
                            </td>
                            <td class="p-3.5 text-center">
                                @if($st->current_stock_qty > $st->min_threshold_qty)
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 uppercase">Sufficient</span>
                                @else
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-100 text-amber-800 uppercase">Reorder Soon</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Register Batch Modal -->
    <template x-teleport="body">
        <div x-show="isNewBatchOpen" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-2 sm:p-4">
            <div @click.outside="isNewBatchOpen = false" class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-neutral-200/80 w-full max-w-lg max-h-[92vh] overflow-hidden flex flex-col">
                <div class="p-4 sm:p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-2xl bg-[#D7FF53]/20 border border-[#D7FF53]/30 text-[#D7FF53] flex items-center justify-center font-bold text-sm">
                            ⚙️
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm tracking-tight text-white font-display">Register Production Batch</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono">Quality-Approved Dehydration Run</p>
                        </div>
                    </div>
                    <button @click="isNewBatchOpen = false" class="text-neutral-400 hover:text-white cursor-pointer transition-colors p-1">✕</button>
                </div>
                <form action="{{ route('production.batch.store') }}" method="POST" class="p-4 sm:p-5 space-y-3 text-xs overflow-y-auto flex-1 touch-scroll">
                    @csrf
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Product Cut</label>
                        <select name="product_id" class="w-full px-3 py-2 border border-neutral-200 bg-white rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden" required>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->product_name }} ({{ $p->product_code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Batch Output Qty (KG)</label>
                            <input type="number" name="batch_qty" value="5000" class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden" required>
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Moisture % (QA Test)</label>
                            <input type="number" step="0.1" name="moisture_percentage" value="4.8" class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden" required>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Sensory Grade</label>
                            <input type="text" name="sensory_grade" value="Premium Export Grade A+" class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Raw Material Lot No.</label>
                            <input type="text" name="raw_lot_number" placeholder="LOT-BHAV-0826" class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                    </div>
                    <div class="pt-3 border-t border-neutral-100 flex justify-end gap-2">
                        <button type="button" @click="isNewBatchOpen = false" class="px-5 py-2 font-bold text-neutral-600 hover:bg-neutral-100 rounded-full transition-colors cursor-pointer">Cancel</button>
                        <button type="submit" class="px-5 py-2 font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs border border-[#c8f043] transition-all cursor-pointer">Register Batch & Add Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

<script>
    function productionPageApp() {
        return {
            isNewBatchOpen: false
        }
    }
</script>
@endsection
