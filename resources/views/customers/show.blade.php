@extends('layouts.app')

@section('title', 'Customer 360 — ' . ($customer->company_name ?? 'Account Details'))

@section('content')
<div class="space-y-6 pb-12" x-data='{ tab: "overview", whatsappOpen: false, isQuoteModalOpen: false, isSampleModalOpen: false, noteText: "", msg: "Hi {{ $customer->primary_contact_person ?: $customer->company_name }}, following up regarding our open dehydrated garlic & onion orders and upcoming requirements. Please let me know your schedule." }'>
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
    <div class="bg-white rounded-3xl border border-neutral-200/80 p-6 lg:p-7 shadow-[0_2px_12px_rgba(0,0,0,0.02)]">
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
            </div>
        </div>

        <!-- KPIs & Quantity Fulfillment Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3.5 mt-6 pt-6 border-t border-neutral-100 text-center">
            <div class="bg-[#F5F6F8] p-3 rounded-2xl border border-neutral-200/60">
                <span class="text-[10px] font-bold text-neutral-400 uppercase block font-mono">Total Sales</span>
                <p class="text-base font-black text-neutral-900 mt-1 font-display">{{ formatINR($customer->total_revenue) }}</p>
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
        <div class="bg-[#F3FED4]/60 border border-[#D7FF53] rounded-3xl p-6 shadow-xs">
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
    <div class="flex items-center gap-1.5 p-1.5 bg-[#F5F6F8] rounded-full border border-neutral-200/80 overflow-x-auto w-fit">
        <button @click="tab = 'overview'" :class="tab === 'overview' ? 'bg-[#091315] text-[#D7FF53] shadow-xs' : 'text-neutral-600 hover:text-neutral-900'" class="px-4 py-2 text-xs font-bold rounded-full whitespace-nowrap cursor-pointer transition-all font-display">
            Overview & Contacts
        </button>
        <button @click="tab = 'leads'" :class="tab === 'leads' ? 'bg-[#091315] text-[#D7FF53] shadow-xs' : 'text-neutral-600 hover:text-neutral-900'" class="px-4 py-2 text-xs font-bold rounded-full whitespace-nowrap cursor-pointer transition-all font-display">
            Leads
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
        <button @click="tab = 'purchase_orders'" :class="tab === 'purchase_orders' ? 'bg-[#091315] text-[#D7FF53] shadow-xs' : 'text-neutral-600 hover:text-neutral-900'" class="px-4 py-2 text-xs font-bold rounded-full whitespace-nowrap cursor-pointer transition-all font-display">
            Purchase Orders
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
        <button @click="tab = 'timeline'" :class="tab === 'timeline' ? 'bg-[#091315] text-[#D7FF53] shadow-xs' : 'text-neutral-600 hover:text-neutral-900'" class="px-4 py-2 text-xs font-bold rounded-full whitespace-nowrap cursor-pointer transition-all font-display">
            Timeline & Follow-ups ({{ count($customer->activities) }})
        </button>
    </div>

    <!-- Tab Contents -->
    <div class="bg-white rounded-3xl border border-neutral-200/80 p-6 lg:p-7 shadow-[0_2px_12px_rgba(0,0,0,0.02)]">
        <!-- Overview -->
        <div x-show="tab === 'overview'" class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs">
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

        <!-- Leads -->
        <div x-show="tab === 'leads'" class="space-y-4 text-center py-12">
            <div class="text-4xl mb-3">🎯</div>
            <h3 class="font-bold text-lg text-slate-800">No active leads</h3>
            <p class="text-slate-500 max-w-md mx-auto">This customer has no active CRM leads. Create a new lead to track opportunities.</p>
        </div>

        <!-- Purchase Orders -->
        <div x-show="tab === 'purchase_orders'" class="space-y-4 text-center py-12">
            <div class="text-4xl mb-3">📝</div>
            <h3 class="font-bold text-lg text-slate-800">No Purchase Orders</h3>
            <p class="text-slate-500 max-w-md mx-auto">Upload buyer PO documents directly when creating a Sales Order to see them linked here.</p>
        </div>

        <!-- Payments / Outstanding -->
        <div x-show="tab === 'payments'" class="space-y-4 text-center py-12">
            <div class="text-4xl mb-3">💸</div>
            <h3 class="font-bold text-lg text-slate-800">No Payments Recorded</h3>
            <p class="text-slate-500 max-w-md mx-auto">Record payment receipts against invoices to see the ledger balance here.</p>
        </div>

        <!-- Orders -->
        <div x-show="tab === 'sales_orders'" class="space-y-4">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-slate-700 border-b border-slate-200">
                            <th class="p-3 font-bold">Order No</th>
                            <th class="p-3 font-bold">Date</th>
                            <th class="p-3 font-bold">Items & Granulations</th>
                            <th class="p-3 font-bold text-right">Order Qty</th>
                            <th class="p-3 font-bold text-right">Shipped Qty</th>
                            <th class="p-3 font-bold text-right">Balance Qty</th>
                            <th class="p-3 font-bold text-right">Total Value</th>
                            <th class="p-3 font-bold text-center">Status</th>
                            <th class="p-3 font-bold text-center">Customer PO Doc</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($customer->salesOrders as $so)
                            @php
                                $totalQ = $so->items->sum('order_qty');
                                $shippedQ = $so->items->sum('shipped_qty');
                                $balanceQ = $so->items->sum('balance_qty');
                                $poDoc = $so->poDocument ?? $so->documents->where('document_type', 'PO')->first();
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="p-3 font-bold text-slate-900">{{ $so->order_number }}</td>
                                <td class="p-3 text-slate-600">{{ $so->order_date->format('d M Y') }}</td>
                                <td class="p-3">
                                    @foreach($so->items as $it)
                                        <div class="font-medium text-slate-800">{{ $it->product->product_name }} ({{ number_format($it->order_qty) }} kg @ ₹{{ $it->rate }})</div>
                                    @endforeach
                                </td>
                                <td class="p-3 text-right font-bold text-slate-900">{{ formatQuantity($totalQ) }}</td>
                                <td class="p-3 text-right font-bold text-emerald-600">{{ formatQuantity($shippedQ) }}</td>
                                <td class="p-3 text-right font-bold text-amber-600">{{ formatQuantity($balanceQ) }}</td>
                                <td class="p-3 text-right font-black text-slate-900">{{ formatINR($so->total_amount) }}</td>
                                <td class="p-3 text-center">
                                    <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full {{ $so->status === 'COMPLETED' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                        {{ str_replace('_', ' ', $so->status) }}
                                    </span>
                                </td>
                                <td class="p-3 text-center whitespace-nowrap">
                                    @if($poDoc)
                                        <div class="inline-flex items-center gap-1.5 justify-center">
                                            <a href="{{ route('documents.preview', $poDoc->id) }}" target="_blank" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#091315] text-[#D7FF53] hover:bg-black transition-colors" title="Preview Customer PO Document">
                                                📄 View
                                            </a>
                                            <a href="{{ route('documents.download', $poDoc->id) }}" class="p-0.5 text-neutral-600 hover:text-neutral-900 text-xs" title="Download">
                                                📥
                                            </a>
                                        </div>
                                    @else
                                        <span class="text-neutral-400 italic text-[11px]">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Shipments -->
        <div x-show="tab === 'shipments'" class="space-y-4">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-slate-700 border-b border-slate-200">
                            <th class="p-3 font-bold">Shipment No</th>
                            <th class="p-3 font-bold">Date</th>
                            <th class="p-3 font-bold">Transporter</th>
                            <th class="p-3 font-bold">LR Number</th>
                            <th class="p-3 font-bold">Dispatched Products</th>
                            <th class="p-3 font-bold text-center">Status</th>
                            <th class="p-3 font-bold text-center">LR Document</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($customer->salesOrders->flatMap->shipments as $sh)
                            @php
                                $lrDoc = $sh->lrDocument ?? ($customer->documents->where('document_type', 'LR')->where('commercial_shipment_id', $sh->id)->first());
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="p-3 font-bold text-slate-900">{{ $sh->shipment_number }}</td>
                                <td class="p-3 text-slate-600">{{ $sh->shipment_date->format('d M Y') }}</td>
                                <td class="p-3 font-bold text-slate-800">{{ $sh->transporter }}</td>
                                <td class="p-3">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-bold text-brand-700">{{ $sh->lr_number }}</span>
                                        @if($sh->tracking_url)
                                            <button
                                                type="button"
                                                onclick="openLiveTracking({{ $sh->id }}, '{{ $sh->lr_number }}', '{{ addslashes($sh->transporter) }}')"
                                                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-extrabold bg-blue-50 text-blue-700 hover:bg-blue-100 hover:text-blue-800 border border-blue-200 transition-colors shadow-2xs cursor-pointer active:scale-95"
                                                title="View live shipment tracking details inside ERP"
                                            >
                                                <span>📍</span> Track
                                            </button>
                                        @endif
                                    </div>
                                </td>
                                <td class="p-3">
                                    @foreach($sh->items as $it)
                                        <div>{{ $it->salesOrderItem->product->product_name ?? 'Dehydrated item' }}: <b>{{ number_format($it->quantity) }} kg</b></div>
                                    @endforeach
                                </td>
                                <td class="p-3 text-center">
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 uppercase">{{ $sh->status }}</span>
                                </td>
                                <td class="p-3 text-center whitespace-nowrap">
                                    @if($lrDoc)
                                        <div class="inline-flex items-center gap-1.5 justify-center">
                                            <a href="{{ route('documents.preview', $lrDoc->id) }}" target="_blank" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100 transition-colors" title="Preview Transporter Bilty / LR">
                                                🚚 View
                                            </a>
                                            <a href="{{ route('documents.download', $lrDoc->id) }}" class="p-0.5 text-neutral-600 hover:text-neutral-900 text-xs" title="Download">
                                                📥
                                            </a>
                                        </div>
                                    @else
                                        <span class="text-neutral-400 italic text-[11px]">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Invoices & Receipts -->
        <div x-show="tab === 'invoices'" class="space-y-4">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-slate-700 border-b border-slate-200">
                            <th class="p-3 font-bold">Invoice No</th>
                            <th class="p-3 font-bold">Date</th>
                            <th class="p-3 font-bold">Due Date</th>
                            <th class="p-3 font-bold text-right">Amount</th>
                            <th class="p-3 font-bold text-right">Received</th>
                            <th class="p-3 font-bold text-right">Balance Due</th>
                            <th class="p-3 font-bold text-center">Status</th>
                            <th class="p-3 font-bold text-center">Documents</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($customer->invoices as $inv)
                            @php
                                $suppDoc = $inv->documents->where('document_type', 'INVOICE')->first();
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="p-3 font-mono font-bold text-slate-900">{{ $inv->invoice_number }}</td>
                                <td class="p-3 text-slate-600">{{ $inv->invoice_date->format('d M Y') }}</td>
                                <td class="p-3 text-slate-600">{{ $inv->due_date->format('d M Y') }}</td>
                                <td class="p-3 text-right font-bold text-slate-900">{{ formatINR($inv->total_amount) }}</td>
                                <td class="p-3 text-right font-bold text-emerald-600">{{ formatINR($inv->amount_received) }}</td>
                                <td class="p-3 text-right font-black {{ $inv->balance_due > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                                    {{ formatINR($inv->balance_due) }}
                                </td>
                                <td class="p-3 text-center">
                                    <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full {{ $inv->status === 'PAID' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                        {{ $inv->status }}
                                    </span>
                                </td>
                                <td class="p-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5 justify-center">
                                        <a href="{{ route('invoices.pdf', $inv->id) }}" target="_blank" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#091315] text-[#D7FF53] hover:bg-black transition-colors" title="Official Tax Invoice PDF">
                                            📄 Tax Invoice
                                        </a>
                                        @if($suppDoc)
                                            <a href="{{ route('documents.preview', $suppDoc->id) }}" target="_blank" class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100 transition-colors" title="View Supporting Document">
                                                📎 Doc
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
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

            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                        <tr>
                            <th class="p-3">Quotation #</th>
                            <th class="p-3">Date</th>
                            <th class="p-3">Products & Packaging</th>
                            <th class="p-3 text-right">Subtotal</th>
                            <th class="p-3 text-right">GST (5%)</th>
                            <th class="p-3 text-right">Total Amount</th>
                            <th class="p-3">Payment Terms</th>
                            <th class="p-3 text-center">Status</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @forelse($customer->quotations as $quo)
                            <tr class="hover:bg-slate-50">
                                <td class="p-3 font-bold text-amber-700">
                                    <a href="{{ route('quotations.show', $quo->id) }}" target="_blank" class="hover:underline">
                                        {{ $quo->quotation_number }}
                                    </a>
                                </td>
                                <td class="p-3 text-slate-600">{{ $quo->quotation_date->format('d M Y') }}</td>
                                <td class="p-3 text-slate-800">
                                    @foreach($quo->items as $it)
                                        <div class="text-xs">
                                            <b>{{ $it->product?->product_name }}</b> — {{ number_format($it->quantity) }} KG @ ₹{{ $it->rate }}/KG
                                        </div>
                                    @endforeach
                                </td>
                                <td class="p-3 text-right font-semibold text-slate-700">{{ formatINR($quo->subtotal) }}</td>
                                <td class="p-3 text-right text-slate-500">{{ formatINR($quo->tax_amount) }}</td>
                                <td class="p-3 text-right font-black text-slate-900">{{ formatINR($quo->total_amount) }}</td>
                                <td class="p-3 text-slate-600">{{ $quo->payment_terms }}</td>
                                <td class="p-3 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase">
                                        {{ $quo->status }}
                                    </span>
                                </td>
                                <td class="p-3 text-right space-x-1 whitespace-nowrap">
                                    <a
                                        href="{{ route('quotations.show', $quo->id) }}"
                                        target="_blank"
                                        class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-[11px] inline-block"
                                    >
                                        📄 View PDF
                                    </a>
                                    <a
                                        href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $customer->primary_phone ?: '919826011223') }}?text={{ urlencode('Hi ' . ($customer->primary_contact_person ?: 'Sir') . ', sharing our official Quotation ' . $quo->quotation_number . ' for ' . formatINR($quo->total_amount) . '. View here: ' . route('quotations.show', $quo->id)) }}"
                                        target="_blank"
                                        class="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 font-bold text-[11px] inline-block"
                                    >
                                        💬 WhatsApp
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="p-8 text-center text-xs text-slate-400">
                                    No quotations generated for this customer yet. Click "Generate New Quotation" above.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
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

        <!-- Timeline -->
        <div x-show="tab === 'timeline'" class="space-y-4">
            <form action="{{ route('tasks.store') }}" method="POST" class="flex gap-2">
                @csrf
                <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                <input type="hidden" name="due_date" value="{{ date('Y-m-d') }}">
                <input type="text" name="reason" placeholder="Log a call note, conversation summary, or WhatsApp update..." class="flex-1 px-3 py-2 text-xs border border-slate-300 rounded-xl" required>
                <input type="hidden" name="next_action" value="Review note during next customer discussion">
                <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 rounded-xl">
                    Log Entry
                </button>
            </form>

            <div class="space-y-2 pt-2">
                @foreach($customer->activities as $act)
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-bold text-slate-900">{{ $act->title }}</span>
                            <span class="text-[10px] text-slate-400">{{ $act->occurred_at->format('d M Y') }}</span>
                        </div>
                        <p class="text-slate-600">{{ $act->description }}</p>
                    </div>
                @endforeach
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

            <!-- Documents Table -->
            <div class="border border-neutral-200/80 rounded-2xl overflow-hidden shadow-2xs">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-[#F5F6F8] text-[10px] font-mono text-neutral-500 uppercase border-b border-neutral-200">
                        <tr>
                            <th class="p-3 pl-4">Type</th>
                            <th class="p-3">Document #</th>
                            <th class="p-3">Connected Transaction</th>
                            <th class="p-3">Date</th>
                            <th class="p-3 text-right">Amount (₹)</th>
                            <th class="p-3 text-center">Version</th>
                            <th class="p-3 text-center">Status</th>
                            <th class="p-3 pr-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 bg-white">
                        @forelse($customer->documents as $doc)
                            <tr class="hover:bg-[#F5F6F8]/60 transition-colors">
                                <td class="p-3 pl-4 whitespace-nowrap">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold border {{ $doc->type_badge_color }}">
                                        {{ $doc->type_label }}
                                    </span>
                                </td>
                                <td class="p-3">
                                    <a href="{{ route('documents.preview', $doc->id) }}" target="_blank" class="font-extrabold text-neutral-900 font-display hover:underline">
                                        {{ $doc->document_number }}
                                    </a>
                                    <span class="text-[10px] text-neutral-400 font-mono block">{{ $doc->activeVersion?->file_name ?? 'Document File' }}</span>
                                </td>
                                <td class="p-3 whitespace-nowrap">
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
                                </td>
                                <td class="p-3 font-mono whitespace-nowrap">
                                    {{ $doc->document_date?->format('d M Y') ?? '—' }}
                                </td>
                                <td class="p-3 text-right font-black font-display whitespace-nowrap">
                                    {{ formatINR($doc->total_amount) }}
                                </td>
                                <td class="p-3 text-center whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-[#F5F6F8] text-neutral-700">
                                        v{{ $doc->current_version }}
                                    </span>
                                </td>
                                <td class="p-3 text-center whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase {{ $doc->status === 'PAID' ? 'bg-emerald-50 text-emerald-700' : ($doc->status === 'OVERDUE' ? 'bg-rose-50 text-rose-700' : 'bg-[#F5F6F8] text-neutral-700') }}">
                                        {{ $doc->status }}
                                    </span>
                                </td>
                                <td class="p-3 pr-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('documents.preview', $doc->id) }}" target="_blank" class="px-3 py-1 rounded-full text-[11px] font-bold bg-[#091315] text-[#D7FF53] hover:bg-black transition-all">
                                            View
                                        </a>
                                        <a href="{{ route('documents.download', $doc->id) }}" class="p-1 rounded-full text-neutral-600 hover:bg-neutral-100" title="Download">
                                            📥
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-8 text-center text-neutral-400">
                                    Zero documents uploaded for {{ $customer->company_name }} yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- WhatsApp Dialog -->
    <template x-teleport="body">
        <div x-show="whatsappOpen" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-4">
            <div @click.outside="whatsappOpen = false" class="bg-white rounded-3xl shadow-2xl border border-neutral-200/80 w-full max-w-lg overflow-hidden text-xs flex flex-col">
                <div class="p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5">
                        <span class="text-lg">💬</span>
                        <div>
                            <h3 class="font-extrabold text-sm font-display">WhatsApp Follow-Up</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono">{{ $customer->primary_contact_person ?: $customer->company_name }}</p>
                        </div>
                    </div>
                    <button @click="whatsappOpen = false" class="text-neutral-400 hover:text-white cursor-pointer">✕</button>
                </div>
                <div class="p-5 space-y-4 text-xs">
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
        <div x-show="isQuoteModalOpen" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-4">
            <div @click.outside="isQuoteModalOpen = false" class="bg-white rounded-3xl shadow-2xl border border-neutral-200/80 w-full max-w-2xl overflow-hidden text-xs flex flex-col max-h-[90vh]">
                <div class="p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5">
                        <span class="text-lg">📄</span>
                        <div>
                            <h3 class="font-extrabold text-sm font-display">Generate Formal Quotation / Proforma</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono">For {{ $customer->company_name }}</p>
                        </div>
                    </div>
                    <button @click="isQuoteModalOpen = false" class="text-neutral-400 hover:text-white text-base cursor-pointer">✕</button>
                </div>

                <form action="{{ route('quotations.store') }}" method="POST" class="p-6 space-y-4 overflow-y-auto flex-1" x-data="{ items: [{ product_id: '{{ $products->first()?->id ?? 1 }}', quantity: 5000, rate: 280, packaging: '25 KG Bag / Carton' }] }">
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
        <div x-show="isSampleModalOpen" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-4">
            <div @click.outside="isSampleModalOpen = false" class="bg-white rounded-3xl shadow-2xl border border-neutral-200/80 w-full max-w-lg overflow-hidden text-xs flex flex-col max-h-[90vh]">
                <div class="p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5">
                        <span class="text-lg">🧪</span>
                        <div>
                            <h3 class="font-extrabold text-sm font-display">Dispatch Evaluation Sample</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono">For {{ $customer->company_name }}</p>
                        </div>
                    </div>
                    <button @click="isSampleModalOpen = false" class="text-neutral-400 hover:text-white text-base cursor-pointer">✕</button>
                </div>

                <form action="{{ route('samples.store') }}" method="POST" class="p-6 space-y-4 overflow-y-auto flex-1">
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

                    <div class="grid grid-cols-2 gap-3">
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

                    <div class="grid grid-cols-2 gap-3">
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
</div>
@endsection
