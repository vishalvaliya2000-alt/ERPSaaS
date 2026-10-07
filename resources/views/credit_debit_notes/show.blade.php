@extends('layouts.app')

@section('title', $note->type_label . ' ' . $note->note_number . ' — Details')

@section('content')
<div class="space-y-6 pb-12">
    <!-- Breadcrumb & Top Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-2 text-xs text-neutral-500 font-medium">
            <a href="{{ route('credit-debit-notes.index') }}"
                class="px-3 py-1.5 rounded-full bg-white hover:bg-neutral-100 text-neutral-700 font-bold border border-neutral-200/80 shadow-2xs transition-all flex items-center gap-1.5">
                <span>←</span>
                <span>All Notes</span>
            </a>
            <span class="text-neutral-300">/</span>
            <span class="text-neutral-900 font-bold font-mono">{{ $note->note_number }}</span>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            @if($note->invoice)
            <a href="{{ route('invoices.index') }}?q={{ urlencode($note->invoice->invoice_number) }}"
                class="px-3.5 py-1.5 bg-neutral-100 hover:bg-neutral-200 text-neutral-800 text-xs font-bold rounded-full transition-all flex items-center gap-1.5 border border-neutral-200">
                <span>📄</span>
                <span>View Original Invoice</span>
            </a>
            @endif

            <a href="{{ route('credit-debit-notes.print', $note->id) }}" target="_blank"
                class="px-4 py-1.5 bg-[#091315] hover:bg-black text-[#D7FF53] text-xs font-black rounded-full shadow-2xs transition-all flex items-center gap-1.5 border border-neutral-800 cursor-pointer">
                <span>🖨️</span>
                <span>Print Official Voucher</span>
            </a>

            @if($note->status !== 'CANCELLED')
            <form action="{{ route('credit-debit-notes.cancel', $note->id) }}" method="POST" class="inline-block"
                onsubmit="return confirm('Are you sure you want to cancel {{ $note->type_label }} {{ $note->note_number }}?');">
                @csrf
                <button type="submit"
                    class="px-3.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold rounded-full transition-all flex items-center gap-1 border border-rose-200 cursor-pointer">
                    <span>✕</span>
                    <span>Cancel Note</span>
                </button>
            </form>
            @endif
        </div>
    </div>

    <!-- Main Voucher Document Card -->
    <div class="bg-white rounded-3xl border border-neutral-200/80 p-6 sm:p-8 shadow-xs space-y-6">
        <!-- Top Banner -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-neutral-100 pb-6">
            <div class="flex items-start gap-4">
                <div class="w-14 h-14 rounded-2xl bg-[#091315] text-[#D7FF53] flex items-center justify-center text-2xl font-black shrink-0 shadow-xs">
                    {{ $note->is_credit_note ? '−' : '+' }}
                </div>
                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-black uppercase font-mono border {{ $note->type_badge_class }}">
                            {{ $note->type_label }}
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $note->status_badge_class }}">
                            {{ $note->status }}
                        </span>
                    </div>
                    <h1 class="text-2xl font-black text-neutral-900 font-display tracking-tight mt-1">
                        {{ $note->note_number }}
                    </h1>
                    <p class="text-xs text-neutral-500 font-mono">
                        Issued on {{ $note->note_date ? $note->note_date->format('d F Y') : '—' }}
                    </p>
                </div>
            </div>

            <!-- Total Amount Highlight Box -->
            <div class="p-4 rounded-2xl bg-neutral-50 border border-neutral-200 text-right min-w-[200px]">
                <span class="text-[10px] text-neutral-400 font-bold uppercase tracking-wider block font-mono">Total Adjustment</span>
                <span class="text-2xl font-black font-display {{ $note->is_credit_note ? 'text-rose-600' : 'text-neutral-900' }}">
                    {{ $note->is_credit_note ? '− ' : '+ ' }}₹{{ number_format($note->total_amount, 2) }}
                </span>
                <span class="text-[11px] text-neutral-500 block font-mono">
                    {{ $note->is_interstate ? 'Interstate (IGST)' : 'Intrastate (CGST + SGST)' }}
                </span>
            </div>
        </div>

        <!-- 2-Column Party Information (Issuer & Customer) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
            <!-- Issuer Details -->
            <div class="p-4 rounded-2xl bg-neutral-50 border border-neutral-200/70 space-y-1.5 text-xs">
                <span class="text-[10px] font-bold text-neutral-400 uppercase font-mono tracking-wider block">Issued By (Supplier):</span>
                <h3 class="font-extrabold text-neutral-900 text-sm font-display">{{ $tenant->name ?? 'ERPSaaS Enterprises' }}</h3>
                <p class="text-neutral-600 leading-relaxed">
                    {{ $tenant->address_line ?? '' }}<br>
                    {{ $tenant->city ?? '' }}{{ $tenant->state ? ', ' . $tenant->state : '' }} {{ $tenant->pincode ? '— ' . $tenant->pincode : '' }}
                </p>
                <div class="pt-2 text-[11px] text-neutral-600 font-mono space-y-0.5">
                    @if($tenant->tax_id_number)
                    <div>GSTIN: <b class="text-neutral-800">{{ $tenant->tax_id_number }}</b></div>
                    @endif
                    @if($tenant->pan_number)
                    <div>PAN: <b class="text-neutral-800">{{ $tenant->pan_number }}</b></div>
                    @endif
                </div>
            </div>

            <!-- Customer Details -->
            <div class="p-4 rounded-2xl bg-neutral-50 border border-neutral-200/70 space-y-1.5 text-xs">
                <span class="text-[10px] font-bold text-neutral-400 uppercase font-mono tracking-wider block">Issued To (Customer):</span>
                <h3 class="font-extrabold text-neutral-900 text-sm font-display">{{ $note->customer?->company_name }}</h3>
                <p class="text-neutral-600 leading-relaxed">
                    {{ $note->customer?->billing_address ?? ($note->customer?->city . ', ' . $note->customer?->state) }}
                </p>
                <div class="pt-2 text-[11px] text-neutral-600 font-mono space-y-0.5">
                    <div>Client Code: <b class="text-neutral-800">{{ $note->customer?->customer_code }}</b></div>
                    @if($note->customer?->gst_number)
                    <div>GSTIN: <b class="text-neutral-800">{{ $note->customer?->gst_number }}</b></div>
                    @endif
                    <div>Place of Supply: <b class="text-neutral-800">{{ $note->customer?->state ?? 'Standard' }}</b></div>
                </div>
            </div>
        </div>

        <!-- Reference & Reason Banner -->
        <div class="p-4 rounded-2xl bg-amber-50/50 border border-amber-200/70 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-900 font-mono">Original Invoice Reference:</span>
                <div class="font-bold text-neutral-900 text-sm font-mono flex items-center gap-2">
                    <span>📄 {{ $note->original_invoice_number ?: 'Direct Commercial Adjustment' }}</span>
                    @if($note->invoice && $note->invoice->invoice_date)
                    <span class="text-neutral-400 font-normal text-xs">(Dated: {{ $note->invoice->invoice_date->format('d M Y') }})</span>
                    @endif
                </div>
            </div>

            <div class="text-left sm:text-right space-y-0.5">
                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-900 font-mono">Adjustment Reason:</span>
                <div class="font-extrabold text-neutral-900">
                    {{ $note->reason }}
                </div>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="space-y-2">
            <h4 class="font-extrabold text-neutral-900 text-xs uppercase tracking-wider font-mono">
                Items / Particulars Breakdown
            </h4>

            <div class="border border-neutral-200 rounded-2xl overflow-hidden shadow-2xs">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-neutral-100/80 text-neutral-700 font-bold border-b border-neutral-200">
                        <tr>
                            <th class="p-3 w-10 text-center">#</th>
                            <th class="p-3">Product Description & Cut</th>
                            <th class="p-3 w-28">HSN Code</th>
                            <th class="p-3 w-28 text-right">Quantity</th>
                            <th class="p-3 w-28 text-right">Rate / Unit</th>
                            <th class="p-3 w-28 text-right">Subtotal</th>
                            <th class="p-3 w-20 text-center">GST %</th>
                            <th class="p-3 w-28 text-right">Tax (₹)</th>
                            <th class="p-3 w-32 text-right">Total (₹)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 bg-white">
                        @forelse($note->items as $idx => $item)
                        <tr class="hover:bg-neutral-50/50">
                            <td class="p-3 text-center text-neutral-400 font-mono">{{ $idx + 1 }}</td>
                            <td class="p-3">
                                <span class="font-bold text-neutral-900 block">{{ $item->description }}</span>
                                @if($item->product)
                                <span class="text-[10px] text-neutral-400 font-mono">{{ $item->product->product_code }}</span>
                                @endif
                            </td>
                            <td class="p-3 font-mono text-neutral-600">{{ $item->hsn_code ?: '—' }}</td>
                            <td class="p-3 text-right font-mono font-bold">{{ number_format($item->quantity, 2) }} {{ $item->uom }}</td>
                            <td class="p-3 text-right font-mono">₹{{ number_format($item->rate, 2) }}</td>
                            <td class="p-3 text-right font-mono font-semibold">₹{{ number_format($item->subtotal, 2) }}</td>
                            <td class="p-3 text-center font-mono">{{ number_format($item->tax_rate_percent, 0) }}%</td>
                            <td class="p-3 text-right font-mono text-neutral-600">₹{{ number_format($item->tax_amount, 2) }}</td>
                            <td class="p-3 text-right font-mono font-bold text-neutral-900">₹{{ number_format($item->total_amount, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="p-6 text-center text-neutral-400">No line items attached.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Financial Tax Summary & Signatory Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-neutral-100">
            <!-- Left Remarks -->
            <div class="space-y-4">
                @if($note->notes)
                <div class="p-3.5 bg-neutral-50 rounded-2xl border border-neutral-200 text-xs space-y-1">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase font-mono">Notes & Remarks:</span>
                    <p class="text-neutral-700 whitespace-pre-line">{{ $note->notes }}</p>
                </div>
                @endif

                <div class="p-4 bg-neutral-50/50 rounded-2xl border border-neutral-200 text-xs text-neutral-500 space-y-1">
                    <span class="font-bold text-neutral-800">Accounting Effect:</span>
                    <p class="text-[11px] leading-relaxed">
                        @if($note->is_credit_note)
                        This Credit Note reduces the customer's open receivable balance and accounts for reversed GST liability under Indian GST law.
                        @else
                        This Supplementary Debit Note increases the invoice value and charges applicable GST for additional quantities/costs.
                        @endif
                    </p>
                </div>
            </div>

            <!-- Right Tax Summary -->
            <div class="p-4 rounded-2xl bg-neutral-50 border border-neutral-200 space-y-2 text-xs">
                <div class="flex items-center justify-between text-neutral-600">
                    <span>Taxable Subtotal:</span>
                    <span class="font-mono font-bold">₹{{ number_format($note->subtotal, 2) }}</span>
                </div>

                @if($note->is_interstate)
                <div class="flex items-center justify-between text-neutral-600">
                    <span>Integrated GST (IGST):</span>
                    <span class="font-mono font-bold">₹{{ number_format($note->igst_amount ?: $note->tax_amount, 2) }}</span>
                </div>
                @else
                <div class="flex items-center justify-between text-neutral-600">
                    <span>Central GST (CGST):</span>
                    <span class="font-mono font-bold">₹{{ number_format($note->cgst_amount, 2) }}</span>
                </div>
                <div class="flex items-center justify-between text-neutral-600">
                    <span>State GST (SGST):</span>
                    <span class="font-mono font-bold">₹{{ number_format($note->sgst_amount, 2) }}</span>
                </div>
                @endif

                <div class="pt-2 border-t border-neutral-200 flex items-center justify-between font-black text-sm text-neutral-900">
                    <span>{{ $note->type_label }} Total:</span>
                    <span class="font-mono text-lg {{ $note->is_credit_note ? 'text-rose-600' : 'text-neutral-900' }}">
                        {{ $note->is_credit_note ? '− ' : '+ ' }}₹{{ number_format($note->total_amount, 2) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
