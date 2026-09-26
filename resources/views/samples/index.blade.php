@extends('layouts.app')

@section('title', 'Sample Dispatch & Courier Tracking')

@section('content')
<div class="space-y-6" x-data="samplesPageApp()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-neutral-200/80 shadow-xs">
        <div>
            <h2 class="text-xl font-black text-neutral-900">Samples Evaluation & Trial Lifecycle</h2>
            <p class="text-xs text-neutral-500 mt-1">
                Track client sample requests from dispatch to courier delivery, sensory trial feedback, internal remarks, and conversion to PO.
            </p>
        </div>

        <button
            @click="isNewSampleOpen = true"
            class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-[#f53003] hover:bg-[#c42602] shadow-xs transition-colors cursor-pointer"
        >
            + Dispatch New Sample
        </button>
    </div>

    <!-- Samples Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @foreach($samples as $s)
            @php
                $statusColors = [
                    'PREPARED' => 'bg-slate-100 text-slate-800 border-slate-200',
                    'DISPATCHED' => 'bg-blue-50 text-blue-800 border-blue-200',
                    'IN_TRANSIT' => 'bg-amber-50 text-amber-800 border-amber-200',
                    'DELIVERED' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                    'APPROVED' => 'bg-emerald-100 text-emerald-900 border-emerald-300',
                ];
                $color = $statusColors[$s->delivery_status] ?? 'bg-slate-100 text-slate-800 border-slate-200';
            @endphp
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4 flex flex-col justify-between">
                <div>
                    <!-- Header -->
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 pb-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-base text-slate-900">{{ $s->product->product_name }}</h3>
                                <span class="font-mono text-xs font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">
                                    {{ $s->sample_number }}
                                </span>
                            </div>
                            <a href="{{ $s->customer_id ? route('customers.show', $s->customer_id) : '#' }}" class="text-xs font-bold text-brand-600 hover:underline">
                                {{ $s->customer->company_name ?? 'Prospect' }}
                            </a>
                        </div>
                        <span class="text-xs font-bold px-2.5 py-1 rounded-full border uppercase {{ $color }}">
                            {{ str_replace('_', ' ', $s->delivery_status) }}
                        </span>
                    </div>

                    <!-- Details -->
                    <div class="grid grid-cols-2 gap-2 p-3 bg-slate-50 rounded-xl text-xs text-slate-600 my-3">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-400 block">Quantity & Batch</span>
                            <span class="font-bold text-slate-800">{{ $s->quantity }} {{ $s->uom }} ({{ $s->batch_number ?: 'Standard' }})</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-400 block">Courier & AWB</span>
                            <span class="font-mono font-bold text-brand-700">{{ $s->courier_provider }}: {{ $s->awb_number }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-400 block">Trial / Lab Status</span>
                            <span class="font-bold text-slate-800">{{ $s->trial_status }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-400 block">Delivered On</span>
                            <span class="text-slate-700">{{ $s->delivered_at ? $s->delivered_at->format('d M Y') : 'In Transit' }}</span>
                        </div>
                    </div>

                    <!-- Remarks -->
                    @if($s->remarks)
                        <div class="p-2.5 bg-slate-100 rounded-xl text-xs text-slate-800 mb-2">
                            <span class="font-bold text-[11px] text-slate-600 block">📝 Remarks:</span>
                            <p class="text-slate-800 mt-0.5">{{ $s->remarks }}</p>
                        </div>
                    @endif

                    <!-- Next Action -->
                    <div class="p-3 bg-amber-50 rounded-xl border border-amber-200/80 text-xs text-amber-950">
                        <span class="font-bold block mb-0.5">👉 Next Scheduled Action:</span>
                        <span>{{ $s->next_action }}</span>
                    </div>

                    @if($s->customer_feedback)
                        <div class="p-2.5 bg-emerald-50 rounded-xl text-xs text-emerald-950 mt-2">
                            <b>Evaluation Feedback:</b> {{ $s->customer_feedback }}
                        </div>
                    @endif
                </div>

                <!-- Footer Actions -->
                <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                    <button
                        type="button"
                        @click="openFeedbackModal({{ $s->id }}, '{{ addslashes($s->customer->company_name ?? 'Client') }}', '{{ addslashes($s->remarks ?? '') }}')"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200"
                    >
                        📝 Edit Feedback / Remarks
                    </button>

                    @if($s->customer && $s->customer->primary_phone)
                        <a
                            href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $s->customer->primary_phone) }}?text={{ urlencode('Hi ' . ($s->customer->primary_contact_person ?: 'Sir') . ', this is ' . ($currentUser?->first_name ?? 'our team') . ' from ' . ($currentTenant->name ?? config('app.name', 'ERPSaaS')) . '. Following up regarding your sample evaluation of ' . $s->product->product_name . ' (AWB: ' . $s->awb_number . '). Please share your feedback.') }}"
                            target="_blank"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200"
                        >
                            💬 WhatsApp Feedback
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <!-- Dispatch Sample Modal -->
    <template x-teleport="body">
        <div x-show="isNewSampleOpen" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-4">
            <div @click.outside="isNewSampleOpen = false" class="bg-white rounded-3xl shadow-2xl border border-neutral-200/80 w-full max-w-lg overflow-hidden flex flex-col">
                <div class="p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-2xl bg-[#D7FF53]/20 border border-[#D7FF53]/30 text-[#D7FF53] flex items-center justify-center font-bold text-sm">
                            🧪
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm tracking-tight text-white font-display">Dispatch Commercial Sample</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono">Send sample with DTDC/Trackon AWB & internal remarks</p>
                        </div>
                    </div>
                    <button @click="isNewSampleOpen = false" class="text-neutral-400 hover:text-white cursor-pointer transition-colors">✕</button>
                </div>
                <form action="{{ route('samples.store') }}" method="POST" class="p-5 space-y-3 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Customer / Recipient Account</label>
                        <select name="customer_id" class="w-full px-3 py-2 border border-neutral-200 bg-white rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden" required>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->company_name }} ({{ $c->city }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Product Cut</label>
                            <select name="product_id" class="w-full px-3 py-2 border border-neutral-200 bg-white rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden" required>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}">{{ $p->product_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Sample Quantity (KG)</label>
                            <input type="number" step="0.5" name="quantity" value="1.0" class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden" required>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Courier Partner</label>
                            <select name="courier_provider" class="w-full px-3 py-2 border border-neutral-200 bg-white rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                                <option value="DTDC" selected>DTDC Express</option>
                                <option value="Trackon">Trackon Courier</option>
                                <option value="BlueDart">Blue Dart</option>
                                <option value="Professional">Professional Courier</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">AWB / Consignment No.</label>
                            <input type="text" name="awb_number" placeholder="e.g. D49182799" class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Remarks / Internal Notes</label>
                        <textarea name="remarks" rows="2" placeholder="e.g. Client requested 100-mesh fine powder sample with low pungency." class="w-full p-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden"></textarea>
                    </div>
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Scheduled Next Action</label>
                        <input type="text" name="next_action" value="Follow up for sensory and solubility lab feedback" class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden" required>
                    </div>
                    <div class="pt-3 border-t border-neutral-100 flex justify-end gap-2">
                        <button type="button" @click="isNewSampleOpen = false" class="px-5 py-2 font-bold text-neutral-600 hover:bg-neutral-100 rounded-full transition-colors cursor-pointer">Cancel</button>
                        <button type="submit" class="px-5 py-2 font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs border border-[#c8f043] transition-all cursor-pointer">Dispatch Sample</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- Edit Feedback & Remarks Modal -->
    <template x-teleport="body">
        <div x-show="isFeedbackOpen" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-4">
            <div @click.outside="isFeedbackOpen = false" class="bg-white rounded-3xl shadow-2xl border border-neutral-200/80 w-full max-w-lg overflow-hidden flex flex-col">
                <div class="p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-2xl bg-[#D7FF53]/20 border border-[#D7FF53]/30 text-[#D7FF53] flex items-center justify-center font-bold text-sm">
                            📝
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm tracking-tight text-white font-display">Update Evaluation Feedback & Remarks</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono">Sensory lab outcome & approval</p>
                        </div>
                    </div>
                    <button @click="isFeedbackOpen = false" class="text-neutral-400 hover:text-white cursor-pointer transition-colors">✕</button>
                </div>
                <form :action="'/samples/' + currentSampleId + '/status'" method="POST" class="p-5 space-y-3 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Delivery Status</label>
                        <select name="delivery_status" class="w-full px-3 py-2 border border-neutral-200 bg-white rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                            <option value="DELIVERED">Delivered</option>
                            <option value="APPROVED" selected>Approved (Trial Passed)</option>
                            <option value="REJECTED">Rejected (Specification Issue)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Client Feedback & Sensory Evaluation</label>
                        <textarea name="customer_feedback" rows="2" placeholder="e.g. Good aroma, passed moisture and particle size check. Approved for bulk order." class="w-full p-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden"></textarea>
                    </div>
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Remarks</label>
                        <textarea name="remarks" x-model="currentRemarks" rows="2" placeholder="Internal remarks..." class="w-full p-2.5 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden"></textarea>
                    </div>
                    <div class="pt-3 border-t border-neutral-100 flex justify-end gap-2">
                        <button type="button" @click="isFeedbackOpen = false" class="px-5 py-2 font-bold text-neutral-600 hover:bg-neutral-100 rounded-full transition-colors cursor-pointer">Cancel</button>
                        <button type="submit" class="px-5 py-2 font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs border border-[#c8f043] transition-all cursor-pointer">Save Updates</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

<script>
    function samplesPageApp() {
        return {
            isNewSampleOpen: false,
            isFeedbackOpen: false,
            currentSampleId: '',
            currentRemarks: '',
            openFeedbackModal(id, client, remarks) {
                this.currentSampleId = id;
                this.currentRemarks = remarks;
                this.isFeedbackOpen = true;
            }
        }
    }
</script>
@endsection
