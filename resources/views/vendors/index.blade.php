@extends('layouts.app')

@section('title', 'Vendors & Mandi Purchase Registry')

@section('content')
<div class="space-y-6" x-data="{ showAddModal: false, editVendor: null }">

    <!-- Header & Action Controls -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-neutral-900 flex items-center gap-2">
                <span>🌾</span> Raw Material & Packaging Vendors
            </h1>
            <p class="text-xs text-neutral-500 font-medium">Mahuva & Bhavnagar APMC traders, garlic/onion farmers, and packaging suppliers</p>
        </div>

        <div class="flex items-center gap-2">
            <button
                @click="showAddModal = true"
                class="px-4 py-2 bg-[#f53003] hover:bg-[#c42602] text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer"
            >
                <span>+ Add New Vendor</span>
            </button>
        </div>
    </div>

    <!-- Vendors Table / Directory -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <span class="text-xs font-bold text-slate-700">Suppliers Directory ({{ $vendors->count() }})</span>
            <span class="text-[11px] text-slate-500">Track APMC mandi inward supplies, payment terms, and GST details</span>
        </div>

        @if($vendors->isEmpty())
            <div class="p-12 text-center space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl mx-auto font-bold">
                    🌾
                </div>
                <h3 class="text-sm font-bold text-slate-800">No Vendors Added Yet</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">Add your raw garlic/onion APMC mandi suppliers, box makers, and HDPE liner bag vendors.</p>
                <button @click="showAddModal = true" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl shadow-xs cursor-pointer">
                    + Add First Vendor
                </button>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-100/80 text-slate-700 font-bold border-b border-slate-200">
                            <th class="p-3.5">Vendor Name</th>
                            <th class="p-3.5">Code</th>
                            <th class="p-3.5">Contact Person & Phone</th>
                            <th class="p-3.5">Location</th>
                            <th class="p-3.5">GSTIN</th>
                            <th class="p-3.5 text-center">Payment Terms</th>
                            <th class="p-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($vendors as $v)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="p-3.5 font-bold text-slate-900">
                                    {{ $v->company_name }}
                                </td>
                                <td class="p-3.5 font-mono text-[11px] text-slate-500">
                                    {{ $v->vendor_code }}
                                </td>
                                <td class="p-3.5">
                                    <span class="font-bold text-slate-800 block">{{ $v->contact_person ?: '—' }}</span>
                                    <span class="text-[11px] text-slate-500">{{ $v->phone ?: 'No phone' }}</span>
                                </td>
                                <td class="p-3.5 text-slate-600">
                                    {{ $v->city ?: 'Mahuva' }}, {{ $v->state }}
                                </td>
                                <td class="p-3.5 font-mono text-[11px] text-slate-600">
                                    {{ $v->gstin ?: 'UNREGISTERED' }}
                                </td>
                                <td class="p-3.5 text-center">
                                    <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-bold text-[10px]">
                                        {{ $v->payment_terms_days }} Days
                                    </span>
                                </td>
                                <td class="p-3.5 text-right space-x-2">
                                    <button
                                        @click="editVendor = @js($v)"
                                        class="px-2.5 py-1 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors cursor-pointer"
                                    >
                                        Edit
                                    </button>
                                    <form action="{{ route('vendors.destroy', $v->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Delete vendor {{ $v->company_name }}?');">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 text-xs font-bold text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Add Vendor Modal -->
    <template x-teleport="body">
        <div x-show="showAddModal" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-4">
            <div @click.outside="showAddModal = false" class="bg-white rounded-3xl shadow-2xl border border-neutral-200/80 max-w-lg w-full overflow-hidden flex flex-col">
                <div class="p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-2xl bg-[#D7FF53]/20 border border-[#D7FF53]/30 text-[#D7FF53] flex items-center justify-center font-bold text-sm">
                            🌾
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm tracking-tight text-white font-display">Add New Vendor / Mandi Trader</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono">Raw Garlic & Onion Mandi Sourcing</p>
                        </div>
                    </div>
                    <button @click="showAddModal = false" class="text-neutral-400 hover:text-white cursor-pointer transition-colors">✕</button>
                </div>

                <form action="{{ route('vendors.store') }}" method="POST" class="p-5 space-y-3 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Company / Vendor Legal Name *</label>
                        <input type="text" name="company_name" required placeholder="e.g. Mahuva Agro Commodity Traders" class="w-full px-3 py-2 border border-neutral-200 rounded-xl font-bold focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Contact Person</label>
                            <input type="text" name="contact_person" placeholder="e.g. Bharatbhai" class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Phone / Mobile</label>
                            <input type="text" name="phone" placeholder="+91 98250 12345" class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">City</label>
                            <input type="text" name="city" placeholder="Mahuva" class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Payment Terms (Days)</label>
                            <input type="number" name="payment_terms_days" value="15" class="w-full px-3 py-2 border border-neutral-200 rounded-xl font-bold focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">GSTIN (Optional)</label>
                            <input type="text" name="gstin" placeholder="24AAAPG1234F1Z5" class="w-full px-3 py-2 border border-neutral-200 rounded-xl font-mono uppercase focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Email (Optional)</label>
                            <input type="email" name="email" placeholder="vendor@gmail.com" class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-neutral-100">
                        <button type="button" @click="showAddModal = false" class="px-5 py-2 font-bold text-neutral-600 hover:bg-neutral-100 rounded-full transition-colors cursor-pointer">Cancel</button>
                        <button type="submit" class="px-5 py-2 font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs border border-[#c8f043] transition-all cursor-pointer">Save Vendor</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- Edit Vendor Modal -->
    <template x-teleport="body">
        <div x-show="editVendor !== null" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-4">
            <div @click.outside="editVendor = null" class="bg-white rounded-3xl shadow-2xl border border-neutral-200/80 max-w-lg w-full overflow-hidden flex flex-col">
                <div class="p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-2xl bg-[#D7FF53]/20 border border-[#D7FF53]/30 text-[#D7FF53] flex items-center justify-center font-bold text-sm">
                            🌾
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm tracking-tight text-white font-display">Edit Vendor Details</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono" x-text="editVendor ? editVendor.company_name : ''"></p>
                        </div>
                    </div>
                    <button @click="editVendor = null" class="text-neutral-400 hover:text-white cursor-pointer transition-colors">✕</button>
                </div>

                <form :action="'/vendors/' + (editVendor ? editVendor.id : '') + '/update'" method="POST" class="p-5 space-y-3 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Company / Vendor Legal Name *</label>
                        <input type="text" name="company_name" :value="editVendor ? editVendor.company_name : ''" required class="w-full px-3 py-2 border border-neutral-200 rounded-xl font-bold focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Contact Person</label>
                            <input type="text" name="contact_person" :value="editVendor ? editVendor.contact_person : ''" class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Phone / Mobile</label>
                            <input type="text" name="phone" :value="editVendor ? editVendor.phone : ''" class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">City</label>
                            <input type="text" name="city" :value="editVendor ? editVendor.city : ''" class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Payment Terms (Days)</label>
                            <input type="number" name="payment_terms_days" :value="editVendor ? editVendor.payment_terms_days : 15" class="w-full px-3 py-2 border border-neutral-200 rounded-xl font-bold focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">GSTIN</label>
                            <input type="text" name="gstin" :value="editVendor ? editVendor.gstin : ''" class="w-full px-3 py-2 border border-neutral-200 rounded-xl font-mono uppercase focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Email</label>
                            <input type="email" name="email" :value="editVendor ? editVendor.email : ''" class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-neutral-100">
                        <button type="button" @click="editVendor = null" class="px-5 py-2 font-bold text-neutral-600 hover:bg-neutral-100 rounded-full transition-colors cursor-pointer">Cancel</button>
                        <button type="submit" class="px-5 py-2 font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs border border-[#c8f043] transition-all cursor-pointer">Update Vendor</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

</div>
@endsection