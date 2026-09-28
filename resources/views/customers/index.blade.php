@extends('layouts.app')

@section('title', 'Customers 360 & Ledger Directory')

@section('content')
<div class="space-y-6" x-data="{ isAddCustomerOpen: {{ session('requires_duplicate_confirmation') || $errors->any() ? 'true' : 'false' }} }">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 sm:p-6 lg:p-7 rounded-2xl sm:rounded-3xl border border-neutral-200/80 shadow-[0_2px_12px_rgba(0,0,0,0.02)]">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#F5F6F8] text-neutral-600 border border-neutral-200/80 font-mono uppercase tracking-wider">Directory</span>
                <span class="text-xs text-neutral-300">·</span>
                <span class="text-xs text-neutral-500 font-medium">{{ count($customers) }} Active Accounts</span>
            </div>
            <h2 class="text-2xl font-black text-neutral-900 tracking-tight font-display">Clients</h2>
            <p class="text-xs text-neutral-500 mt-1 max-w-2xl">
                Complete account intelligence, purchase records, ledger balance, total ordered, supplied, and pending dispatch quantities.
            </p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <a href="{{ route('excel.index') }}" class="px-4 py-2.5 rounded-full text-xs font-bold text-neutral-800 bg-[#F5F6F8] hover:bg-neutral-100 border border-neutral-200/80 shadow-2xs transition-all flex items-center gap-1.5">
                <span>📊</span>
                <span>Excel Export & Reports</span>
            </a>
            <button
                @click="isAddCustomerOpen = true"
                class="flex items-center gap-1.5 px-5 py-2.5 rounded-full text-xs font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] border border-[#c8f043] shadow-2xs transition-all cursor-pointer active:scale-95"
            >
                <span>+</span>
                <span>Add Customer</span>
            </button>
        </div>
    </div>

    <!-- Customer Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($customers as $cust)
            @php
                $health = getHealthBadge($cust->health_score);
                $orderedQty = $cust->total_ordered_qty;
                $suppliedQty = $cust->total_supplied_qty;
                $pendingQty = $cust->total_pending_qty;
                $fulfillPct = $cust->fulfillment_percentage;
            @endphp
            <div class="bg-white rounded-2xl sm:rounded-3xl border border-neutral-200/80 p-4 sm:p-6 shadow-[0_2px_12px_rgba(0,0,0,0.02)] hover:border-neutral-400 transition-all flex flex-col justify-between group">
                <div>
                    <!-- Card Top Header -->
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-12 h-12 rounded-2xl bg-[#091315] text-[#D7FF53] font-black text-base flex items-center justify-center shadow-xs shrink-0 border border-neutral-800 font-display">
                                {{ strtoupper(substr($cust->company_name, 0, 2)) }}
                            </div>
                            <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <a href="{{ route('customers.show', $cust->id) }}" class="font-black text-base text-neutral-900 hover:text-[#091315] font-display transition-colors">
                                            {{ $cust->company_name }}
                                        </a>
                                        @if(!empty($cust->trade_name) && $cust->trade_name !== $cust->company_name)
                                            <span class="text-xs font-semibold text-neutral-500 font-sans">({{ $cust->trade_name }})</span>
                                        @endif
                                        <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-600 border border-neutral-200/80">
                                            {{ $cust->customer_code }}
                                        </span>
                                    </div>
                                <p class="text-xs text-neutral-500 mt-0.5 flex items-center gap-1 font-medium">
                                    <span>📍</span>
                                    <span>{{ $cust->city ? $cust->city . ', ' : '' }}{{ $cust->state ?? 'India' }}</span>
                                </p>
                            </div>
                        </div>

                        <span class="text-xs font-bold px-3 py-1 rounded-full border flex items-center gap-1.5 shrink-0 font-mono {{ $health['bg'] }}">
                            <span class="w-2 h-2 rounded-full {{ $health['dot'] }}"></span>
                            {{ $health['label'] }}
                        </span>
                    </div>

                    <!-- Contact & Tax Info -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 p-3.5 bg-[#F5F6F8] rounded-2xl border border-neutral-200/60 text-xs text-neutral-600 mb-3.5">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-neutral-400 block font-mono">Contact Person</span>
                            <span class="font-bold text-neutral-800">{{ $cust->primary_contact_person ?? 'Purchases Head' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-neutral-400 block font-mono">GSTIN</span>
                            <span class="font-mono font-bold text-neutral-800">{{ $cust->gst_number ?? 'Unregistered' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-neutral-400 block font-mono">Phone / WhatsApp</span>
                            <span class="font-medium text-neutral-700">{{ $cust->primary_phone ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-neutral-400 block font-mono">Payment Terms</span>
                            <span class="font-bold text-neutral-800">{{ $cust->payment_terms_days }} Days Credit</span>
                        </div>
                    </div>

                    <!-- QUANTITY BREAKDOWN (Ordered, Supplied, Pending) -->
                    <div class="p-3.5 bg-[#F5F6F8] rounded-2xl border border-neutral-200/60 mb-3.5">
                        <div class="flex items-center justify-between text-[11px] font-bold text-neutral-800 mb-2.5">
                            <span class="flex items-center gap-1 font-display">
                                <span>📦</span>
                                <span>Fulfillment & Dispatch:</span>
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold {{ $pendingQty > 0 ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200' }}">
                                {{ $fulfillPct }}% Supplied
                            </span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-center text-xs">
                            <div class="bg-white p-2.5 rounded-xl border border-neutral-200/80">
                                <span class="text-[10px] uppercase font-bold text-neutral-400 block font-mono">Total Ordered</span>
                                <p class="font-black text-neutral-900 font-display mt-0.5">{{ number_format($orderedQty) }} <span class="text-[10px] font-normal text-neutral-400">KG</span></p>
                            </div>
                            <div class="bg-white p-2.5 rounded-xl border border-neutral-200/80">
                                <span class="text-[10px] uppercase font-bold text-emerald-600 block font-mono">Supplied</span>
                                <p class="font-black text-emerald-600 font-display mt-0.5">{{ number_format($suppliedQty) }} <span class="text-[10px] font-normal text-emerald-500">KG</span></p>
                            </div>
                            <div class="bg-white p-2.5 rounded-xl border border-neutral-200/80">
                                <span class="text-[10px] uppercase font-bold {{ $pendingQty > 0 ? 'text-amber-600' : 'text-neutral-400' }} block font-mono">Pending</span>
                                <p class="font-black {{ $pendingQty > 0 ? 'text-amber-600' : 'text-neutral-400' }} font-display mt-0.5">{{ number_format($pendingQty) }} <span class="text-[10px] font-normal text-neutral-400">KG</span></p>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Summary -->
                    <div class="grid grid-cols-3 gap-2 text-center py-2.5 border-y border-neutral-100 mb-4">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-neutral-400 block font-mono">Total Revenue</span>
                            <p class="font-black text-neutral-900 text-sm font-display mt-0.5">{{ formatINR($cust->total_revenue) }}</p>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-neutral-400 block font-mono">Outstanding</span>
                            <p class="font-black text-sm font-display mt-0.5 {{ $cust->outstanding_amount > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                {{ formatINR($cust->outstanding_amount) }}
                            </p>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-neutral-400 block font-mono">Orders</span>
                            <p class="font-black text-neutral-900 text-sm font-display mt-0.5">{{ count($cust->salesOrders) }} POs</p>
                        </div>
                    </div>

                    <!-- AI Insights -->
                    @if($cust->aiInsights->count() > 0)
                        <div class="p-3.5 bg-[#F3FED4]/60 rounded-2xl border border-[#D7FF53] text-xs mb-4">
                            <span class="text-[#091315] font-extrabold block mb-1 font-display flex items-center gap-1.5">
                                <span>⚡</span>
                                <span>AI Sales Intelligence</span>
                            </span>
                            <p class="text-neutral-700 leading-relaxed">{{ $cust->aiInsights->first()->summary }}</p>
                        </div>
                    @endif
                </div>

                <!-- Bottom Actions -->
                <div class="pt-3 flex items-center justify-between border-t border-neutral-100">
                    <span class="text-xs text-neutral-500 font-medium">
                        {{ $cust->followups->where('status', 'PENDING')->count() }} pending actions
                    </span>

                    <a href="{{ route('customers.show', $cust->id) }}" class="flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-bold text-white bg-[#091315] hover:bg-black transition-all shadow-2xs cursor-pointer active:scale-95">
                        <span>View Full 360 Profile</span>
                        <span>→</span>
                    </a>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Add Customer Modal -->
    <template x-teleport="body">
        <div x-show="isAddCustomerOpen" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-2 sm:p-4">
            <div @click.outside="isAddCustomerOpen = false" class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-neutral-200/80 w-full max-w-lg overflow-hidden flex flex-col text-xs max-h-[92vh]">
                <!-- Modal Header -->
                <div class="p-4 sm:p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-2xl bg-[#D7FF53]/20 border border-[#D7FF53]/30 text-[#D7FF53] flex items-center justify-center text-lg font-bold">
                            🏢
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm tracking-tight text-white font-display">Add New Customer Account</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono">Creates 360 profile, tax ledger & automated tracking</p>
                        </div>
                    </div>
                    <button @click="isAddCustomerOpen = false" class="text-neutral-400 hover:text-white p-1 rounded-lg text-sm cursor-pointer">✕</button>
                </div>

                <form action="{{ route('customers.store') }}" method="POST" class="p-4 sm:p-5 space-y-3.5 text-xs overflow-y-auto flex-1">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block font-bold text-neutral-700">Customer Code</label>
                                <span class="text-[9px] font-mono font-extrabold uppercase px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">Auto-Generated</span>
                            </div>
                            <input type="text" name="customer_code" value="{{ old('customer_code', $nextCustomerCode ?? '') }}" placeholder="Auto-Generated" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl font-mono font-bold bg-neutral-50/70 focus:bg-white focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                            <span class="text-[10px] text-neutral-400 font-mono mt-0.5 block">Pre-filled sequential ID (editable)</span>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block font-bold text-neutral-700">GSTIN Number</label>
                                <span class="text-[10px] text-neutral-400 font-mono">15 Digits</span>
                            </div>
                            <input type="text" name="gst_number" value="{{ old('gst_number') }}" placeholder="24AAACR1234F1Z5" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl font-mono font-bold uppercase focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block font-bold text-neutral-700">
                                Legal Name of Business <span class="text-rose-500">*</span>
                            </label>
                            <span class="text-[10px] text-neutral-400 font-mono">Registered Entity Name</span>
                        </div>
                        <input type="text" name="company_name" value="{{ old('company_name') }}" placeholder="e.g. Balaji Wafers Private Limited" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl font-bold focus:ring-2 focus:ring-[#091315] focus:outline-hidden" required>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block font-bold text-neutral-700">
                                Trade Name
                            </label>
                            <span class="text-[10px] text-neutral-400 font-mono">Brand / Operating Name</span>
                        </div>
                        <input type="text" name="trade_name" value="{{ old('trade_name') }}" placeholder="e.g. Balaji Wafers" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl font-medium focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Primary Contact Person</label>
                            <input type="text" name="primary_contact_person" placeholder="e.g. Rajesh Shah" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Mobile / WhatsApp Number</label>
                            <input type="text" name="primary_phone" placeholder="e.g. 9825012345" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl font-bold focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">City</label>
                            <input type="text" name="city" placeholder="e.g. Rajkot" value="{{ old('city') }}" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">State</label>
                            <input type="text" name="state" value="{{ old('state', 'Gujarat') }}" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Credit Terms (Days)</label>
                            <input type="number" name="payment_terms_days" value="{{ old('payment_terms_days', 30) }}" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl font-bold focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                    </div>

                    @if(session('requires_duplicate_confirmation'))
                        <div class="p-3 bg-red-50 border border-red-200 rounded-xl">
                            <p class="text-red-700 font-bold mb-2">{{ session('error') }}</p>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="confirm_duplicate" value="1" class="w-4 h-4 text-red-600 border-red-300 rounded focus:ring-red-500">
                                <span class="font-bold text-red-900">Confirm Duplicate: Create this record anyway</span>
                            </label>
                        </div>
                    @endif

                    <div class="pt-3 border-t border-neutral-100 flex justify-end gap-2 shrink-0">
                        <button type="button" @click="isAddCustomerOpen = false" class="px-4 py-2 font-bold text-neutral-600 hover:bg-neutral-100 rounded-full cursor-pointer text-xs">Cancel</button>
                        <button type="submit" class="px-5 py-2 font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs cursor-pointer border border-[#c8f043] text-xs active:scale-95 transition-all">
                            Create Customer Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
@endsection
