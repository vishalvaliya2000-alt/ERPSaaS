@extends('layouts.app')

@section('title', 'Credit & Debit Notes — Adjustments & Returns')

@section('content')
<div class="space-y-6" x-data="creditDebitNotesManager(@js($nextCreditNumber), @js($nextDebitNumber))">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-neutral-200/80 shadow-xs">
        <div>
            <h2 class="text-xl font-black text-neutral-900 flex items-center gap-2">
                <span>📑</span> Credit & Debit Notes
            </h2>
            <p class="text-xs text-neutral-500 mt-1">
                Commercial invoice adjustments, quality rejections, sales returns, price differences, transit shortages, and supplementary charges.
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <button @click="openCreateModal('CREDIT_NOTE')"
                class="px-4 py-2.5 bg-[#091315] hover:bg-black text-[#D7FF53] font-black text-xs rounded-full shadow-2xs transition-all flex items-center gap-2 cursor-pointer border border-neutral-800 shrink-0">
                <span>➕</span>
                <span>New Credit Note</span>
            </button>
            <button @click="openCreateModal('DEBIT_NOTE')"
                class="px-4 py-2.5 bg-neutral-100 hover:bg-neutral-200 text-neutral-800 font-bold text-xs rounded-full shadow-2xs transition-all flex items-center gap-2 cursor-pointer border border-neutral-300 shrink-0">
                <span>➕</span>
                <span>New Debit Note</span>
            </button>
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Total Credit Notes (Sales Deductions) -->
        <div class="bg-white p-5 rounded-2xl sm:rounded-3xl border border-neutral-200/80 shadow-xs space-y-1">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold tracking-wider text-rose-600 uppercase font-mono">Credit Notes Issued</span>
                <span class="w-7 h-7 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xs font-black">−</span>
            </div>
            <div class="text-2xl font-black text-neutral-900 font-display">
                ₹{{ number_format($totalCreditAmount, 2) }}
            </div>
            <p class="text-[11px] text-neutral-400">Total reductions from returns, shortages & discounts</p>
        </div>

        <!-- Total Debit Notes (Supplementary Charges) -->
        <div class="bg-white p-5 rounded-2xl sm:rounded-3xl border border-neutral-200/80 shadow-xs space-y-1">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold tracking-wider text-indigo-600 uppercase font-mono">Debit Notes Issued</span>
                <span class="w-7 h-7 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-black">+</span>
            </div>
            <div class="text-2xl font-black text-neutral-900 font-display">
                ₹{{ number_format($totalDebitAmount, 2) }}
            </div>
            <p class="text-[11px] text-neutral-400">Total supplementary billings & price escalations</p>
        </div>

        <!-- Net AR Adjustment -->
        <div class="bg-white p-5 rounded-2xl sm:rounded-3xl border border-neutral-200/80 shadow-xs space-y-1">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold tracking-wider text-neutral-600 uppercase font-mono">Net Ledger Impact</span>
                <span class="w-7 h-7 rounded-xl bg-neutral-100 text-neutral-700 flex items-center justify-center text-xs font-black">⇄</span>
            </div>
            <div class="text-2xl font-black font-display {{ $netAdjustment < 0 ? 'text-rose-600' : 'text-neutral-900' }}">
                {{ $netAdjustment < 0 ? '− ' : '+ ' }}₹{{ number_format(abs($netAdjustment), 2) }}
            </div>
            <p class="text-[11px] text-neutral-400">Net effect applied against historical receivables</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-2xl sm:rounded-3xl border border-neutral-200/80 shadow-xs flex flex-col md:flex-row items-center justify-between gap-3">
        <form method="GET" action="{{ route('credit-debit-notes.index') }}" class="flex items-center gap-2 w-full md:w-auto flex-1">
            <input type="text" name="q" placeholder="Search Note #, Invoice #, customer, reason..." value="{{ $search }}"
                class="px-3.5 py-2 text-xs border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315] w-full sm:w-80 font-medium">
            
            <select name="type" onchange="this.form.submit()"
                class="px-3 py-2 text-xs border border-neutral-300 rounded-xl bg-neutral-50 font-bold text-neutral-700 focus:ring-2 focus:ring-[#091315]">
                <option value="ALL" {{ $type === 'ALL' ? 'selected' : '' }}>All Note Types</option>
                <option value="CREDIT_NOTE" {{ $type === 'CREDIT_NOTE' ? 'selected' : '' }}>Credit Notes Only</option>
                <option value="DEBIT_NOTE" {{ $type === 'DEBIT_NOTE' ? 'selected' : '' }}>Debit Notes Only</option>
            </select>

            <select name="status" onchange="this.form.submit()"
                class="px-3 py-2 text-xs border border-neutral-300 rounded-xl bg-neutral-50 font-bold text-neutral-700 focus:ring-2 focus:ring-[#091315]">
                <option value="ALL" {{ $status === 'ALL' ? 'selected' : '' }}>All Statuses</option>
                <option value="ISSUED" {{ $status === 'ISSUED' ? 'selected' : '' }}>Issued / Active</option>
                <option value="CANCELLED" {{ $status === 'CANCELLED' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </form>

        <div class="text-[11px] text-neutral-400 font-mono shrink-0">
            Showing {{ $notes->count() }} of {{ $notes->total() }} records
        </div>
    </div>

    <!-- Notes Table -->
    <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        @if($notes->isEmpty())
        <div class="p-12 text-center space-y-3">
            <div class="w-14 h-14 rounded-3xl bg-neutral-100 text-neutral-600 flex items-center justify-center text-2xl mx-auto font-bold shadow-xs">
                📑
            </div>
            <h3 class="text-sm font-bold text-slate-800">No Credit or Debit Notes Found</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto">
                No adjustments recorded yet. Issue a Credit Note for quality returns / shortages, or a Debit Note for supplementary billing.
            </p>
            <button @click="openCreateModal('CREDIT_NOTE')"
                class="px-4 py-2 bg-[#091315] hover:bg-black text-[#D7FF53] text-xs font-bold rounded-xl shadow-xs cursor-pointer">
                + Create First Credit Note
            </button>
        </div>
        @else
        <div class="overflow-x-auto touch-scroll">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-100/80 text-slate-700 font-bold border-b border-slate-200">
                        <th class="p-3.5">Note Number</th>
                        <th class="p-3.5">Type</th>
                        <th class="p-3.5">Date</th>
                        <th class="p-3.5">Original Invoice</th>
                        <th class="p-3.5">Customer</th>
                        <th class="p-3.5">Reason & Description</th>
                        <th class="p-3.5 text-right">Tax (GST)</th>
                        <th class="p-3.5 text-right">Total Amount</th>
                        <th class="p-3.5 text-center">Status</th>
                        <th class="p-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($notes as $note)
                    <tr class="hover:bg-neutral-50/70 transition-colors {{ $note->status === 'CANCELLED' ? 'opacity-50' : '' }}">
                        <td class="p-3.5 font-mono font-black text-slate-900 text-sm whitespace-nowrap">
                            <a href="{{ route('credit-debit-notes.show', $note->id) }}"
                                class="px-2.5 py-1 rounded-lg bg-neutral-100 hover:bg-[#D7FF53] hover:text-[#091315] border border-neutral-200 transition-colors inline-block">
                                {{ $note->note_number }}
                            </a>
                        </td>
                        <td class="p-3.5 whitespace-nowrap">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase font-mono border {{ $note->type_badge_class }}">
                                {{ $note->type_label }}
                            </span>
                        </td>
                        <td class="p-3.5 whitespace-nowrap font-mono text-neutral-600">
                            {{ $note->note_date ? $note->note_date->format('d M Y') : '—' }}
                        </td>
                        <td class="p-3.5 whitespace-nowrap font-mono font-bold">
                            @if($note->invoice)
                            <a href="{{ route('invoices.index') }}?q={{ urlencode($note->invoice->invoice_number) }}"
                                class="text-blue-600 hover:underline">
                                {{ $note->original_invoice_number }}
                            </a>
                            @else
                            <span class="text-neutral-500">{{ $note->original_invoice_number ?: 'Direct Adjustment' }}</span>
                            @endif
                        </td>
                        <td class="p-3.5">
                            <span class="font-bold text-neutral-900 block truncate max-w-xs">{{ $note->customer?->company_name ?? '—' }}</span>
                            <span class="text-[10px] text-neutral-400 font-mono">{{ $note->customer?->customer_code }}</span>
                        </td>
                        <td class="p-3.5">
                            <span class="font-medium text-neutral-800 block">{{ $note->reason }}</span>
                            @if($note->notes)
                            <span class="text-[10px] text-neutral-400 truncate block max-w-xs italic">{{ $note->notes }}</span>
                            @endif
                        </td>
                        <td class="p-3.5 text-right font-mono text-neutral-600 whitespace-nowrap">
                            ₹{{ number_format($note->tax_amount, 2) }}
                            <span class="text-[9px] text-neutral-400 block">
                                {{ $note->is_interstate ? 'IGST' : 'CGST+SGST' }}
                            </span>
                        </td>
                        <td class="p-3.5 text-right font-mono font-black text-sm whitespace-nowrap {{ $note->is_credit_note ? 'text-rose-600' : 'text-neutral-900' }}">
                            {{ $note->is_credit_note ? '−' : '+' }} ₹{{ number_format($note->total_amount, 2) }}
                        </td>
                        <td class="p-3.5 text-center whitespace-nowrap">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $note->status_badge_class }}">
                                {{ $note->status }}
                            </span>
                        </td>
                        <td class="p-3.5 text-right space-x-1.5 whitespace-nowrap">
                            <a href="{{ route('credit-debit-notes.show', $note->id) }}"
                                class="px-2 py-1 text-xs font-bold text-neutral-700 bg-neutral-100 hover:bg-neutral-200 rounded-lg transition-colors cursor-pointer"
                                title="View Details">
                                👁️ View
                            </a>
                            <a href="{{ route('credit-debit-notes.print', $note->id) }}" target="_blank"
                                class="px-2 py-1 text-xs font-bold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-lg transition-colors cursor-pointer border border-[#c8f043]"
                                title="Print Voucher">
                                🖨️ Print
                            </a>
                            @if($note->status !== 'CANCELLED')
                            <form action="{{ route('credit-debit-notes.cancel', $note->id) }}" method="POST" class="inline-block"
                                onsubmit="return confirm('Cancel {{ $note->type_label }} {{ $note->note_number }}?');">
                                @csrf
                                <button type="submit"
                                    class="px-2 py-1 text-xs font-bold text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer"
                                    title="Cancel Note">
                                    ✕
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200">
            {{ $notes->links() }}
        </div>
        @endif
    </div>

    <!-- Create Credit / Debit Note Modal -->
    <template x-teleport="body">
        <div x-show="isCreateModalOpen" x-cloak
            class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-2 sm:p-4">
            <div @click.outside="isCreateModalOpen = false"
                class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-neutral-200/80 max-w-4xl w-full overflow-hidden max-h-[94vh] flex flex-col text-xs">
                
                <!-- Modal Header -->
                <div class="p-4 sm:p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-2xl bg-[#D7FF53]/20 border border-[#D7FF53]/30 text-[#D7FF53] flex items-center justify-center text-lg font-bold">
                            📑
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm tracking-tight text-white font-display"
                                x-text="form.note_type === 'CREDIT_NOTE' ? 'Issue Credit Note (Reduction)' : 'Issue Debit Note (Supplementary)'"></h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono">GST & Financial Adjustment</p>
                        </div>
                    </div>
                    <button type="button" @click="isCreateModalOpen = false"
                        class="text-neutral-400 hover:text-white p-1 rounded-lg text-sm cursor-pointer">✕</button>
                </div>

                <form action="{{ route('credit-debit-notes.store') }}" method="POST"
                    class="p-4 sm:p-6 space-y-4 overflow-y-auto flex-1 touch-scroll">
                    @csrf
                    
                    <!-- Note Type Toggle -->
                    <div class="flex items-center gap-2 p-1.5 bg-neutral-100 rounded-2xl w-fit">
                        <button type="button" @click="setNoteType('CREDIT_NOTE')"
                            class="px-4 py-1.5 rounded-xl text-xs font-black transition-all cursor-pointer"
                            :class="form.note_type === 'CREDIT_NOTE' ? 'bg-[#091315] text-[#D7FF53] shadow-xs' : 'text-neutral-600 hover:text-neutral-900'">
                            🔴 Credit Note (Returns / Deductions)
                        </button>
                        <button type="button" @click="setNoteType('DEBIT_NOTE')"
                            class="px-4 py-1.5 rounded-xl text-xs font-black transition-all cursor-pointer"
                            :class="form.note_type === 'DEBIT_NOTE' ? 'bg-[#091315] text-[#D7FF53] shadow-xs' : 'text-neutral-600 hover:text-neutral-900'">
                            🔵 Debit Note (Extra Charges / Hikes)
                        </button>
                        <input type="hidden" name="note_type" :value="form.note_type">
                    </div>

                    <!-- Top Metadata Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Note Number *</label>
                            <input type="text" name="note_number" x-model="form.note_number" required
                                class="w-full px-3 py-2 border border-neutral-300 rounded-xl font-mono font-bold uppercase focus:ring-2 focus:ring-[#091315]">
                        </div>

                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Date *</label>
                            <input type="date" name="note_date" x-model="form.note_date" required
                                class="w-full px-3 py-2 border border-neutral-300 rounded-xl font-bold focus:ring-2 focus:ring-[#091315]">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-bold text-neutral-700 mb-1">Original Invoice (Auto-loads line items)</label>
                            <select x-model="form.invoice_id" @change="onInvoiceSelected($event.target.value)"
                                class="w-full px-3 py-2 border border-neutral-300 rounded-xl font-medium bg-neutral-50 focus:ring-2 focus:ring-[#091315]">
                                <option value="">-- Direct Note (No Invoice Link) --</option>
                                @foreach($invoices as $inv)
                                <option value="{{ $inv->id }}">
                                    {{ $inv->invoice_number }} ({{ $inv->invoice_date ? $inv->invoice_date->format('d M Y') : '' }}) — {{ $inv->customer?->company_name }} — ₹{{ number_format($inv->total_amount, 2) }}
                                </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="invoice_id" :value="form.invoice_id">
                            <input type="hidden" name="original_invoice_number" :value="form.original_invoice_number">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Customer / Client *</label>
                            <select name="customer_id" x-model="form.customer_id" required
                                class="w-full px-3 py-2 border border-neutral-300 rounded-xl font-bold bg-neutral-50 focus:ring-2 focus:ring-[#091315]">
                                <option value="">-- Select Customer --</option>
                                @foreach($customers as $c)
                                <option value="{{ $c->id }}">
                                    {{ $c->company_name }} ({{ $c->customer_code }})
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Reason for Adjustment *</label>
                            <select name="reason" x-model="form.reason" required
                                class="w-full px-3 py-2 border border-neutral-300 rounded-xl font-bold bg-neutral-50 focus:ring-2 focus:ring-[#091315]">
                                <option value="Sales Return / Rejection">01 - Sales Return / Quality Rejection</option>
                                <option value="Post-Sale Discount / Rate Difference">02 - Post-Sale Discount / Rate Difference</option>
                                <option value="Transit Shortage / Weight Loss">03 - Transit Shortage / Weight Loss</option>
                                <option value="Correction in Invoice">04 - Correction in Invoice</option>
                                <option value="Supplementary / Extra Charges">05 - Supplementary / Extra Charges</option>
                                <option value="Other Commercial Adjustment">06 - Other Commercial Adjustment</option>
                            </select>
                        </div>
                    </div>

                    <!-- Line Items Section -->
                    <div class="space-y-3 pt-2">
                        <div class="flex items-center justify-between">
                            <h4 class="font-extrabold text-neutral-900 text-xs flex items-center gap-1.5 uppercase tracking-wider font-mono">
                                <span>📦</span> Line Items Adjusted
                            </h4>
                            <button type="button" @click="addItem()"
                                class="px-3 py-1 bg-neutral-100 hover:bg-neutral-200 text-neutral-800 text-[11px] font-bold rounded-xl transition-colors cursor-pointer">
                                + Add Item
                            </button>
                        </div>

                        <div class="border border-neutral-200 rounded-2xl overflow-hidden shadow-2xs">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead class="bg-neutral-100/80 text-neutral-700 font-bold border-b border-neutral-200">
                                    <tr>
                                        <th class="p-2.5">Description</th>
                                        <th class="p-2.5 w-24">HSN</th>
                                        <th class="p-2.5 w-24 text-right">Qty</th>
                                        <th class="p-2.5 w-20">UOM</th>
                                        <th class="p-2.5 w-28 text-right">Rate (₹)</th>
                                        <th class="p-2.5 w-20 text-center">GST %</th>
                                        <th class="p-2.5 w-32 text-right">Total (₹)</th>
                                        <th class="p-2.5 w-10 text-center"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-neutral-100 bg-white">
                                    <template x-for="(item, index) in form.items" :key="index">
                                        <tr>
                                            <td class="p-2">
                                                <input type="text" :name="'items['+index+'][description]'" x-model="item.description" required
                                                    placeholder="Product / cut description..."
                                                    class="w-full px-2.5 py-1.5 border border-neutral-300 rounded-lg text-xs font-medium">
                                                <input type="hidden" :name="'items['+index+'][product_id]'" :value="item.product_id">
                                                <input type="hidden" :name="'items['+index+'][invoice_item_id]'" :value="item.invoice_item_id">
                                            </td>
                                            <td class="p-2">
                                                <input type="text" :name="'items['+index+'][hsn_code]'" x-model="item.hsn_code"
                                                    class="w-full px-2 py-1.5 border border-neutral-300 rounded-lg text-xs font-mono">
                                            </td>
                                            <td class="p-2">
                                                <input type="number" step="0.01" min="0" :name="'items['+index+'][quantity]'" x-model.number="item.quantity" required
                                                    @input="recalculateTotals()"
                                                    class="w-full px-2 py-1.5 border border-neutral-300 rounded-lg text-xs font-mono text-right font-bold">
                                            </td>
                                            <td class="p-2">
                                                <input type="text" :name="'items['+index+'][uom]'" x-model="item.uom"
                                                    class="w-full px-2 py-1.5 border border-neutral-300 rounded-lg text-xs font-mono uppercase">
                                            </td>
                                            <td class="p-2">
                                                <input type="number" step="0.01" min="0" :name="'items['+index+'][rate]'" x-model.number="item.rate" required
                                                    @input="recalculateTotals()"
                                                    class="w-full px-2 py-1.5 border border-neutral-300 rounded-lg text-xs font-mono text-right font-bold">
                                            </td>
                                            <td class="p-2">
                                                <input type="number" step="0.01" min="0" :name="'items['+index+'][tax_rate_percent]'" x-model.number="item.tax_rate_percent"
                                                    @input="recalculateTotals()"
                                                    class="w-full px-2 py-1.5 border border-neutral-300 rounded-lg text-xs font-mono text-center">
                                            </td>
                                            <td class="p-2 text-right font-mono font-bold text-neutral-800">
                                                ₹<span x-text="getItemTotal(item)"></span>
                                            </td>
                                            <td class="p-2 text-center">
                                                <button type="button" @click="removeItem(index)" :disabled="form.items.length <= 1"
                                                    class="text-rose-500 hover:text-rose-700 disabled:opacity-30 p-1 cursor-pointer">
                                                    ✕
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Summary & Notes -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Remarks & Audit Notes</label>
                            <textarea name="notes" x-model="form.notes" rows="3"
                                placeholder="Details about quality inspection, weighbridge ticket, or communication reference..."
                                class="w-full px-3 py-2 border border-neutral-300 rounded-xl text-xs"></textarea>
                        </div>

                        <!-- Computed Totals Summary Box -->
                        <div class="p-3.5 bg-neutral-50 rounded-2xl border border-neutral-200/80 space-y-1.5 text-xs">
                            <div class="flex items-center justify-between text-neutral-600">
                                <span>Material Subtotal:</span>
                                <span class="font-mono font-bold">₹<span x-text="computedSubtotal.toFixed(2)"></span></span>
                            </div>
                            <div class="flex items-center justify-between text-neutral-600">
                                <span>GST Tax Total:</span>
                                <span class="font-mono font-bold">₹<span x-text="computedTax.toFixed(2)"></span></span>
                            </div>
                            <div class="pt-2 border-t border-neutral-200 flex items-center justify-between font-black text-sm text-neutral-900">
                                <span x-text="form.note_type === 'CREDIT_NOTE' ? 'Total Credit Amount:' : 'Total Debit Amount:'"></span>
                                <span class="font-mono text-base"
                                    :class="form.note_type === 'CREDIT_NOTE' ? 'text-rose-600' : 'text-neutral-900'">
                                    ₹<span x-text="computedTotal.toFixed(2)"></span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Action Buttons -->
                    <div class="pt-4 border-t border-neutral-100 flex justify-end gap-2 shrink-0">
                        <button type="button" @click="isCreateModalOpen = false"
                            class="px-4 py-2 text-xs font-bold text-neutral-600 hover:bg-neutral-100 rounded-full cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-6 py-2.5 text-xs font-black text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs cursor-pointer border border-[#c8f043]">
                            <span x-text="form.note_type === 'CREDIT_NOTE' ? 'Generate Credit Note' : 'Generate Debit Note'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

