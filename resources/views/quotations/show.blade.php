@extends('layouts.app')

@section('title', 'Quotation — ' . ($quotation->quotation_number ?? 'Details'))

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    <!-- Breadcrumb & Header -->
    <div class="flex items-center justify-between gap-4">
        <a href="{{ route('quotations.index') }}" class="text-xs font-bold text-slate-500 hover:text-slate-900 flex items-center gap-1">
            ← Back to Quotations
        </a>
        <div class="flex items-center gap-2">
            @if($quotation->status !== 'ACCEPTED')
            <form action="{{ route('quotations.convert', $quotation->id) }}" method="POST" class="inline">
                @csrf
                <button type="submit" onclick="return confirm('Convert this Quotation to a live Sales Order?')" class="px-3.5 py-1.5 rounded-xl text-xs font-bold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] border border-[#c8f043] shadow-xs cursor-pointer">
                    ⚡ Convert to Order
                </button>
            </form>
            @endif
            <button onclick="window.print()" class="px-3.5 py-1.5 rounded-xl text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 shadow-xs">
                🖨️ Print / Save PDF
            </button>
            <a
                href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $quotation->recipient_phone ?: '919825088990') }}?text={{ urlencode('Hi ' . ($quotation->recipient_name ?: 'Sir') . ', please find attached formal quotation ' . $quotation->quotation_number . ' from ' . ($currentTenant->name ?? config('app.name', 'ERPSaaS')) . ' for total ' . formatINR($quotation->total_amount) . '. Looking forward to your purchase order confirmation.') }}"
                target="_blank"
                class="px-3.5 py-1.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-xs"
            >
                💬 Share on WhatsApp
            </a>
        </div>
    </div>

    <!-- Formal Printable Quotation Sheet -->
    <div class="bg-white rounded-2xl border border-slate-200 p-8 shadow-sm space-y-6 print:border-none print:shadow-none">
        <!-- Letterhead -->
        <div class="flex items-start justify-between border-b border-slate-200 pb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-slate-900 text-amber-400 flex items-center justify-center font-black text-xl">
                    {{ substr($currentTenant->name ?? 'CO', 0, 2) }}
                </div>
                <div>
                    <h1 class="text-xl font-black text-slate-900">{{ strtoupper($currentTenant->name ?? config('app.name', 'COMPANY')) }}</h1>
                    <p class="text-xs text-slate-500">{{ $currentTenant->tagline ?: 'GST Billing & Operations' }}</p>
                    <p class="text-[11px] text-slate-400">{{ $currentTenant->full_address }} | {{ $currentTenant->email ?? 'contact@company.com' }}</p>
                    <p class="text-[10px] text-slate-500 font-mono">{{ $currentTenant->tax_id_label ?? 'GSTIN' }}: {{ $currentTenant->tax_id_number ?? '24AAACR1234F1Z5' }} {{ $currentTenant->iec_code ? ' | IEC: ' . $currentTenant->iec_code : '' }}</p>
                </div>
            </div>

            <div class="text-right">
                <span class="text-xs font-bold text-amber-700 bg-amber-50 px-2.5 py-1 rounded-full uppercase border border-amber-200 block mb-1">
                    Commercial Quotation
                </span>
                <p class="font-mono font-bold text-sm text-slate-900">{{ $quotation->quotation_number }}</p>
                <p class="text-xs text-slate-500">Date: {{ $quotation->quotation_date->format('d M Y') }}</p>
                <p class="text-xs text-slate-500">Valid Until: {{ $quotation->valid_until->format('d M Y') }}</p>
            </div>
        </div>

        <!-- Recipient Info -->
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 text-xs">
            <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Quotation Issued To:</span>
            <p class="font-bold text-sm text-slate-900">{{ $quotation->recipient_company }}</p>
            <p class="text-slate-600">Attn: {{ $quotation->recipient_name ?? 'Purchase Head' }} ({{ $quotation->recipient_phone ?? 'Phone on file' }})</p>
            <p class="text-slate-600">{{ $quotation->recipient_email ?? '' }}</p>
        </div>

        <!-- Line Items Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-slate-700 border-y border-slate-200">
                        <th class="p-3 font-bold">#</th>
                        <th class="p-3 font-bold">Product Description & Cut</th>
                        <th class="p-3 font-bold">Packaging Specs</th>
                        <th class="p-3 font-bold text-right">Quantity</th>
                        <th class="p-3 font-bold text-right">Rate / KG (₹)</th>
                        <th class="p-3 font-bold text-right">Amount (₹)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($quotation->items as $idx => $it)
                        <tr>
                            <td class="p-3 text-slate-500">{{ $idx + 1 }}</td>
                            <td class="p-3">
                                <span class="font-bold text-slate-900 block">{{ $it->product->product_name }}</span>
                                <span class="text-[11px] text-slate-500">HSN: {{ $it->product->hsn_code }}</span>
                            </td>
                            <td class="p-3 text-slate-600">{{ $it->packaging }}</td>
                            <td class="p-3 text-right font-bold text-slate-900">{{ number_format($it->quantity) }} {{ $it->uom }}</td>
                            <td class="p-3 text-right text-slate-700">₹{{ number_format($it->rate, 2) }}</td>
                            <td class="p-3 text-right font-bold text-slate-900">{{ formatINR($it->amount) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals & Calculations -->
        <div class="flex justify-end pt-2">
            <div class="w-72 space-y-1.5 text-xs text-right border-t border-slate-200 pt-3">
                <div class="flex justify-between text-slate-600">
                    <span>Subtotal:</span>
                    <span class="font-bold text-slate-900">{{ formatINR($quotation->subtotal) }}</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>GST (5% Agricultural Dehydrated Food):</span>
                    <span class="font-bold text-slate-900">{{ formatINR($quotation->tax_amount) }}</span>
                </div>
                <div class="flex justify-between text-base font-black text-slate-900 border-t border-slate-200 pt-2">
                    <span>Total Amount:</span>
                    <span class="text-emerald-700">{{ formatINR($quotation->total_amount) }}</span>
                </div>
            </div>
        </div>

        <!-- Terms & Conditions -->
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 text-xs space-y-1.5">
            <h4 class="font-bold text-slate-800 uppercase tracking-wide mb-1">Commercial Terms & Conditions:</h4>
            <p class="text-slate-600">1. <b>Payment Terms:</b> {{ $quotation->payment_terms }}</p>
            <p class="text-slate-600">2. <b>Freight / Dispatch:</b> {{ $quotation->freight_terms }}</p>
            <p class="text-slate-600">3. <b>Delivery Schedule:</b> {{ $quotation->delivery_timeline }}</p>
            <p class="text-slate-600">4. <b>Quality Standard:</b> Moisture max 5%, Export Premium Grade, 100% sortex clean.</p>
        </div>

        <!-- Signature -->
        <div class="flex items-center justify-between pt-6 border-t border-slate-200 text-xs text-slate-500">
            <div>
                <p class="font-bold text-slate-800">For {{ strtoupper($currentTenant->name ?? config('app.name', 'COMPANY')) }}</p>
                <p class="mt-6 text-slate-600 font-medium">Authorized Signatory</p>
            </div>
            <div class="text-right">
                <p class="font-bold text-slate-800">Prepared by: {{ $currentUser?->name ?? 'Authorized Team' }}</p>
                <p class="text-[11px] text-slate-400">Operations & Sales</p>
            </div>
        </div>
    </div>
</div>
@endsection
