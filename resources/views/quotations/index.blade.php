@extends('layouts.app')

@section('title', 'Quotations & Estimates')

@section('content')
<div class="space-y-6" x-data="quotationsPageApp()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-neutral-200/80 shadow-xs">
        <div>
            <h2 class="text-xl font-black text-neutral-900">Quotation Generator & History</h2>
            <p class="text-xs text-neutral-500 mt-1">
                Create formal commercial price quotations with packaging specs, GST rates, freight terms, and WhatsApp sharing.
            </p>
        </div>

        <button
            @click="isNewQuoteOpen = true"
            class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-[#f53003] hover:bg-[#c42602] shadow-xs transition-colors cursor-pointer"
        >
            + Create New Quotation
        </button>
    </div>

    <!-- Quotations Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-slate-700 border-b border-slate-200">
                        <th class="p-3.5 font-bold">Quote No</th>
                        <th class="p-3.5 font-bold">Date</th>
                        <th class="p-3.5 font-bold">Recipient / Client</th>
                        <th class="p-3.5 font-bold">Items Quoted</th>
                        <th class="p-3.5 font-bold">Payment & Freight Terms</th>
                        <th class="p-3.5 font-bold text-right">Total Amount (Inc GST)</th>
                        <th class="p-3.5 font-bold text-center">Status</th>
                        <th class="p-3.5 font-bold text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($quotations as $q)
                        <tr class="hover:bg-amber-50/40 transition-colors">
                            <td class="p-3.5 font-mono font-bold text-slate-900">{{ $q->quotation_number }}</td>
                            <td class="p-3.5 text-slate-600">{{ $q->quotation_date->format('d M Y') }}</td>
                            <td class="p-3.5">
                                <span class="font-bold text-slate-900 block">{{ $q->recipient_company }}</span>
                                <span class="text-[11px] text-slate-500">Attn: {{ $q->recipient_name ?? 'Purchase Head' }}</span>
                            </td>
                            <td class="p-3.5">
                                @foreach($q->items as $it)
                                    <div class="font-medium text-slate-800">{{ $it->product->product_name ?? 'Dehydrated cut' }}: <b>{{ number_format($it->quantity) }} kg @ ₹{{ $it->rate }}</b></div>
                                @endforeach
                            </td>
                            <td class="p-3.5 text-slate-600">
                                <div>{{ $q->payment_terms }}</div>
                                <div class="text-[11px] text-slate-500">{{ $q->freight_terms }}</div>
                            </td>
                            <td class="p-3.5 text-right font-black text-slate-900 text-sm">
                                {{ formatINR($q->total_amount) }}
                            </td>
                            <td class="p-3.5 text-center">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 uppercase">{{ $q->status }}</span>
                            </td>
                            <td class="p-3.5 text-right flex items-center justify-end gap-2">
                                <a href="{{ route('quotations.show', $q->id) }}" class="px-3 py-1.5 rounded-lg text-xs font-bold text-brand-600 hover:text-brand-800 bg-brand-50 hover:bg-brand-100">
                                    View / Print →
                                </a>
                                @if($q->status !== 'ACCEPTED')
                                <form action="{{ route('quotations.convert', $q->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" onclick="return confirm('Convert this Quotation to a live Sales Order?')" class="px-3 py-1.5 rounded-lg text-xs font-bold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] border border-[#c8f043] shadow-xs cursor-pointer">
                                        Convert to Order
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Quotation Modal -->
    <template x-teleport="body">
        <div x-show="isNewQuoteOpen" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-4">
            <div @click.outside="isNewQuoteOpen = false" class="bg-white rounded-3xl shadow-2xl border border-neutral-200/80 w-full max-w-2xl overflow-hidden max-h-[90vh] flex flex-col">
                <div class="p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-2xl bg-[#D7FF53]/20 border border-[#D7FF53]/30 text-[#D7FF53] flex items-center justify-center font-bold text-sm">
                            📄
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm tracking-tight text-white font-display">Create Commercial Quotation</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono">Formal Proforma & Pricing Commitment</p>
                        </div>
                    </div>
                    <button @click="isNewQuoteOpen = false" class="text-neutral-400 hover:text-white cursor-pointer transition-colors">✕</button>
                </div>
                <form action="{{ route('quotations.store') }}" method="POST" class="p-5 space-y-4 text-xs overflow-y-auto flex-1">
                    @csrf
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Company / Client Name</label>
                            <input type="text" name="recipient_company" placeholder="e.g. Gimi Michi Food Ingredients" class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden" required>
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Attention Person</label>
                            <input type="text" name="recipient_name" placeholder="e.g. Supriya Shah" class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Payment Terms</label>
                            <input type="text" name="payment_terms" value="30 Days Credit" class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Freight / Delivery Terms</label>
                            <input type="text" name="freight_terms" value="FOR Destination (Freight Carrier Road Transport)" class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                    </div>

                    <!-- Product Line Item -->
                    <div class="p-3.5 bg-[#F5F6F8] rounded-2xl border border-neutral-200/80 space-y-3">
                        <span class="font-bold text-neutral-800 block">Line Item #1</span>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="col-span-1">
                                <label class="block font-bold text-neutral-700 mb-1">Product</label>
                                <select name="items[0][product_id]" class="w-full px-2.5 py-2 border border-neutral-200 bg-white rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden" required>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}">{{ $p->product_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-neutral-700 mb-1">Quantity (KG)</label>
                                <input type="number" name="items[0][quantity]" value="5000" class="w-full px-2.5 py-2 border border-neutral-200 bg-white rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden" required>
                            </div>
                            <div>
                                <label class="block font-bold text-neutral-700 mb-1">Rate / KG (₹)</label>
                                <input type="number" step="0.5" name="items[0][rate]" value="115" class="w-full px-2.5 py-2 border border-neutral-200 bg-white rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden" required>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-neutral-100 flex justify-end gap-2">
                        <button type="button" @click="isNewQuoteOpen = false" class="px-5 py-2 font-bold text-neutral-600 hover:bg-neutral-100 rounded-full transition-colors cursor-pointer">Cancel</button>
                        <button type="submit" class="px-5 py-2 font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs border border-[#c8f043] transition-all cursor-pointer">Generate Quotation</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

<script>
    function quotationsPageApp() {
        return {
            isNewQuoteOpen: false
        }
    }
</script>
@endsection
