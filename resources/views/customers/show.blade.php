@extends('layouts.app')

@section('title', 'Customer 360 — ' . ($customer->company_name ?? 'Account Details'))

@section('content')
<div class="space-y-6 pb-12" x-data="{ tab: 'overview', whatsappOpen: false, isQuoteModalOpen: false, isSampleModalOpen: false, isEditCustomerOpen: false, noteText: '', msg: @js('Hi ' . ($customer->primary_contact_person ?: $customer->company_name) . ', following up regarding our open dehydrated garlic & onion orders and upcoming requirements. Please let me know your schedule.') }">
    <!-- Breadcrumb & Header -->
    <div class="flex items-center gap-2 text-xs text-neutral-500 font-medium">
        <a href="{{ route('customers.index') }}" class="px-3 py-1 rounded-full bg-white hover:bg-neutral-100 text-neutral-700 font-bold border border-neutral-200/80 shadow-2xs transition-all flex items-center gap-1.5">
            <span>←</span>
            <span>Clients Directory</span>
        </a>
        <span class="text-neutral-300">/</span>
        <span class="text-neutral-900 font-bold font-display">{{ $customer->company_name }}</span>
    </div>

    @php $health = getHealthBadge($customer->health_score); @endphp
    <div class="bg-white rounded-2xl sm:rounded-3xl border border-neutral-200/80 p-4 sm:p-6 lg:p-7 shadow-[0_2px_12px_rgba(0,0,0,0.02)]">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="flex items-start gap-4">
                <div class="w-16 h-16 rounded-2xl bg-[#091315] text-[#D7FF53] font-black text-2xl flex items-center justify-center shadow-xs shrink-0 border border-neutral-800 font-display">
                    {{ strtoupper(substr($customer->company_name, 0, 2)) }}
                </div>
                <div class="space-y-1">
                    <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="text-2xl lg:text-3xl font-black text-neutral-900 font-display tracking-tight">{{ $customer->company_name }}</h1>
                        @if(!empty($customer->trade_name) && $customer->trade_name !== $customer->company_name)
                            <span class="text-sm font-semibold text-neutral-500 font-sans">({{ $customer->trade_name }})</span>
                        @endif
                        <span class="font-mono text-xs font-bold px-2.5 py-0.5 rounded-full bg-neutral-100 text-neutral-600 border border-neutral-200/80">
                            {{ $customer->customer_code }}
                        </span>
                        <span class="text-xs font-bold px-3 py-1 rounded-full border flex items-center gap-1.5 font-mono {{ $health['bg'] }}">
                            <span class="w-2 h-2 rounded-full {{ $health['dot'] }}"></span>
                            {{ $health['label'] }}
                        </span>
                    </div>
                    <p class="text-xs text-neutral-500 flex items-center gap-3 flex-wrap font-medium">
                        @if(!empty($customer->trade_name) && $customer->trade_name !== $customer->company_name)
                            <span>Trade Name: <b class="text-neutral-800">{{ $customer->trade_name }}</b></span>
                            <span class="text-neutral-300">·</span>
                        @endif
                        <span>GSTIN: <b class="font-mono text-neutral-800">{{ $customer->gst_number ?? 'Unregistered' }}</b></span>
                        <span class="text-neutral-300">·</span>
                        <span>📍 {{ $customer->city ? $customer->city . ', ' : '' }}{{ $customer->state ?? 'India' }}</span>
                        <span class="text-neutral-300">·</span>
                        <span>Terms: <b class="text-neutral-800">{{ $customer->payment_terms_days }} Days Credit</b></span>
                    </p>
                </div>
            </div>

            <!-- Triggers -->
            <div class="flex items-center gap-2.5 flex-wrap">
                <button
                    type="button"
                    @click="whatsappOpen = true"
                    class="flex items-center gap-1.5 px-4 py-2.5 rounded-full text-xs font-bold text-emerald-900 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200/80 shadow-2xs transition-all cursor-pointer"
                >
                    <span>💬</span>
                    <span>WhatsApp Message</span>
                </button>
                @if($customer->primary_phone)
                    <a
                        href="tel:{{ $customer->primary_phone }}"
                        class="flex items-center gap-1.5 px-4 py-2.5 rounded-full text-xs font-bold text-neutral-800 bg-[#F5F6F8] hover:bg-neutral-100 border border-neutral-200/80 shadow-2xs transition-all"
                    >
                        <span>📞</span>
                        <span>Call ({{ $customer->primary_contact_person }})</span>
                    </a>
                @endif
                <button
                    type="button"
                    @click="isEditCustomerOpen = true"
                    class="flex items-center gap-1.5 px-4 py-2.5 rounded-full text-xs font-bold text-neutral-800 bg-[#F5F6F8] hover:bg-neutral-100 border border-neutral-200/80 shadow-2xs transition-all cursor-pointer"
                >
                    <span>✏️</span>
                    <span>Edit Terms & Profile</span>
                </button>
            </div>
        </div>

        <!-- KPIs & Quantity Fulfillment Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3.5 mt-6 pt-6 border-t border-neutral-100 text-center">
            <div class="bg-[#F5F6F8] p-3 rounded-2xl border border-neutral-200/60">
                <span class="text-[10px] font-bold text-neutral-400 uppercase block font-mono">Total Sales</span>
                <p class="text-base font-black text-neutral-900 mt-1 font-display">{{ formatINR($customer->total_revenue > 0 ? $customer->total_revenue : (float) $customer->invoices->sum('total_amount')) }}</p>
            </div>
            <div class="bg-[#F5F6F8] p-3 rounded-2xl border border-neutral-200/60">
                <span class="text-[10px] font-bold text-neutral-400 uppercase block font-mono">Outstanding</span>
                <p class="text-base font-black mt-1 font-display {{ $customer->outstanding_amount > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                    {{ formatINR($customer->outstanding_amount) }}
                </p>
            </div>
            <div class="bg-[#F5F6F8] p-3 rounded-2xl border border-neutral-200/60">
                <span class="text-[10px] font-bold text-neutral-500 uppercase block font-mono">Total Ordered</span>
                <p class="text-base font-black text-neutral-900 mt-1 font-display">{{ number_format($customer->total_ordered_qty) }} <span class="text-[10px] font-normal text-neutral-400">KG</span></p>
            </div>
            <div class="bg-[#F5F6F8] p-3 rounded-2xl border border-neutral-200/60">
                <span class="text-[10px] font-bold text-emerald-700 uppercase block font-mono">Supplied</span>
                <p class="text-base font-black text-emerald-600 mt-1 font-display">{{ number_format($customer->total_supplied_qty) }} <span class="text-[10px] font-normal text-emerald-500">KG</span></p>
            </div>
            <div class="bg-[#F5F6F8] p-3 rounded-2xl border border-neutral-200/60">
                <span class="text-[10px] font-bold {{ $customer->total_pending_qty > 0 ? 'text-amber-800' : 'text-neutral-400' }} uppercase block font-mono">Pending</span>
                <p class="text-base font-black mt-1 font-display {{ $customer->total_pending_qty > 0 ? 'text-amber-600' : 'text-neutral-400' }}">
                    {{ number_format($customer->total_pending_qty) }} <span class="text-[10px] font-normal text-neutral-400">KG</span>
                </p>
            </div>
            <div class="bg-[#F5F6F8] p-3 rounded-2xl border border-neutral-200/60">
                <span class="text-[10px] font-bold text-neutral-400 uppercase block font-mono">Sales Orders</span>
                <p class="text-base font-black text-neutral-900 mt-1 font-display">{{ count($customer->salesOrders) }} POs</p>
            </div>
            <div class="bg-[#F5F6F8] p-3 rounded-2xl border border-neutral-200/60">
                <span class="text-[10px] font-bold text-neutral-400 uppercase block font-mono">Last Order</span>
                <p class="text-base font-black text-neutral-900 mt-1 font-display">{{ $customer->last_order_date ? $customer->last_order_date->format('d M Y') : '—' }}</p>
            </div>
        </div>
    </div>

    <!-- AI Insights Banner -->
    @if($customer->aiInsights->count() > 0)
        <div class="bg-[#F3FED4]/60 border border-[#D7FF53] rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-xs">
            <h3 class="font-extrabold text-xs text-[#091315] uppercase tracking-wider mb-3 font-display flex items-center gap-1.5">
                <span>⚡</span>
                <span>AI Sales & Opportunity Recommendations</span>
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($customer->aiInsights as $ins)
                    <div class="bg-white rounded-2xl p-4 border border-neutral-200/80 shadow-2xs space-y-2">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-black text-neutral-900 font-display">{{ $ins->summary }}</span>
                            <span class="text-[9px] font-mono font-bold uppercase px-2 py-0.5 rounded-full bg-[#F5F6F8] text-neutral-700 border border-neutral-200/80">
                                {{ str_replace('_', ' ', $ins->insight_type) }}
                            </span>
                        </div>
                        <p class="text-xs text-neutral-600 leading-relaxed">{{ $ins->rationale }}</p>
                        <div class="p-2.5 bg-emerald-50 rounded-xl border border-emerald-200/80 text-xs text-emerald-950 font-medium flex items-center gap-1.5">
                            <span>💡</span>
                            <span><b>Action:</b> {{ $ins->recommended_action }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Profile Tabs -->
    <div class="flex items-center gap-1.5 p-1.5 bg-[#F5F6F8] rounded-full border border-neutral-200/80 overflow-x-auto touch-scroll max-w-full">
        <button @click="tab = 'overview'" :class="tab === 'overview' ? 'bg-[#091315] text-[#D7FF53] shadow-xs' : 'text-neutral-600 hover:text-neutral-900'" class="px-4 py-2 text-xs font-bold rounded-full whitespace-nowrap cursor-pointer transition-all font-display">
            Overview & Contacts
        </button>
        <button @click="tab = 'samples'" :class="tab === 'samples' ? 'bg-[#091315] text-[#D7FF53] shadow-xs' : 'text-neutral-600 hover:text-neutral-900'" class="px-4 py-2 text-xs font-bold rounded-full whitespace-nowrap cursor-pointer transition-all font-display">
            Samples ({{ count($customer->samples) }})
        </button>
        <button @click="tab = 'quotations'" :class="tab === 'quotations' ? 'bg-[#091315] text-[#D7FF53] shadow-xs' : 'text-neutral-600 hover:text-neutral-900'" class="px-4 py-2 text-xs font-bold rounded-full whitespace-nowrap cursor-pointer transition-all font-display">
            Quotations ({{ count($customer->quotations) }})
        </button>
        <button @click="tab = 'sales_orders'" :class="tab === 'sales_orders' ? 'bg-[#091315] text-[#D7FF53] shadow-xs' : 'text-neutral-600 hover:text-neutral-900'" class="px-4 py-2 text-xs font-bold rounded-full whitespace-nowrap cursor-pointer transition-all font-display">
            Sales Orders ({{ count($customer->salesOrders) }})
        </button>
        <button @click="tab = 'shipments'" :class="tab === 'shipments' ? 'bg-[#091315] text-[#D7FF53] shadow-xs' : 'text-neutral-600 hover:text-neutral-900'" class="px-4 py-2 text-xs font-bold rounded-full whitespace-nowrap cursor-pointer transition-all font-display">
            Shipments & LRs
        </button>
        <button @click="tab = 'invoices'" :class="tab === 'invoices' ? 'bg-[#091315] text-[#D7FF53] shadow-xs' : 'text-neutral-600 hover:text-neutral-900'" class="px-4 py-2 text-xs font-bold rounded-full whitespace-nowrap cursor-pointer transition-all font-display">
            Invoices ({{ count($customer->invoices) }})
        </button>
        <button @click="tab = 'payments'" :class="tab === 'payments' ? 'bg-[#091315] text-[#D7FF53] shadow-xs' : 'text-neutral-600 hover:text-neutral-900'" class="px-4 py-2 text-xs font-bold rounded-full whitespace-nowrap cursor-pointer transition-all font-display">
            Payments / Outstanding
        </button>
        <button @click="tab = 'documents'" :class="tab === 'documents' ? 'bg-[#091315] text-[#D7FF53] shadow-xs' : 'text-neutral-600 hover:text-neutral-900'" class="px-4 py-2 text-xs font-bold rounded-full whitespace-nowrap cursor-pointer transition-all font-display">
            Documents ({{ count($customer->documents) }})
        </button>
    </div>

    <!-- Tab Contents -->
    <div class="bg-white rounded-3xl border border-neutral-200/80 p-6 lg:p-7 shadow-[0_2px_12px_rgba(0,0,0,0.02)]">
        <!-- Overview -->
        <div x-show="tab === 'overview'" class="space-y-6 text-xs">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-3">
                    <h4 class="font-bold text-slate-800 uppercase tracking-wide border-b pb-2">Business Information</h4>
                    <dl class="grid grid-cols-2 gap-3">
                        <div>
                            <dt class="text-slate-400 font-bold">Company Name</dt>
                            <dd class="font-bold text-slate-800 mt-0.5">{{ $customer->company_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-bold">Customer Code</dt>
                            <dd class="font-mono font-bold text-slate-800 mt-0.5">{{ $customer->customer_code }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-bold">GSTIN</dt>
                            <dd class="font-mono font-bold text-slate-800 mt-0.5">{{ $customer->gst_number ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-bold">Payment Terms</dt>
                            <dd class="font-bold text-slate-800 mt-0.5">{{ $customer->payment_terms_days }} Days Credit</dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-slate-400 font-bold">Factory / Godown Address</dt>
                            <dd class="text-slate-700 mt-0.5">{{ $customer->address_line ?? 'Address on file' }} ({{ $customer->city }}, {{ $customer->state }})</dd>
                        </div>
                    </dl>
                </div>

                <div class="space-y-3">
                    <h4 class="font-bold text-slate-800 uppercase tracking-wide border-b pb-2">Contacts</h4>
                    @forelse($customer->contacts as $c)
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900">{{ $c->name ?: ($customer->primary_contact_person ?? 'Primary Contact') }}</span>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800">{{ $c->designation ?? 'Primary' }}</span>
                            </div>
                            <p class="text-slate-600">Phone: {{ $c->phone ?: ($customer->primary_phone ?? '—') }}</p>
                            <p class="text-slate-600">Email: {{ $c->email ?: ($customer->email ?? '—') }}</p>
                        </div>
                    @empty
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900">{{ $customer->primary_contact_person ?? 'Primary Contact' }}</span>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800">Primary</span>
                            </div>
                            <p class="text-slate-600">Phone: {{ $customer->primary_phone ?? '—' }}</p>
                            <p class="text-slate-600">Email: {{ $customer->email ?? '—' }}</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Product-Wise Orders & Fulfillment Widget -->
            @include('customers.partials.product_fulfillment_widget', ['customer' => $customer])
        </div>

        <!-- Payments / Outstanding -->
        <div x-show="tab === 'payments'" class="space-y-6">
            <!-- Financial Overview Header -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-[#F5F6F8] p-4 rounded-2xl border border-neutral-200/60 shadow-2xs">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase font-mono block">Lifetime Billed Revenue</span>
                    <p class="text-lg font-black text-neutral-900 font-display mt-1">{{ formatINR($customer->total_revenue > 0 ? $customer->total_revenue : (float) $customer->invoices->sum('total_amount')) }}</p>
                </div>
                <div class="bg-emerald-50/60 p-4 rounded-2xl border border-emerald-200/80 shadow-2xs">
                    <span class="text-[10px] font-bold text-emerald-700 uppercase font-mono block">Total Received Payments</span>
                    <p class="text-lg font-black text-emerald-700 font-display mt-1">{{ formatINR($customer->paymentReceipts->sum('amount_received')) }}</p>
                </div>
                <div class="bg-blue-50/60 p-4 rounded-2xl border border-blue-200/80 shadow-2xs">
                    <span class="text-[10px] font-bold text-blue-700 uppercase font-mono block">Unadjusted PO Advances</span>
                    <p class="text-lg font-black text-blue-700 font-display mt-1">{{ formatINR($unadjustedAdvances ?? 0) }}</p>
                </div>
                <div class="p-4 rounded-2xl border shadow-2xs {{ ($netOutstanding ?? $customer->outstanding_amount) > 0 ? 'bg-rose-50/60 border-rose-200/80' : 'bg-emerald-50/60 border-emerald-200/80' }}">
                    <span class="text-[10px] font-bold uppercase font-mono block {{ ($netOutstanding ?? $customer->outstanding_amount) > 0 ? 'text-rose-700' : 'text-emerald-700' }}">Net Outstanding Balance</span>
                    <p class="text-lg font-black font-display mt-1 {{ ($netOutstanding ?? $customer->outstanding_amount) > 0 ? 'text-rose-700' : 'text-emerald-700' }}">
                        {{ formatINR($netOutstanding ?? $customer->outstanding_amount) }}
                    </p>
                </div>
            </div>

            <!-- Payment Receipts History Section -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-extrabold text-sm text-neutral-900 font-display">Payment & Advance Receipts</h4>
                        <p class="text-xs text-neutral-500 font-medium">Recorded payment entries, RTGS/NEFT transaction references, and allocations</p>
                    </div>
                    <span class="font-mono text-xs font-bold px-2.5 py-0.5 rounded-full bg-[#F5F6F8] text-neutral-700 border border-neutral-200/80">
                        {{ $customer->paymentReceipts->count() }} {{ Str::plural('Receipt', $customer->paymentReceipts->count()) }}
                    </span>
                </div>

                @if($customer->paymentReceipts->isEmpty())
                    <div class="p-8 text-center bg-[#F5F6F8] rounded-2xl border border-dashed border-neutral-200 space-y-2">
                        <div class="text-3xl">💸</div>
                        <p class="font-extrabold text-sm text-neutral-800 font-display">No Payment Receipts Recorded</p>
                        <p class="text-xs text-neutral-500 max-w-md mx-auto">
                            Payment receipts booked against Sales Orders or Tax Invoices will appear here with mode, reference, and allocation details.
                        </p>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($customer->paymentReceipts as $rcpt)
                            <div class="bg-white rounded-2xl border border-neutral-200/80 p-4 shadow-2xs hover:border-neutral-300 transition-all space-y-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono font-black text-sm text-neutral-900">{{ $rcpt->receipt_number }}</span>
                                            <span class="text-[10px] font-mono font-bold uppercase px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                {{ str_replace('_', ' ', $rcpt->receipt_type) }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-neutral-500 font-mono mt-0.5">
                                            Date: {{ $rcpt->receipt_date ? $rcpt->receipt_date->format('d M Y') : '—' }} · Mode: <b class="text-neutral-800">{{ $rcpt->payment_mode ?? 'RTGS' }}</b>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-[10px] uppercase font-bold text-neutral-400 font-mono block">Amount Received</span>
                                        <span class="font-black text-base text-emerald-700 font-display">{{ formatINR($rcpt->amount_received) }}</span>
                                    </div>
                                </div>

                                <div class="p-2.5 bg-[#F5F6F8] rounded-xl border border-neutral-200/60 text-xs space-y-1">
                                    @if($rcpt->salesOrder)
                                        <div class="flex items-center justify-between">
                                            <span class="text-neutral-500">Linked Sales Order:</span>
                                            <span class="font-mono font-bold text-neutral-800">PO #{{ $rcpt->salesOrder->order_number }}</span>
                                        </div>
                                    @endif
                                    @if($rcpt->invoice)
                                        <div class="flex items-center justify-between">
                                            <span class="text-neutral-500">Linked Invoice:</span>
                                            <span class="font-mono font-bold text-neutral-800">{{ $rcpt->invoice->invoice_number }}</span>
                                        </div>
                                    @endif
                                    @if($rcpt->reference_number)
                                        <div class="flex items-center justify-between">
                                            <span class="text-neutral-500">UTR / Ref:</span>
                                            <span class="font-mono text-neutral-700">{{ $rcpt->reference_number }}</span>
                                        </div>
                                    @endif
                                    @if(!$rcpt->salesOrder && !$rcpt->invoice && !$rcpt->reference_number)
                                        <span class="text-neutral-400 italic">Direct Account Credit</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Standalone Credit & Debit Notes Section -->
            @php
                $standaloneNotes = $customer->creditDebitNotes->whereNull('invoice_id');
            @endphp
            @if($standaloneNotes->isNotEmpty())
                <div class="space-y-3 pt-4 border-t border-neutral-100">
                    <div class="flex items-center justify-between">
                        <h4 class="font-extrabold text-sm text-neutral-900 font-display">Standalone Credit & Debit Adjustments</h4>
                        <span class="font-mono text-xs font-bold px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-700">
                            {{ $standaloneNotes->count() }} Notes
                        </span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($standaloneNotes as $note)
                            <div class="bg-white rounded-2xl border border-neutral-200/80 p-3.5 shadow-2xs space-y-2">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-bold text-xs text-neutral-900">{{ $note->note_number }}</span>
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-mono font-bold uppercase {{ in_array($note->note_type, ['CREDIT_NOTE', 'CREDIT']) ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' }}">
                                            {{ in_array($note->note_type, ['CREDIT_NOTE', 'CREDIT']) ? 'Credit Note (-)' : 'Debit Note (+)' }}
                                        </span>
                                    </div>
                                    <span class="font-mono font-black text-xs text-neutral-900">{{ formatINR($note->total_amount) }}</span>
                                </div>
                                <p class="text-xs text-neutral-500">{{ $note->reason ?? 'Account adjustment' }} · <span class="font-mono">{{ $note->note_date ? $note->note_date->format('d M Y') : '—' }}</span></p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Orders -->
        <div x-show="tab === 'sales_orders'" class="space-y-6">
            <!-- Product-Wise Fulfillment Summary -->
            @include('customers.partials.product_fulfillment_widget', ['customer' => $customer])

            @if($customer->salesOrders->isEmpty())
                <div class="p-8 text-center bg-[#F5F6F8] rounded-2xl border border-dashed border-neutral-200 space-y-2">
                    <div class="text-3xl">📋</div>
                    <p class="font-extrabold text-sm text-neutral-800 font-display">No Sales Orders Recorded</p>
                    <p class="text-xs text-neutral-500 max-w-sm mx-auto">
                        Sales orders confirmed for this client will appear here with product line items, dispatch fulfillment, and PO documents.
                    </p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($customer->salesOrders as $so)
                        @php
                            $totalQ = $so->items->sum('order_qty');
                            $shippedQ = $so->items->sum('shipped_qty');
                            $balanceQ = $so->items->sum('balance_qty');
                            $poDoc = $so->poDocument ?? $so->documents->where('document_type', 'PO')->first();
                        @endphp
                        <div class="bg-white rounded-2xl border border-neutral-200/80 p-4 shadow-2xs hover:border-neutral-300 transition-all space-y-3">
                            <!-- Card Header -->
                            <div class="flex items-start justify-between gap-2 pb-2.5 border-b border-neutral-100">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-black text-sm text-neutral-900">{{ $so->order_number }}</span>
                                        <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full {{ $so->status === 'COMPLETED' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                                            {{ str_replace('_', ' ', $so->status) }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-neutral-500 font-mono mt-0.5">
                                        Date: {{ $so->order_date ? $so->order_date->format('d M Y') : '—' }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="font-black text-sm text-neutral-900 font-display">{{ formatINR($so->total_amount) }}</span>
                                    @if($poDoc)
                                        <div class="inline-flex items-center gap-1 ml-1">
                                            <a href="{{ route('documents.preview', $poDoc->id) }}" target="_blank" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#091315] text-[#D7FF53] hover:bg-black transition-colors" title="Preview Customer PO Document">
                                                📄 PO
                                            </a>
                                            <a href="{{ route('documents.download', $poDoc->id) }}" class="p-0.5 text-neutral-600 hover:text-neutral-900 text-xs" title="Download">
                                                📥
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Line Items -->
                            <div class="space-y-1.5 text-xs">
                                <span class="text-[10px] uppercase font-bold text-neutral-400 font-mono block">Order Items & Rates</span>
                                @foreach($so->items as $it)
                                    <div class="flex items-center justify-between p-2 bg-[#F5F6F8] rounded-xl text-neutral-800">
                                        <span class="font-medium truncate">{{ $it->product->product_name ?? 'Product' }}</span>
                                        <span class="font-mono font-bold text-neutral-900 shrink-0 ml-2">
                                            {{ number_format($it->order_qty) }} kg @ ₹{{ $it->rate }}/kg
                                        </span>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Fulfillment Breakdown Metrics -->
                            <div class="grid grid-cols-3 gap-2 text-center text-xs font-mono pt-1">
                                <div class="bg-[#F5F6F8] p-2 rounded-xl border border-neutral-200/60">
                                    <span class="text-[9px] uppercase font-bold text-neutral-400 block">Ordered</span>
                                    <span class="font-black text-neutral-900 text-xs mt-0.5 block">{{ formatQuantity($totalQ) }}</span>
                                </div>
                                <div class="bg-[#F5F6F8] p-2 rounded-xl border border-neutral-200/60">
                                    <span class="text-[9px] uppercase font-bold text-emerald-600 block">Shipped</span>
                                    <span class="font-black text-emerald-600 text-xs mt-0.5 block">{{ formatQuantity($shippedQ) }}</span>
                                </div>
                                <div class="p-2 rounded-xl border {{ $balanceQ > 0 ? 'bg-amber-50/60 border-amber-200/80' : 'bg-[#F5F6F8] border-neutral-200/60' }}">
                                    <span class="text-[9px] uppercase font-bold {{ $balanceQ > 0 ? 'text-amber-700' : 'text-neutral-400' }} block">Balance</span>
                                    <span class="font-black text-xs mt-0.5 block {{ $balanceQ > 0 ? 'text-amber-600' : 'text-neutral-400' }}">{{ formatQuantity($balanceQ) }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Shipments -->
        <div x-show="tab === 'shipments'" class="space-y-4">
            @php
                $shipments = $customer->salesOrders->flatMap->shipments;
            @endphp
            @if($shipments->isEmpty())
                <div class="p-8 text-center bg-white rounded-3xl border border-neutral-200/60 shadow-2xs space-y-2">
                    <div class="w-10 h-10 rounded-2xl bg-neutral-100 text-neutral-400 flex items-center justify-center mx-auto text-lg">🚚</div>
                    <p class="font-extrabold text-sm text-neutral-800">No Shipments Found</p>
                    <p class="text-xs text-neutral-400">There are no commercial dispatches or shipments recorded for this customer yet.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($shipments as $sh)
                        @php
                            $lrDoc = $sh->lrDocument ?? ($customer->documents->where('document_type', 'LR')->where('commercial_shipment_id', $sh->id)->first());
                        @endphp
                        <div class="bg-white rounded-2xl border border-neutral-200/80 p-4 space-y-3 shadow-2xs hover:shadow-xs transition-shadow">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="font-black text-sm text-neutral-900 block font-mono">{{ $sh->shipment_number }}</span>
                                    <span class="text-[11px] text-neutral-400 font-medium">{{ $sh->shipment_date->format('d M Y') }}</span>
                                </div>
                                <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 uppercase">
                                    {{ $sh->status }}
                                </span>
                            </div>

                            <div class="p-3 bg-[#F5F6F8] rounded-xl border border-neutral-200/60 space-y-2 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] uppercase font-bold text-neutral-400">Transporter</span>
                                    <span class="font-bold text-neutral-800">{{ $sh->transporter }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] uppercase font-bold text-neutral-400">LR Number</span>
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-mono font-bold text-brand-700">{{ $sh->lr_number }}</span>
                                        @if($sh->tracking_url)
                                            <button
                                                type="button"
                                                onclick="openLiveTracking({{ $sh->id }}, '{{ $sh->lr_number }}', '{{ addslashes($sh->transporter) }}')"
                                                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-extrabold bg-blue-50 text-blue-700 hover:bg-blue-100 hover:text-blue-800 border border-blue-200 transition-colors cursor-pointer"
                                                title="View live shipment tracking details inside ERP"
                                            >
                                                <span>📍</span> Track
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Dispatched Products -->
                            <div class="space-y-1.5 text-xs">
                                <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider block">Dispatched Items</span>
                                <div class="space-y-1">
                                    @foreach($sh->items as $it)
                                        <div class="flex items-center justify-between text-neutral-700 bg-neutral-50 px-2 py-1 rounded-lg border border-neutral-100 text-[11px]">
                                            <span class="truncate pr-2">{{ $it->salesOrderItem->product->product_name ?? 'Dehydrated item' }}</span>
                                            <span class="font-bold text-neutral-900 shrink-0">{{ number_format($it->quantity) }} kg</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- LR Document actions -->
                            <div class="pt-2 border-t border-neutral-100 flex items-center justify-between">
                                <span class="text-[10px] font-bold text-neutral-400 uppercase">LR Document</span>
                                @if($lrDoc)
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="{{ route('documents.preview', $lrDoc->id) }}" target="_blank" class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100 transition-colors" title="Preview Transporter Bilty / LR">
                                            🚚 View Bilty
                                        </a>
                                        <a href="{{ route('documents.download', $lrDoc->id) }}" class="p-1 rounded-lg text-neutral-600 hover:bg-neutral-100 text-xs" title="Download">
                                            📥
                                        </a>
                                    </div>
                                @else
                                    <span class="text-neutral-400 italic text-[11px]">No LR attached</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Invoices & Receipts -->
        <div x-show="tab === 'invoices'" class="space-y-4">
            @if($customer->invoices->isEmpty())
                <div class="p-8 text-center bg-white rounded-3xl border border-neutral-200/60 shadow-2xs space-y-2">
                    <div class="w-10 h-10 rounded-2xl bg-neutral-100 text-neutral-400 flex items-center justify-center mx-auto text-lg">📄</div>
                    <p class="font-extrabold text-sm text-neutral-800">No Invoices Found</p>
                    <p class="text-xs text-neutral-400">There are no commercial invoices generated for this account yet.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($customer->invoices as $inv)
                        @php
                            $suppDoc = $inv->documents->where('document_type', 'INVOICE')->first();
                            $hasNotes = ($inv->total_debited_amount > 0 || $inv->total_credited_amount > 0);
                        @endphp
                        <div class="bg-white rounded-2xl sm:rounded-3xl border border-neutral-200/80 p-4 shadow-2xs hover:border-neutral-300 transition-all space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2.5 border-b border-neutral-100">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-mono font-black text-sm text-neutral-900">{{ $inv->invoice_number }}</span>
                                    <span class="text-xs text-neutral-500 font-mono">· Date: {{ $inv->invoice_date->format('d M Y') }}</span>
                                    <span class="text-xs text-neutral-500 font-mono">· Due: {{ $inv->due_date->format('d M Y') }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider font-mono {{ ($inv->status === 'PAID' || $inv->is_settled) ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : ($inv->due_date->isPast() && $inv->adjusted_balance_due > 0 ? 'bg-rose-50 text-rose-700 border border-rose-200 animate-pulse' : ($inv->amount_received > 0 ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-[#F5F6F8] text-neutral-700 border border-neutral-200/80')) }}">
                                        @if($inv->status === 'PAID' || $inv->is_settled) ✓ Settled
                                        @elseif($inv->due_date->isPast() && $inv->adjusted_balance_due > 0) 🚨 Overdue
                                        @elseif($inv->status === 'PART_PAID' || $inv->amount_received > 0) ⚡ Part Paid
                                        @else ⏳ Issued
                                        @endif
                                    </span>
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="{{ route('invoices.pdf', $inv->id) }}" target="_blank" class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-[#091315] text-[#D7FF53] hover:bg-black transition-colors" title="Official Tax Invoice PDF">
                                            📄 Tax Invoice
                                        </a>
                                        @if($suppDoc)
                                            <a href="{{ route('documents.preview', $suppDoc->id) }}" target="_blank" class="px-2 py-1 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100 transition-colors" title="View Supporting Document">
                                                📎 Doc
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Financial Breakdown Grid -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center text-xs font-mono">
                                <div class="p-2 bg-[#F5F6F8] rounded-2xl border border-neutral-200/60">
                                    <span class="text-[9px] uppercase tracking-wider text-neutral-400 font-bold block">Original Billed</span>
                                    <span class="font-black text-neutral-900 text-xs mt-0.5 block truncate">{{ formatINR($inv->total_amount) }}</span>
                                </div>
                                <div class="p-2 bg-[#F5F6F8] rounded-2xl border border-neutral-200/60">
                                    <span class="text-[9px] uppercase tracking-wider text-neutral-400 font-bold block">Adjusted Total</span>
                                    <span class="font-black text-neutral-900 text-xs mt-0.5 block truncate">{{ formatINR($inv->adjusted_total_amount) }}</span>
                                    @if($hasNotes)
                                        <span class="text-[8px] text-amber-800 font-extrabold block truncate">
                                            @if($inv->total_debited_amount > 0)
                                                +{{ formatINR($inv->total_debited_amount) }} DN
                                            @endif
                                            @if($inv->total_credited_amount > 0)
                                                -{{ formatINR($inv->total_credited_amount) }} CN
                                            @endif
                                        </span>
                                    @endif
                                </div>
                                <div class="p-2 bg-emerald-50/50 rounded-2xl border border-emerald-100">
                                    <span class="text-[9px] uppercase tracking-wider text-emerald-700 font-bold block">Received</span>
                                    <span class="font-black text-emerald-700 text-xs mt-0.5 block truncate">{{ formatINR($inv->amount_received) }}</span>
                                </div>
                                <div class="p-2 rounded-2xl border {{ $inv->adjusted_balance_due > 0 ? 'bg-rose-50/50 border-rose-100' : 'bg-[#F5F6F8] border-neutral-200/60' }}">
                                    <span class="text-[9px] uppercase tracking-wider font-bold block {{ $inv->adjusted_balance_due > 0 ? 'text-rose-700' : 'text-neutral-400' }}">Remaining Due</span>
                                    <span class="font-black text-xs mt-0.5 block truncate {{ $inv->adjusted_balance_due > 0 ? 'text-rose-700' : 'text-neutral-400' }}">{{ formatINR($inv->adjusted_balance_due) }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Quotations & Proformas -->
        <div x-show="tab === 'quotations'" class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-sm text-slate-900">Commercial Quotations & Proforma Invoices</h3>
                    <p class="text-xs text-slate-500">Historical rate quotations and formal proformas issued to {{ $customer->company_name }}</p>
                </div>
                <button
                    @click="isQuoteModalOpen = true"
                    type="button"
                    class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 shadow-xs flex items-center gap-1.5 cursor-pointer"
                >
                    <span>➕</span>
                    <span>Generate New Quotation</span>
                </button>
            </div>

            @if($customer->quotations->isEmpty())
                <div class="p-8 text-center bg-white rounded-3xl border border-neutral-200/60 shadow-2xs space-y-2">
                    <div class="w-10 h-10 rounded-2xl bg-neutral-100 text-neutral-400 flex items-center justify-center mx-auto text-lg">📑</div>
                    <p class="font-extrabold text-sm text-neutral-800">No Quotations Found</p>
                    <p class="text-xs text-neutral-400">No quotations generated for this customer yet. Click "Generate New Quotation" above.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($customer->quotations as $quo)
                        <div class="bg-white rounded-2xl border border-neutral-200/80 p-4 space-y-3 shadow-2xs hover:shadow-xs transition-shadow flex flex-col justify-between">
                            <div class="space-y-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <a href="{{ route('quotations.show', $quo->id) }}" target="_blank" class="font-black text-sm text-amber-700 hover:underline block font-mono">
                                            {{ $quo->quotation_number }}
                                        </a>
                                        <span class="text-[11px] text-neutral-400 font-medium">{{ $quo->quotation_date->format('d M Y') }}</span>
                                    </div>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase">
                                        {{ $quo->status }}
                                    </span>
                                </div>

                                <!-- Items -->
                                <div class="p-2.5 bg-[#F5F6F8] rounded-xl border border-neutral-200/60 space-y-1.5">
                                    <span class="text-[9px] uppercase tracking-wider font-bold text-neutral-400 block">Quoted Products</span>
                                    @foreach($quo->items as $it)
                                        <div class="flex items-center justify-between text-xs text-neutral-800">
                                            <span class="font-semibold truncate pr-2">{{ $it->product?->product_name ?? 'Item' }}</span>
                                            <span class="text-neutral-500 text-[11px] shrink-0">{{ number_format($it->quantity) }} KG @ ₹{{ $it->rate }}/KG</span>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Pricing Breakdown -->
                                <div class="grid grid-cols-3 gap-2 text-center">
                                    <div class="bg-[#F5F6F8] p-2 rounded-xl border border-neutral-200/60">
                                        <span class="text-[9px] uppercase font-bold text-neutral-400 block">Subtotal</span>
                                        <span class="font-bold text-xs text-neutral-700 mt-0.5 block truncate">{{ formatINR($quo->subtotal) }}</span>
                                    </div>
                                    <div class="bg-[#F5F6F8] p-2 rounded-xl border border-neutral-200/60">
                                        <span class="text-[9px] uppercase font-bold text-neutral-400 block">GST (5%)</span>
                                        <span class="font-bold text-xs text-neutral-500 mt-0.5 block truncate">{{ formatINR($quo->tax_amount) }}</span>
                                    </div>
                                    <div class="bg-[#F5F6F8] p-2 rounded-xl border border-neutral-200/60">
                                        <span class="text-[9px] uppercase font-bold text-neutral-800 block">Total</span>
                                        <span class="font-black text-xs text-neutral-900 mt-0.5 block truncate">{{ formatINR($quo->total_amount) }}</span>
                                    </div>
                                </div>

                                @if($quo->payment_terms)
                                    <div class="text-[11px] text-neutral-500 flex items-center gap-1">
                                        <span class="font-bold text-neutral-600">Terms:</span>
                                        <span class="truncate">{{ $quo->payment_terms }}</span>
                                    </div>
                                @endif
                            </div>

                            <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-2">
                                <a
                                    href="{{ route('quotations.show', $quo->id) }}"
                                    target="_blank"
                                    class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-[11px] inline-flex items-center gap-1 transition-colors"
                                >
                                    📄 PDF
                                </a>
                                <a
                                    href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $customer->primary_phone ?: '919826011223') }}?text={{ urlencode('Hi ' . ($customer->primary_contact_person ?: 'Sir') . ', sharing our official Quotation ' . $quo->quotation_number . ' for ' . formatINR($quo->total_amount) . '. View here: ' . route('quotations.show', $quo->id)) }}"
                                    target="_blank"
                                    class="px-2.5 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 font-bold text-[11px] inline-flex items-center gap-1 transition-colors"
                                >
                                    💬 WhatsApp
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Samples & Trials -->
        <div x-show="tab === 'samples'" class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-sm text-slate-900">Commercial Evaluation Samples & Lab Trials</h3>
                    <p class="text-xs text-slate-500">Track sample dispatches, courier AWBs, and sensory trial approvals for {{ $customer->company_name }}</p>
                </div>
                <button
                    @click="isSampleModalOpen = true"
                    type="button"
                    class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 shadow-xs flex items-center gap-1.5 cursor-pointer"
                >
                    <span>🧪</span>
                    <span>Dispatch New Sample</span>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse($customer->samples as $s)
                    <div class="bg-slate-50 rounded-2xl border border-slate-200 p-4 space-y-3 shadow-2xs">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-black text-sm text-slate-900">{{ $s->product->product_name }}</span>
                                    <span class="px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 font-extrabold text-[10px]">
                                        {{ $s->quantity }} {{ $s->uom }}
                                    </span>
                                </div>
                                <span class="text-[10px] text-slate-500 font-mono">Sample #{{ $s->sample_number }} · Batch: {{ $s->batch_number ?: 'Standard' }}</span>
                            </div>
                            <span class="text-[10px] font-black uppercase px-2.5 py-1 rounded-full {{ $s->delivery_status === 'DELIVERED' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $s->delivery_status }}
                            </span>
                        </div>

                        <!-- Courier & AWB Tracker -->
                        <div class="p-2.5 bg-white rounded-xl border border-slate-200/80 flex items-center justify-between text-xs">
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase block">Courier Tracking</span>
                                <span class="font-bold text-slate-800">{{ $s->courier_provider }}</span>
                                <span class="font-mono text-slate-600 ml-1 font-semibold">({{ $s->awb_number }})</span>
                            </div>
                            <a
                                href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $customer->primary_phone ?: '919826011223') }}?text={{ urlencode('Hi ' . ($customer->primary_contact_person ?: 'Sir') . ', your evaluation sample of ' . $s->product->product_name . ' (' . $s->quantity . ' ' . $s->uom . ') was dispatched via ' . $s->courier_provider . ' (AWB: ' . $s->awb_number . '). Please let us know once received!') }}"
                                target="_blank"
                                class="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 text-[11px] font-bold"
                            >
                                💬 WhatsApp AWB
                            </a>
                        </div>

                        <!-- Next Action -->
                        <div class="p-2.5 bg-amber-50/70 rounded-xl border border-amber-200/60 text-xs space-y-1">
                            <span class="font-bold text-amber-900 block text-[11px]">👉 Next Action:</span>
                            <p class="text-slate-700 font-medium">{{ $s->next_action ?: 'Follow up on sensory and lab evaluation' }}</p>
                        </div>
                    </div>
                @empty
                    <div class="col-span-2 p-8 text-center bg-slate-50 rounded-2xl border border-slate-200 text-slate-400 text-xs">
                        No evaluation samples dispatched yet. Click "Dispatch New Sample" above.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Documents Tab (Requirement 12) -->
        <div x-show="tab === 'documents'" class="space-y-5">
            <!-- Documents Summary Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 bg-[#F5F6F8] rounded-2xl border border-neutral-200/80">
                <div class="flex items-center gap-2.5 flex-wrap">
                    <span class="text-xs font-bold text-neutral-500 font-mono uppercase">Vault Breakdown:</span>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-800 border border-blue-200 font-mono">
                        POs: {{ $customer->documents->where('document_type', 'PO')->count() }}
                    </span>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 font-mono">
                        Invoices: {{ $customer->documents->where('document_type', 'INVOICE')->count() }}
                    </span>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-purple-50 text-purple-800 border border-purple-200 font-mono">
                        LRs: {{ $customer->documents->where('document_type', 'LR')->count() }}
                    </span>
                </div>
            </div>

            <!-- Documents List -->
            @if($customer->documents->isEmpty())
                <div class="p-8 text-center bg-white rounded-3xl border border-neutral-200/60 shadow-2xs space-y-2">
                    <div class="w-10 h-10 rounded-2xl bg-neutral-100 text-neutral-400 flex items-center justify-center mx-auto text-lg">📁</div>
                    <p class="font-extrabold text-sm text-neutral-800">No Documents Found</p>
                    <p class="text-xs text-neutral-400">Zero documents uploaded for {{ $customer->company_name }} yet.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($customer->documents as $doc)
                        <div class="bg-white rounded-2xl border border-neutral-200/80 p-4 space-y-3 shadow-2xs hover:shadow-xs transition-shadow flex flex-col justify-between">
                            <div class="space-y-2.5">
                                <div class="flex items-start justify-between gap-2">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold border {{ $doc->type_badge_color }}">
                                        {{ $doc->type_label }}
                                    </span>
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-[#F5F6F8] text-neutral-700">
                                            v{{ $doc->current_version }}
                                        </span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase {{ $doc->status === 'PAID' ? 'bg-emerald-50 text-emerald-700' : ($doc->status === 'OVERDUE' ? 'bg-rose-50 text-rose-700' : 'bg-[#F5F6F8] text-neutral-700') }}">
                                            {{ $doc->status }}
                                        </span>
                                    </div>
                                </div>

                                <div>
                                    <a href="{{ route('documents.preview', $doc->id) }}" target="_blank" class="font-extrabold text-sm text-neutral-900 font-display hover:underline block truncate">
                                        {{ $doc->document_number }}
                                    </a>
                                    <span class="text-[10px] text-neutral-400 font-mono block truncate">{{ $doc->activeVersion?->file_name ?? 'Document File' }}</span>
                                </div>

                                <div class="p-2.5 bg-[#F5F6F8] rounded-xl border border-neutral-200/60 space-y-1.5 text-xs">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] uppercase font-bold text-neutral-400">Date</span>
                                        <span class="font-mono font-medium text-neutral-700">{{ $doc->document_date?->format('d M Y') ?? '—' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] uppercase font-bold text-neutral-400">Amount</span>
                                        <span class="font-black font-display text-neutral-900">{{ formatINR($doc->total_amount) }}</span>
                                    </div>
                                </div>

                                <!-- Connected Transaction -->
                                <div class="flex items-center gap-1 flex-wrap">
                                    @if($doc->salesOrder)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-blue-50 text-blue-700 border border-blue-200" title="Linked Sales Order">
                                            SO: {{ $doc->salesOrder->order_number }}
                                        </span>
                                    @endif
                                    @if($doc->commercialShipment)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-amber-50 text-amber-800 border border-amber-200" title="Linked Shipment LR">
                                            LR: {{ $doc->commercialShipment->lr_number }}
                                        </span>
                                    @endif
                                    @if($doc->invoice)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-emerald-50 text-emerald-800 border border-emerald-200" title="Linked Tax Invoice">
                                            INV: {{ $doc->invoice->invoice_number }}
                                        </span>
                                    @endif
                                    @if(!$doc->salesOrder && !$doc->commercialShipment && !$doc->invoice)
                                        <span class="text-neutral-400 italic text-[10px]">Direct Vault</span>
                                    @endif
                                </div>
                            </div>

                            <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-1.5">
                                <a href="{{ route('documents.preview', $doc->id) }}" target="_blank" class="px-3 py-1 rounded-full text-[11px] font-bold bg-[#091315] text-[#D7FF53] hover:bg-black transition-all">
                                    View
                                </a>
                                <a href="{{ route('documents.download', $doc->id) }}" class="p-1 rounded-full text-neutral-600 hover:bg-neutral-100 text-xs" title="Download">
                                    📥
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- WhatsApp Dialog -->
    <template x-teleport="body">
        <div x-show="whatsappOpen" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-2 sm:p-4">
            <div @click.outside="whatsappOpen = false" class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-neutral-200/80 w-full max-w-lg max-h-[92vh] overflow-hidden text-xs flex flex-col">
                <div class="p-4 sm:p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5">
                        <span class="text-lg">💬</span>
                        <div>
                            <h3 class="font-extrabold text-sm font-display">WhatsApp Follow-Up</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono">{{ $customer->primary_contact_person ?: $customer->company_name }}</p>
                        </div>
                    </div>
                    <button @click="whatsappOpen = false" class="text-neutral-400 hover:text-white cursor-pointer">✕</button>
                </div>
                <div class="p-4 sm:p-5 space-y-4 text-xs overflow-y-auto flex-1 touch-scroll">
                    <div class="p-3 bg-[#F5F6F8] rounded-2xl border border-neutral-200/80 text-neutral-800">
                        <span class="font-bold">Context:</span> Outstanding <span class="font-bold text-neutral-900">{{ formatINR($customer->outstanding_amount) }}</span>
                    </div>
                    <textarea rows="4" class="w-full p-3.5 border border-neutral-300 rounded-2xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden font-sans leading-relaxed text-xs" x-model="msg" x-ref="waText"></textarea>
                    <div class="flex justify-end gap-2 pt-2 border-t border-neutral-100">
                        <button type="button" @click="whatsappOpen = false" class="px-4 py-2 font-bold text-neutral-600 hover:bg-neutral-100 rounded-full cursor-pointer">Cancel</button>
                        <a
                            :href="'https://wa.me/{{ preg_replace('/[^0-9]/', '', $customer->primary_phone ?: '919826011223') }}?text=' + encodeURIComponent(msg)"
                            target="_blank"
                            @click="whatsappOpen = false"
                            class="px-5 py-2 font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs cursor-pointer inline-flex items-center gap-1.5 border border-[#c8f043]"
                        >
                            <span>🚀 Launch WhatsApp</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Generate Quotation Modal -->
    <template x-teleport="body">
        <div x-show="isQuoteModalOpen" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-2 sm:p-4">
            <div @click.outside="isQuoteModalOpen = false" class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-neutral-200/80 w-full max-w-2xl overflow-hidden text-xs flex flex-col max-h-[92vh]">
                <div class="p-4 sm:p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5">
                        <span class="text-lg">📄</span>
                        <div>
                            <h3 class="font-extrabold text-sm font-display">Generate Formal Quotation / Proforma</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono">For {{ $customer->company_name }}</p>
                        </div>
                    </div>
                    <button @click="isQuoteModalOpen = false" class="text-neutral-400 hover:text-white text-base cursor-pointer">✕</button>
                </div>

                <form action="{{ route('quotations.store') }}" method="POST" class="p-4 sm:p-6 space-y-4 overflow-y-auto flex-1 touch-scroll" x-data="{ items: [{ product_id: '{{ $products->first()?->id ?? 1 }}', quantity: 5000, rate: 280, packaging: '25 KG Bag / Carton' }] }">
                    @csrf
                    <input type="hidden" name="recipient_company" value="{{ $customer->company_name }}">
                    <input type="hidden" name="recipient_name" value="{{ $customer->primary_contact_person }}">
                    <input type="hidden" name="recipient_phone" value="{{ $customer->primary_phone }}">
                    <input type="hidden" name="recipient_email" value="{{ $customer->primary_email }}">

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Payment Terms</label>
                            <select name="payment_terms" class="w-full px-3 py-2 border border-neutral-300 rounded-xl bg-white focus:ring-2 focus:ring-[#091315]">
                                <option value="30 Days Credit" selected>30 Days Credit</option>
                                <option value="100% Advance">100% Advance</option>
                                <option value="50% Advance, 50% Against Bilty">50% Adv, 50% Against Bilty</option>
                                <option value="15 Days Credit">15 Days Credit</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Freight Terms</label>
                            <select name="freight_terms" class="w-full px-3 py-2 border border-neutral-300 rounded-xl bg-white focus:ring-2 focus:ring-[#091315]">
                                <option value="FOR Destination (Paid)" selected>FOR Destination (Paid)</option>
                                <option value="Ex-Factory Mahuva (To-Pay)">Ex-Factory Mahuva (To-Pay)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Validity (Days)</label>
                            <input type="number" name="validity_days" value="7" class="w-full px-3 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315]">
                        </div>
                    </div>

                    <!-- Line Items -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="font-bold text-neutral-800">Quotation Line Items</h4>
                            <button type="button" @click="items.push({ product_id: '{{ $products->first()?->id ?? 1 }}', quantity: 5000, rate: 280, packaging: '25 KG Bag' })" class="text-[11px] font-bold text-neutral-900 bg-[#D7FF53] px-3 py-1 rounded-full cursor-pointer hover:bg-[#c8f043]">
                                + Add Another Product
                            </button>
                        </div>

                        <template x-for="(item, idx) in items" :key="idx">
                            <div class="p-3.5 bg-[#F5F6F8] rounded-2xl border border-neutral-200/80 space-y-2">
                                <div class="grid grid-cols-1 sm:grid-cols-4 gap-2.5">
                                    <div class="sm:col-span-2">
                                        <label class="block font-bold text-neutral-600 mb-1 text-[11px]">Product</label>
                                        <select :name="'items[' + idx + '][product_id]'" x-model="item.product_id" class="w-full px-2.5 py-1.5 border border-neutral-300 rounded-xl bg-white text-xs focus:ring-2 focus:ring-[#091315]">
                                            @foreach($products as $p)
                                                <option value="{{ $p->id }}">{{ $p->product_name }} ({{ $p->category }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-bold text-neutral-600 mb-1 text-[11px]">Quantity (KG)</label>
                                        <input type="number" :name="'items[' + idx + '][quantity]'" x-model="item.quantity" class="w-full px-2.5 py-1.5 border border-neutral-300 rounded-xl bg-white text-xs font-bold focus:ring-2 focus:ring-[#091315]">
                                    </div>
                                    <div>
                                        <label class="block font-bold text-neutral-600 mb-1 text-[11px]">Rate (₹ / KG)</label>
                                        <input type="number" :name="'items[' + idx + '][rate]'" x-model="item.rate" class="w-full px-2.5 py-1.5 border border-neutral-300 rounded-xl bg-white text-xs font-bold focus:ring-2 focus:ring-[#091315]">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="pt-4 border-t border-neutral-100 flex justify-end gap-2 shrink-0">
                        <button type="button" @click="isQuoteModalOpen = false" class="px-4 py-2 font-bold text-neutral-600 hover:bg-neutral-100 rounded-full cursor-pointer">Cancel</button>
                        <button type="submit" class="px-5 py-2 font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs cursor-pointer flex items-center gap-1.5 border border-[#c8f043]">
                            <span>📄 Generate Official Quotation</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- Dispatch Sample Modal -->
    <template x-teleport="body">
        <div x-show="isSampleModalOpen" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-2 sm:p-4">
            <div @click.outside="isSampleModalOpen = false" class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-neutral-200/80 w-full max-w-lg overflow-hidden text-xs flex flex-col max-h-[92vh]">
                <div class="p-4 sm:p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5">
                        <span class="text-lg">🧪</span>
                        <div>
                            <h3 class="font-extrabold text-sm font-display">Dispatch Evaluation Sample</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono">For {{ $customer->company_name }}</p>
                        </div>
                    </div>
                    <button @click="isSampleModalOpen = false" class="text-neutral-400 hover:text-white text-base cursor-pointer">✕</button>
                </div>

                <form action="{{ route('samples.store') }}" method="POST" class="p-4 sm:p-6 space-y-4 overflow-y-auto flex-1 touch-scroll">
                    @csrf
                    <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                    <input type="hidden" name="recipient_name" value="{{ $customer->primary_contact_person }}">

                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Product Grade <span class="text-rose-500">*</span></label>
                        <select name="product_id" class="w-full px-3 py-2 border border-neutral-300 rounded-xl bg-white focus:ring-2 focus:ring-[#091315]" required>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->product_name }} ({{ $p->category }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Quantity</label>
                            <input type="number" step="0.1" name="quantity" value="0.5" class="w-full px-3 py-2 border border-neutral-300 rounded-xl font-bold focus:ring-2 focus:ring-[#091315]" required>
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Unit of Measurement</label>
                            <select name="uom" class="w-full px-3 py-2 border border-neutral-300 rounded-xl bg-white focus:ring-2 focus:ring-[#091315]">
                                <option value="KG" selected>KG (Kilograms)</option>
                                <option value="Grams">Grams (g)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Courier Partner</label>
                            <select name="courier_provider" class="w-full px-3 py-2 border border-neutral-300 rounded-xl bg-white focus:ring-2 focus:ring-[#091315]">
                                <option value="DTDC Express" selected>DTDC Express</option>
                                <option value="Trackon Courier">Trackon Courier</option>
                                <option value="Professional Courier">Professional Courier</option>
                                <option value="Blue Dart Express">Blue Dart Express</option>
                                <option value="Direct Factory Handover">Direct Factory Handover</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Courier Tracking AWB #</label>
                            <input type="text" name="awb_number" placeholder="e.g. DTDC-{{ rand(100000, 999999) }}" class="w-full px-3 py-2 border border-neutral-300 rounded-xl font-mono focus:ring-2 focus:ring-[#091315]">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Scheduled Next Action <span class="text-rose-500">*</span></label>
                        <input type="text" name="next_action" value="Call buyer for sample delivery confirmation & sensory trial feedback" class="w-full px-3 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315]" required>
                    </div>

                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Sample Remarks / Target Mesh</label>
                        <textarea name="remarks" rows="2" placeholder="e.g. 80-100 mesh Garlic Powder sample for seasoning blending trial." class="w-full px-3 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315]"></textarea>
                    </div>

                    <div class="pt-3 border-t border-neutral-100 flex justify-end gap-2 shrink-0">
                        <button type="button" @click="isSampleModalOpen = false" class="px-4 py-2 font-bold text-neutral-600 hover:bg-neutral-100 rounded-full cursor-pointer">Cancel</button>
                        <button type="submit" class="px-5 py-2 font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs cursor-pointer flex items-center gap-1.5 border border-[#c8f043]">
                            <span>🚀 Dispatch Sample</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- Edit Customer & Credit Terms Modal -->
    <template x-teleport="body">
        <div x-show="isEditCustomerOpen" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-2 sm:p-4">
            <div @click.outside="isEditCustomerOpen = false" class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-neutral-200/80 w-full max-w-lg overflow-hidden flex flex-col text-xs max-h-[92vh]" x-data="{ creditDays: {{ (int)($customer->payment_terms_days ?? 30) }} }">
                <!-- Modal Header -->
                <div class="p-4 sm:p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-2xl bg-[#D7FF53]/20 border border-[#D7FF53]/30 text-[#D7FF53] flex items-center justify-center text-lg font-bold">
                            ✏️
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm tracking-tight text-white font-display">Edit Customer Profile & Terms</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono">{{ $customer->customer_code }} · {{ $customer->company_name }}</p>
                        </div>
                    </div>
                    <button @click="isEditCustomerOpen = false" class="text-neutral-400 hover:text-white text-base cursor-pointer">✕</button>
                </div>

                <form action="{{ route('customers.update', $customer->id) }}" method="POST" class="p-4 sm:p-5 space-y-3.5 text-xs overflow-y-auto flex-1">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Company / Billing Name *</label>
                            <input type="text" name="company_name" value="{{ old('company_name', $customer->company_name) }}" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl font-bold focus:ring-2 focus:ring-[#091315] focus:outline-hidden" required>
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Trade Name (Brand/Display)</label>
                            <input type="text" name="trade_name" value="{{ old('trade_name', $customer->trade_name) }}" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">GSTIN Number</label>
                            <input type="text" name="gst_number" value="{{ old('gst_number', $customer->gst_number) }}" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl font-mono uppercase focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Relationship Stage</label>
                            <select name="stage" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl bg-white font-bold focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                                <option value="ACTIVE" {{ $customer->stage === 'ACTIVE' ? 'selected' : '' }}>Active Account</option>
                                <option value="LEAD" {{ $customer->stage === 'LEAD' ? 'selected' : '' }}>Lead / Prospect</option>
                                <option value="CHURN_RISK" {{ $customer->stage === 'CHURN_RISK' ? 'selected' : '' }}>At Risk</option>
                            </select>
                        </div>
                    </div>

                    <!-- Payment Terms / Agreed Credit Days -->
                    <div class="p-3.5 bg-neutral-50 rounded-2xl border border-neutral-200 space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block font-bold text-neutral-800">
                                <span>📅 Agreed Payment Terms (Credit Days) *</span>
                            </label>
                            <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800" x-text="creditDays == 0 ? '⚡ 100% Advance / Immediate' : creditDays + ' Days Credit Period'"></span>
                        </div>
                        <input type="number" min="0" max="365" name="payment_terms_days" x-model="creditDays" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl font-bold text-sm bg-white focus:ring-2 focus:ring-[#091315] focus:outline-hidden" required>
                        <p class="text-[11px] text-neutral-500">Invoices generated for this client will auto-calculate their Due Date as <b>Invoice Date + <span x-text="creditDays"></span> days</b>.</p>

                        <!-- Quick Chips -->
                        <div class="flex items-center gap-1.5 pt-1 overflow-x-auto">
                            <button type="button" @click="creditDays = 0" class="px-2 py-1 rounded-lg text-[10px] font-bold border transition-colors cursor-pointer" :class="creditDays == 0 ? 'bg-[#091315] text-[#D7FF53] border-black' : 'bg-white hover:bg-neutral-100 text-neutral-700 border-neutral-200'">0D (Advance)</button>
                            <button type="button" @click="creditDays = 15" class="px-2 py-1 rounded-lg text-[10px] font-bold border transition-colors cursor-pointer" :class="creditDays == 15 ? 'bg-[#091315] text-[#D7FF53] border-black' : 'bg-white hover:bg-neutral-100 text-neutral-700 border-neutral-200'">15 Days</button>
                            <button type="button" @click="creditDays = 30" class="px-2 py-1 rounded-lg text-[10px] font-bold border transition-colors cursor-pointer" :class="creditDays == 30 ? 'bg-[#091315] text-[#D7FF53] border-black' : 'bg-white hover:bg-neutral-100 text-neutral-700 border-neutral-200'">30 Days</button>
                            <button type="button" @click="creditDays = 45" class="px-2 py-1 rounded-lg text-[10px] font-bold border transition-colors cursor-pointer" :class="creditDays == 45 ? 'bg-[#091315] text-[#D7FF53] border-black' : 'bg-white hover:bg-neutral-100 text-neutral-700 border-neutral-200'">45 Days</button>
                            <button type="button" @click="creditDays = 60" class="px-2 py-1 rounded-lg text-[10px] font-bold border transition-colors cursor-pointer" :class="creditDays == 60 ? 'bg-[#091315] text-[#D7FF53] border-black' : 'bg-white hover:bg-neutral-100 text-neutral-700 border-neutral-200'">60 Days</button>
                            <button type="button" @click="creditDays = 90" class="px-2 py-1 rounded-lg text-[10px] font-bold border transition-colors cursor-pointer" :class="creditDays == 90 ? 'bg-[#091315] text-[#D7FF53] border-black' : 'bg-white hover:bg-neutral-100 text-neutral-700 border-neutral-200'">90 Days</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Contact Person</label>
                            <input type="text" name="primary_contact_person" value="{{ old('primary_contact_person', $customer->primary_contact_person) }}" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Phone / WhatsApp</label>
                            <input type="text" name="primary_phone" value="{{ old('primary_phone', $customer->primary_phone) }}" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Email</label>
                            <input type="email" name="primary_email" value="{{ old('primary_email', $customer->primary_email) }}" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">City</label>
                            <input type="text" name="city" value="{{ old('city', $customer->city) }}" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">State</label>
                            <input type="text" name="state" value="{{ old('state', $customer->state) }}" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                    </div>

                    <div class="pt-3 border-t border-neutral-100 flex justify-end gap-2 shrink-0">
                        <button type="button" @click="isEditCustomerOpen = false" class="px-4 py-2 font-bold text-neutral-600 hover:bg-neutral-100 rounded-full cursor-pointer">Cancel</button>
                        <button type="submit" class="px-5 py-2 font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs cursor-pointer flex items-center gap-1.5 border border-[#c8f043]">
                            <span>✓ Save Changes</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
@endsection