<script>
function creditDebitNotesManager(defaultCreditNumber, defaultDebitNumber) {
    return {
        isCreateModalOpen: false,
        defaultCreditNumber: defaultCreditNumber,
        defaultDebitNumber: defaultDebitNumber,
        form: {
            note_type: 'CREDIT_NOTE',
            note_number: defaultCreditNumber,
            note_date: new Date().toISOString().split('T')[0],
            invoice_id: '',
            original_invoice_number: '',
            customer_id: '',
            reason: 'Sales Return / Rejection',
            notes: '',
            items: [
                {
                    description: '',
                    hsn_code: '07129020',
                    quantity: 1,
                    uom: 'KGS',
                    rate: 0,
                    tax_rate_percent: 5,
                    product_id: null,
                    invoice_item_id: null
                }
            ]
        },
        computedSubtotal: 0,
        computedTax: 0,
        computedTotal: 0,

        init() {
            this.recalculateTotals();
        },

        openCreateModal(type = 'CREDIT_NOTE', prefillInvoiceId = null) {
            this.setNoteType(type);
            this.form.note_date = new Date().toISOString().split('T')[0];
            this.form.notes = '';
            this.form.invoice_id = prefillInvoiceId || '';

            if (prefillInvoiceId) {
                this.onInvoiceSelected(prefillInvoiceId);
            } else {
                this.form.items = [{
                    description: '',
                    hsn_code: '07129020',
                    quantity: 1,
                    uom: 'KGS',
                    rate: 0,
                    tax_rate_percent: 5,
                    product_id: null,
                    invoice_item_id: null
                }];
                this.recalculateTotals();
            }

            this.isCreateModalOpen = true;
        },

        setNoteType(type) {
            this.form.note_type = type;
            this.form.note_number = type === 'CREDIT_NOTE' ? this.defaultCreditNumber : this.defaultDebitNumber;
            this.form.reason = type === 'CREDIT_NOTE' ? 'Sales Return / Rejection' : 'Supplementary / Extra Charges';
        },

        async onInvoiceSelected(invId) {
            if (!invId) {
                this.form.original_invoice_number = '';
                return;
            }

            try {
                const res = await fetch(`/api/invoices/${invId}/details`);
                const data = await res.json();
                if (data.success) {
                    this.form.customer_id = data.invoice.customer_id;
                    this.form.original_invoice_number = data.invoice.invoice_number;

                    if (data.items && data.items.length > 0) {
                        this.form.items = data.items.map(it => ({
                            description: it.description,
                            hsn_code: it.hsn_code || '07129020',
                            quantity: it.quantity,
                            uom: it.uom || 'KGS',
                            rate: it.rate,
                            tax_rate_percent: it.tax_rate_percent || 5,
                            product_id: it.product_id,
                            invoice_item_id: it.id
                        }));
                    }
                    this.recalculateTotals();
                }
            } catch (err) {
                console.error('Failed to load invoice items:', err);
            }
        },

        addItem() {
            this.form.items.push({
                description: '',
                hsn_code: '07129020',
                quantity: 1,
                uom: 'KGS',
                rate: 0,
                tax_rate_percent: 5,
                product_id: null,
                invoice_item_id: null
            });
            this.recalculateTotals();
        },

        removeItem(index) {
            if (this.form.items.length > 1) {
                this.form.items.splice(index, 1);
                this.recalculateTotals();
            }
        },

        getItemTotal(item) {
            const sub = (Number(item.quantity) || 0) * (Number(item.rate) || 0);
            const tax = sub * ((Number(item.tax_rate_percent) || 0) / 100);
            return (sub + tax).toFixed(2);
        },

        recalculateTotals() {
            let subtotal = 0;
            let tax = 0;

            this.form.items.forEach(it => {
                const itemSub = (Number(it.quantity) || 0) * (Number(it.rate) || 0);
                const itemTax = itemSub * ((Number(it.tax_rate_percent) || 0) / 100);
                subtotal += itemSub;
                tax += itemTax;
            });

            this.computedSubtotal = subtotal;
            this.computedTax = tax;
            this.computedTotal = subtotal + tax;
        }
    };
}
</script>
@endsection
