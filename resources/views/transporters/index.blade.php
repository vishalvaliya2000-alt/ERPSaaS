@extends('layouts.app')

@section('title', 'Transporters & Logistics Directory')

@section('content')
<div class="space-y-6" x-data="{ showAddModal: false, editTransporter: null }">

    <!-- Header & Action Controls -->
    <div class="bg-white p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-neutral-900 flex items-center gap-2">
                <span>🚚</span> Transporters
            </h1>
            <p class="text-xs text-neutral-500 font-medium">Manage transport agencies, branch offices, GSTIN, and dispatch contacts</p>
        </div>

        <div class="flex items-center gap-2">
            <button
                @click="showAddModal = true"
                class="px-4 py-2 bg-[#091315] hover:bg-black text-[#D7FF53] font-bold text-xs rounded-full shadow-2xs transition-all flex items-center gap-1.5 cursor-pointer border border-neutral-800"
            >
                <span>+ Add Transporter</span>
            </button>
        </div>
    </div>

    <!-- Transporters Table / Directory -->
    <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <span class="text-xs font-bold text-slate-700">Registered Transporters ({{ $transporters->count() }})</span>
            <span class="text-[11px] text-slate-500">Auto-populates transporter options in Shipments & LR generation</span>
        </div>

        @if($transporters->isEmpty())
            <div class="p-12 text-center space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl mx-auto font-bold">
                    🚚
                </div>
                <h3 class="text-sm font-bold text-slate-800">No Transporters Added Yet</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">Add Mahuva, Bhavnagar, and All-India transport carriers (e.g. Mahalaxmi Transport, Patel Roadways, Safexpress).</p>
                <button @click="showAddModal = true" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl shadow-xs cursor-pointer">
                    + Add First Transporter
                </button>
            </div>
        @else
            <div class="overflow-x-auto touch-scroll">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-100/80 text-slate-700 font-bold border-b border-slate-200">
                            <th class="p-3.5">Transporter Name</th>
                            <th class="p-3.5">Contact Person & Phone</th>
                            <th class="p-3.5">Branch Location</th>
                            <th class="p-3.5">Transporter GSTIN / ID</th>
                            <th class="p-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($transporters as $t)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="p-3.5 font-bold text-slate-900">
                                    {{ $t->transporter_name }}
                                </td>
                                <td class="p-3.5">
                                    <span class="font-bold text-slate-800 block">{{ $t->contact_person ?: '—' }}</span>
                                    <span class="text-[11px] text-slate-500">{{ $t->phone ?: 'No phone' }}</span>
                                </td>
                                <td class="p-3.5 text-slate-600">
                                    {{ $t->city ?: 'Mahuva' }}, {{ $t->state }}
                                </td>
                                <td class="p-3.5 font-mono text-[11px] text-slate-600">
                                    {{ $t->transporter_id_gst ?: '—' }}
                                </td>
                                <td class="p-3.5 text-right space-x-2">
                                    <button
                                        @click="editTransporter = @js($t)"
                                        class="px-2.5 py-1 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors cursor-pointer"
                                    >
                                        Edit
                                    </button>
                                    <form action="{{ route('transporters.destroy', $t->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Delete transporter {{ $t->transporter_name }}?');">
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

    <!-- Add Transporter Modal -->
    <template x-teleport="body">
        <div x-show="showAddModal" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-2 sm:p-4">
            <div @click.outside="showAddModal = false" class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-neutral-200/80 max-w-lg w-full max-h-[92vh] overflow-hidden flex flex-col">
                <div class="p-4 sm:p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-2xl bg-[#D7FF53]/20 border border-[#D7FF53]/30 text-[#D7FF53] flex items-center justify-center font-bold text-sm">
                            🚛
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm tracking-tight text-white font-display">Add New Transporter</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono">Register Logistics & Fleet Partner</p>
                        </div>
                    </div>
                    <button @click="showAddModal = false" class="text-neutral-400 hover:text-white cursor-pointer transition-colors p-1">✕</button>
                </div>

                <form action="{{ route('transporters.store') }}" method="POST" class="p-4 sm:p-5 space-y-3 text-xs overflow-y-auto flex-1 touch-scroll">
                    @csrf
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Transporter Name *</label>
                        <input type="text" name="transporter_name" required placeholder="e.g. Mahalaxmi Roadlines (Mahuva)" class="w-full px-3 py-2 border border-neutral-200 rounded-xl font-bold focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Contact Person</label>
                            <input type="text" name="contact_person" placeholder="e.g. Hiteshbhai" class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Phone / Mobile</label>
                            <input type="text" name="phone" placeholder="+91 98250 99887" class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">City</label>
                            <input type="text" name="city" placeholder="Mahuva, Bhavnagar" class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Transporter GSTIN / ID</label>
                            <input type="text" name="transporter_id_gst" placeholder="24AAAPM1234F1Z5" class="w-full px-3 py-2 border border-neutral-200 rounded-xl font-mono uppercase focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Branch Office Address</label>
                        <textarea name="branch_address" rows="2" placeholder="GIDC Mahuva Godown No. 5" class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-neutral-100">
                        <button type="button" @click="showAddModal = false" class="px-5 py-2 font-bold text-neutral-600 hover:bg-neutral-100 rounded-full transition-colors cursor-pointer">Cancel</button>
                        <button type="submit" class="px-5 py-2 font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs border border-[#c8f043] transition-all cursor-pointer">Save Transporter</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- Edit Transporter Modal -->
    <template x-teleport="body">
        <div x-show="editTransporter !== null" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-2 sm:p-4">
            <div @click.outside="editTransporter = null" class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-neutral-200/80 max-w-lg w-full max-h-[92vh] overflow-hidden flex flex-col">
                <div class="p-4 sm:p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-2xl bg-[#D7FF53]/20 border border-[#D7FF53]/30 text-[#D7FF53] flex items-center justify-center font-bold text-sm">
                            🚛
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm tracking-tight text-white font-display">Edit Transporter Details</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono" x-text="editTransporter ? editTransporter.transporter_name : ''"></p>
                        </div>
                    </div>
                    <button @click="editTransporter = null" class="text-neutral-400 hover:text-white cursor-pointer transition-colors p-1">✕</button>
                </div>

                <form :action="'/transporters/' + (editTransporter ? editTransporter.id : '') + '/update'" method="POST" class="p-4 sm:p-5 space-y-3 text-xs overflow-y-auto flex-1 touch-scroll">
                    @csrf
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Transporter Name *</label>
                        <input type="text" name="transporter_name" :value="editTransporter ? editTransporter.transporter_name : ''" required class="w-full px-3 py-2 border border-neutral-200 rounded-xl font-bold focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Contact Person</label>
                            <input type="text" name="contact_person" :value="editTransporter ? editTransporter.contact_person : ''" class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Phone / Mobile</label>
                            <input type="text" name="phone" :value="editTransporter ? editTransporter.phone : ''" class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">City</label>
                            <input type="text" name="city" :value="editTransporter ? editTransporter.city : ''" class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Transporter GSTIN / ID</label>
                            <input type="text" name="transporter_id_gst" :value="editTransporter ? editTransporter.transporter_id_gst : ''" class="w-full px-3 py-2 border border-neutral-200 rounded-xl font-mono uppercase focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Branch Office Address</label>
                        <textarea name="branch_address" rows="2" class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden" x-text="editTransporter ? editTransporter.branch_address : ''"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-neutral-100">
                        <button type="button" @click="editTransporter = null" class="px-5 py-2 font-bold text-neutral-600 hover:bg-neutral-100 rounded-full transition-colors cursor-pointer">Cancel</button>
                        <button type="submit" class="px-5 py-2 font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs border border-[#c8f043] transition-all cursor-pointer">Update Transporter</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

</div>
@endsection