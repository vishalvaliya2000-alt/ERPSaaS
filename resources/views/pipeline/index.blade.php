@extends('layouts.app')

@section('title', 'Leads & Sales Pipeline')

@section('content')
<div class="space-y-6 pb-12" x-data="pipelineEngineApp()">

    <!-- 1. COMPACT PAGE HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 sm:p-5 lg:p-6 rounded-2xl sm:rounded-3xl border border-neutral-200/80 shadow-[0_2px_12px_rgba(0,0,0,0.02)]">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-[#091315] text-[#D7FF53] flex items-center justify-center text-lg shrink-0 shadow-xs border border-neutral-800">
                📑
            </div>
            <div>
                <h2 class="text-xl font-black text-neutral-900 tracking-tight font-display">Lead / Prospect</h2>
                <p class="text-xs text-neutral-500">
                    Track buyer negotiations from RFQ and sample sensory trials to confirmed bulk PO contracts.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <button
                @click="isNewDealOpen = true"
                type="button"
                class="px-5 py-2.5 rounded-full bg-[#D7FF53] hover:bg-[#c8f043] text-[#091315] font-extrabold text-xs shadow-2xs hover:scale-[1.02] active:scale-95 transition-all flex items-center gap-2 cursor-pointer border border-[#c8f043]"
            >
                <span class="font-black text-sm leading-none">+</span>
                <span>Add Lead / Prospect</span>
            </button>
        </div>
    </div>

    <!-- 2. HORIZONTAL PIPELINE STAGES FUNNEL RIBBON -->
    <div class="overflow-x-auto pb-1 -mb-1 no-scrollbar">
        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3 min-w-[700px] xl:min-w-0">
            <!-- All Stages Filter -->
            <button
                type="button"
                @click="activeStageFilter = 'ALL'"
                class="p-3.5 rounded-2xl border text-left transition-all relative overflow-hidden flex flex-col justify-between h-[92px] cursor-pointer group"
                :class="activeStageFilter === 'ALL' ? 'bg-[#091315] text-white border-[#091315] shadow-md ring-2 ring-[#D7FF53]/40' : 'bg-white text-neutral-800 border-neutral-200/80 hover:border-neutral-300 hover:bg-neutral-50/80 shadow-[0_2px_8px_rgba(0,0,0,0.02)]'"
            >
                <div class="flex items-center justify-between gap-1 mb-1">
                    <span class="text-[10px] font-black uppercase tracking-wider font-mono truncate" :class="activeStageFilter === 'ALL' ? 'text-[#D7FF53]' : 'text-neutral-400'">
                        All Stages
                    </span>
                    <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-full shrink-0" :class="activeStageFilter === 'ALL' ? 'bg-[#D7FF53] text-[#091315]' : 'bg-[#F5F6F8] text-neutral-700 border border-neutral-200/60'">
                        {{ $leads->count() }}
                    </span>
                </div>
                <div>
                    <h4 class="font-bold text-xs truncate" :class="activeStageFilter === 'ALL' ? 'text-white' : 'text-neutral-900'">Total Deal Flow</h4>
                    <p class="text-xs font-black mt-0.5 font-mono" :class="activeStageFilter === 'ALL' ? 'text-[#D7FF53]' : 'text-neutral-900'">
                        {{ formatCompactINR($totalPipelineValue + $wonDealsValue) }}
                    </p>
                </div>
            </button>

            @foreach($stageDefinitions as $st)
                @php
                    $count = $leads->where('stage', $st['id'])->count();
                    $val = $leads->where('stage', $st['id'])->sum('estimated_value');
                @endphp
                <button
                    type="button"
                    @click="activeStageFilter = (activeStageFilter === '{{ $st['id'] }}' ? 'ALL' : '{{ $st['id'] }}')"
                    class="p-3.5 rounded-2xl border text-left transition-all relative overflow-hidden flex flex-col justify-between h-[92px] cursor-pointer group"
                    :class="activeStageFilter === '{{ $st['id'] }}' ? 'bg-[#091315] text-white border-[#091315] shadow-md ring-2 ring-[#D7FF53]/40' : 'bg-white text-neutral-800 border-neutral-200/80 hover:border-neutral-300 hover:bg-neutral-50/80 shadow-[0_2px_8px_rgba(0,0,0,0.02)]'"
                >
                    <div class="flex items-center justify-between gap-1 mb-1">
                        <span class="text-[10px] font-black uppercase tracking-wider flex items-center gap-1 font-mono truncate" :class="activeStageFilter === '{{ $st['id'] }}' ? 'text-[#D7FF53]' : 'text-neutral-400'">
                            <span>{{ $st['icon'] }}</span>
                            <span class="truncate">Stage {{ $st['step'] }}</span>
                        </span>
                        <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-full shrink-0" :class="activeStageFilter === '{{ $st['id'] }}' ? 'bg-[#D7FF53] text-[#091315]' : 'bg-[#F5F6F8] text-neutral-700 border border-neutral-200/60'">
                            {{ $count }}
                        </span>
                    </div>
                    <div>
                        <h4 class="font-bold text-xs truncate" :class="activeStageFilter === '{{ $st['id'] }}' ? 'text-white' : 'text-neutral-900'">{{ $st['label'] }}</h4>
                        <p class="text-xs font-black mt-0.5 font-mono" :class="activeStageFilter === '{{ $st['id'] }}' ? 'text-[#D7FF53]' : 'text-neutral-900'">
                            {{ formatCompactINR($val) }}
                        </p>
                    </div>
                </button>
            @endforeach
        </div>
    </div>

    <!-- 3. COMPACT SEARCH & FILTER ROW -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white px-4 py-3 rounded-2xl border border-neutral-200/80 shadow-[0_2px_12px_rgba(0,0,0,0.02)]">
        <div class="flex items-center gap-2.5 flex-1 max-w-lg">
            <span class="text-neutral-400 text-xs">🔍</span>
            <input
                type="text"
                x-model="searchQuery"
                placeholder="Search by buyer company, contact person, city, or product..."
                class="w-full text-xs font-medium text-neutral-900 placeholder:text-neutral-400 bg-transparent outline-hidden"
            >
            <button
                x-show="searchQuery"
                @click="searchQuery = ''"
                class="text-neutral-400 hover:text-neutral-600 text-xs cursor-pointer px-1"
            >✕</button>
        </div>

        <div class="flex items-center gap-2 text-xs font-bold text-neutral-500 shrink-0">
            <span class="text-neutral-400 text-[11px] font-medium">Showing:</span>
            <span class="px-2.5 py-1 rounded-full bg-[#F5F6F8] text-neutral-800 border border-neutral-200/80 font-mono text-[11px]" x-text="filteredLeadsCount() + ' Deals'"></span>
        </div>
    </div>

    <!-- 4. HORIZONTAL COMMERCIAL DEAL STREAM (High-Density B2B CRM Cards) -->
    <div class="space-y-4">
        @forelse($leads as $lead)
            @php
                $stageIndex = match($lead->stage) {
                    'LEAD' => 1,
                    'SAMPLE_REQUESTED' => 2,
                    'QUOTATION_SENT' => 3,
                    'NEGOTIATION' => 4,
                    'WON' => 5,
                    default => 1
                };
            @endphp
            <div
                x-show="shouldShowLead(@js($lead))"
                class="bg-white rounded-2xl sm:rounded-3xl border border-neutral-200/80 p-4 sm:p-5 lg:p-6 shadow-[0_2px_12px_rgba(0,0,0,0.02)] hover:shadow-md transition-all space-y-4 group"
            >
                <!-- Row 1: Company Profile, Deal Value & Quick Contacts -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-neutral-100">
                    <!-- Left: Company & Contact -->
                    <div class="flex items-start gap-3.5">
                        <div class="w-11 h-11 rounded-2xl bg-[#091315] text-[#D7FF53] font-black flex items-center justify-center text-sm shrink-0 border border-neutral-800 shadow-xs font-mono">
                            {{ strtoupper(substr($lead->company_name, 0, 2)) }}
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <h3 class="font-black text-base text-neutral-900 font-display tracking-tight">{{ $lead->company_name }}</h3>
                                @if($lead->city)
                                    <span class="px-2.5 py-0.5 rounded-full bg-[#F5F6F8] text-neutral-600 text-[10px] font-bold border border-neutral-200/60 font-mono">
                                        📍 {{ $lead->city }}, {{ $lead->state ?? 'India' }}
                                    </span>
                                @endif

                                @if($lead->stage === 'WON')
                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 text-[10px] font-black border border-emerald-200 flex items-center gap-1 font-mono">
                                        <span>🏆</span> Active Client Account
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-center gap-3 text-xs text-neutral-600 flex-wrap">
                                <span>Buyer Contact: <b class="text-neutral-900">{{ $lead->contact_person ?: 'Purchase Head' }}</b></span>
                                @if($lead->phone)
                                    <span class="text-neutral-300">•</span>
                                    <span class="font-mono text-neutral-700 font-semibold">{{ $lead->phone }}</span>
                                    <a
                                        href="tel:{{ $lead->phone }}"
                                        class="text-neutral-500 hover:text-neutral-900 font-bold transition-colors"
                                        title="Call Buyer"
                                    >
                                        📞 Call
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Right: Commercial Requirement & Deal Value -->
                    <div class="flex items-center gap-3 lg:text-right flex-wrap">
                        <div class="p-3 bg-[#F5F6F8] rounded-2xl border border-neutral-200/60">
                            <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider block font-mono">Requirement</span>
                            <p class="text-xs font-black text-neutral-900">
                                {{ $lead->interested_products ?: 'Dehydrated Spices / Garlic / Onion' }}
                            </p>
                        </div>

                        @if($lead->estimated_value > 0)
                            <div class="p-3 bg-[#091315] rounded-2xl border border-neutral-800 text-right">
                                <span class="text-[10px] font-bold text-[#D7FF53] uppercase tracking-wider block font-mono">Estimated Deal Value</span>
                                <p class="text-sm font-black text-white font-mono">{{ formatINR($lead->estimated_value) }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Row 2: Interactive Stage Progression Stepper Bar -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="font-bold text-neutral-500 uppercase tracking-wider font-mono">
                            {{ $lead->stage === 'WON' ? 'Deal Stage Completed' : 'Interactive Deal Stage Progression' }}
                        </span>
                        <span class="text-neutral-400 font-medium">
                            {{ $lead->stage === 'WON' ? '✓ Closed Won & Active in Directory' : 'Click any stage to advance deal' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 bg-[#F5F6F8] p-2 rounded-2xl border border-neutral-200/60">
                        @foreach($stageDefinitions as $st)
                            @php
                                $isCurrent = ($lead->stage === $st['id']);
                                $isPassed = ($stageIndex > $st['step']);
                                $isWon = ($lead->stage === 'WON');
                            @endphp
                            @if($isWon)
                                <div
                                    class="py-2.5 px-3 rounded-xl text-xs font-bold flex items-center justify-between gap-1.5 {{ $st['id'] === 'WON' ? 'bg-[#091315] text-[#D7FF53] shadow-xs ring-2 ring-[#D7FF53]/40' : 'bg-emerald-50 text-emerald-800 border border-emerald-200/60 opacity-80' }}"
                                >
                                    <span class="flex items-center gap-1.5 truncate">
                                        <span>{{ $st['icon'] }}</span>
                                        <span class="truncate">{{ $st['label'] }}</span>
                                    </span>
                                    <span class="text-emerald-700 text-xs font-black shrink-0">✓</span>
                                </div>
                            @else
                                <button
                                    type="button"
                                    @click="advanceStage({{ $lead->id }}, '{{ $st['id'] }}')"
                                    class="py-2.5 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-between gap-1.5 cursor-pointer"
                                    :class="{{ $isCurrent ? 'true' : 'false' }}
                                        ? 'bg-[#091315] text-[#D7FF53] shadow-xs ring-2 ring-[#D7FF53]/40 font-black'
                                        : ({{ $isPassed ? 'true' : 'false' }} ? 'bg-emerald-50 text-emerald-900 hover:bg-emerald-100 border border-emerald-200/60' : 'bg-white text-neutral-600 hover:bg-neutral-100 border border-neutral-200/60')"
                                >
                                    <span class="flex items-center gap-1.5 truncate">
                                        <span>{{ $st['icon'] }}</span>
                                        <span class="truncate">{{ $st['label'] }}</span>
                                    </span>
                                    @if($isCurrent)
                                        <span class="w-2 h-2 rounded-full bg-[#D7FF53] animate-pulse shrink-0"></span>
                                    @elseif($isPassed)
                                        <span class="text-emerald-700 text-xs font-black shrink-0">✓</span>
                                    @endif
                                </button>
                            @endif
                        @endforeach
                    </div>
                </div>

                <!-- Row 3: Action Area -->
                @if($lead->stage === 'WON')
                    <!-- Converted State: All prospect functions locked, direct link to Customer 360 -->
                    <div class="pt-2">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 bg-emerald-50/70 rounded-2xl border border-emerald-200/80">
                            <div class="flex items-center gap-2.5 text-xs text-emerald-950 font-bold">
                                <span class="w-7 h-7 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-black text-sm shrink-0 shadow-2xs">✓</span>
                                <div>
                                    <span class="block text-emerald-900 font-bold font-display">Converted into Active Client Account</span>
                                    <span class="text-[11px] text-emerald-700 font-normal">All sales orders, quotations, shipments, and invoicing are now active in Customer 360 directory.</span>
                                </div>
                            </div>
                            <a
                                href="{{ route('customers.show', $lead->converted_customer_id ?? 1) }}"
                                class="px-5 py-2.5 rounded-full text-xs font-bold text-white bg-[#091315] hover:bg-black shadow-2xs flex items-center gap-1.5 shrink-0 transition-all cursor-pointer"
                            >
                                <span>👤 Open Customer 360</span>
                                <span>→</span>
                            </a>
                        </div>
                    </div>
                @else
                    <!-- Active Prospect State: Full negotiation & deal acceleration actions enabled -->
                    <div class="pt-2 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <!-- Scheduled Next Action -->
                        <div class="flex items-center gap-2 text-xs">
                            <span class="px-3 py-1 rounded-full bg-[#F5F6F8] text-neutral-800 font-bold border border-neutral-200/80 flex items-center gap-1.5">
                                <span class="text-amber-600 font-black">👉 Next Step:</span>
                                <span>{{ $lead->next_action ?: 'Follow up on technical requirements' }}</span>
                            </span>
                            @if($lead->next_action_date)
                                <span class="text-[11px] font-bold text-neutral-500 font-mono">
                                    (Due: {{ $lead->next_action_date->format('d M Y') }})
                                </span>
                            @endif
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2 shrink-0 flex-wrap">
                            <!-- 1-Tap WhatsApp Deal Closer -->
                            <button
                                type="button"
                                @click="openWhatsAppCloser(@js($lead))"
                                class="px-3.5 py-2 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200/80 transition-all flex items-center gap-1.5 cursor-pointer shadow-2xs"
                            >
                                <span>💬</span>
                                <span>WhatsApp Closer</span>
                            </button>

                            <!-- Dispatch Sample -->
                            <button
                                type="button"
                                @click="openLeadSampleModal(@js($lead))"
                                class="px-3.5 py-2 rounded-full text-xs font-bold bg-blue-50 text-blue-900 hover:bg-blue-100 border border-blue-200/80 transition-all flex items-center gap-1 cursor-pointer"
                            >
                                <span>🧪</span>
                                <span>Dispatch Sample</span>
                            </button>

                            <!-- Generate Quotation -->
                            <button
                                type="button"
                                @click="openLeadQuoteModal(@js($lead))"
                                class="px-3.5 py-2 rounded-full text-xs font-bold bg-amber-50 text-amber-900 hover:bg-amber-100 border border-amber-200/80 transition-all flex items-center gap-1 cursor-pointer"
                            >
                                <span>📄</span>
                                <span>Generate Quote</span>
                            </button>

                            <!-- Log Communication -->
                            <button
                                type="button"
                                @click="openLogModal(@js($lead))"
                                class="px-3.5 py-2 rounded-full text-xs font-bold bg-[#F5F6F8] text-neutral-700 hover:bg-neutral-200/60 border border-neutral-200/80 transition-all flex items-center gap-1 cursor-pointer"
                            >
                                <span>📝</span>
                                <span>Log Note</span>
                            </button>

                            <!-- 1-Click Snooze Chips -->
                            <div class="flex items-center gap-1 bg-[#F5F6F8] p-1 rounded-full border border-neutral-200/80">
                                <button
                                    type="button"
                                    @click="quickSnoozeDeal(@js($lead), 1)"
                                    title="Snooze to Tomorrow"
                                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold text-neutral-600 hover:bg-white hover:text-neutral-900 transition-all cursor-pointer font-mono"
                                >
                                    +1d
                                </button>
                                <button
                                    type="button"
                                    @click="quickSnoozeDeal(@js($lead), 3)"
                                    title="Snooze to Monday / 3 Days"
                                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold text-neutral-600 hover:bg-white hover:text-neutral-900 transition-all cursor-pointer font-mono"
                                >
                                    +3d
                                </button>
                            </div>

                            <!-- Convert to Customer Button -->
                            <button
                                type="button"
                                @click="openConvertModal(@js($lead))"
                                class="px-4 py-2 rounded-full text-xs font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] border border-[#c8f043] shadow-2xs flex items-center gap-1.5 cursor-pointer"
                            >
                                <span>🎉</span>
                                <span>Convert to Client & Create PO</span>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="p-8 sm:p-10 text-center bg-white rounded-3xl border border-neutral-200/80 shadow-[0_2px_12px_rgba(0,0,0,0.02)] max-w-xl mx-auto space-y-3.5 my-6">
                <div class="w-12 h-12 mx-auto rounded-2xl bg-[#091315] text-[#D7FF53] flex items-center justify-center text-xl shadow-xs border border-neutral-800">
                    🎯
                </div>
                <div class="space-y-1">
                    <h3 class="font-extrabold text-base text-neutral-900 font-display">No Commercial Prospects Found</h3>
                    <p class="text-xs text-neutral-500 max-w-sm mx-auto leading-relaxed">
                        Record your first prospective buyer inquiry to track requirements through sample sensory trials to confirmed purchase orders.
                    </p>
                </div>
                <div class="pt-1">
                    <button
                        @click="isNewDealOpen = true"
                        type="button"
                        class="px-5 py-2.5 rounded-full bg-[#D7FF53] hover:bg-[#c8f043] text-[#091315] font-extrabold text-xs shadow-2xs hover:scale-105 active:scale-95 transition-all inline-flex items-center gap-2 cursor-pointer border border-[#c8f043]"
                    >
                        <span class="font-black text-sm leading-none">+</span>
                        <span>Add Lead / Prospect</span>
                    </button>
                </div>
            </div>
        @endforelse
    </div>

    <!-- 5. MODAL: NEW COMMERCIAL DEAL / RFQ -->
    <template x-teleport="body">
        <div x-show="isNewDealOpen" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-2 sm:p-4">
            <div @click.outside="isNewDealOpen = false" class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-neutral-200/80 w-full max-w-2xl overflow-hidden text-xs flex flex-col max-h-[92vh]">
                <div class="p-4 sm:p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-2xl bg-[#D7FF53]/20 border border-[#D7FF53]/30 text-[#D7FF53] flex items-center justify-center text-lg font-bold">
                            🎯
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm tracking-tight text-white font-display">Register New Commercial Prospect / RFQ</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono">Record buyer specifications, volume & target pricing</p>
                        </div>
                    </div>
                    <button @click="isNewDealOpen = false" class="text-neutral-400 hover:text-white text-base cursor-pointer p-1">✕</button>
                </div>

                <form action="{{ route('pipeline.store') }}" method="POST" class="p-4 sm:p-6 space-y-4 overflow-y-auto flex-1 touch-scroll">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Company Name <span class="text-rose-500">*</span></label>
                            <input type="text" name="company_name" placeholder="e.g. Everest Spices Pvt Ltd" class="w-full px-3 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315]" required>
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Buyer Contact Person</label>
                            <input type="text" name="contact_person" placeholder="e.g. Rajesh Shah (Purchase Head)" class="w-full px-3 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315]">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Phone / WhatsApp Number</label>
                            <input type="text" name="phone" placeholder="e.g. 9820155443" class="w-full px-3 py-2 border border-neutral-300 rounded-xl font-mono focus:ring-2 focus:ring-[#091315]">
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Email Address</label>
                            <input type="email" name="email" placeholder="e.g. procurement@everest.com" class="w-full px-3 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315]">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">City</label>
                            <input type="text" name="city" placeholder="e.g. Mumbai" class="w-full px-3 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315]">
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">State</label>
                            <input type="text" name="state" value="Maharashtra" class="w-full px-3 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315]">
                        </div>
                    </div>

                    <!-- Product & Deal Value Calculation -->
                    <div class="p-4 bg-[#F5F6F8] rounded-2xl border border-neutral-200/80 space-y-3" x-data="{ qty: 10, rate: 280 }">
                        <span class="font-extrabold text-neutral-900 block">Commercial Requirements & Pricing</span>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block font-bold text-neutral-700 mb-1">Product Grade</label>
                                <select name="product_name" class="w-full px-3 py-2 border border-neutral-300 rounded-xl bg-white focus:ring-2 focus:ring-[#091315]">
                                    @foreach($products as $p)
                                        <option value="{{ $p->product_name }}">{{ $p->product_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-neutral-700 mb-1">Volume (Metric Tons)</label>
                                <input type="number" step="0.5" name="quantity_mt" x-model="qty" class="w-full px-3 py-2 border border-neutral-300 rounded-xl bg-white font-bold focus:ring-2 focus:ring-[#091315]">
                            </div>
                            <div>
                                <label class="block font-bold text-neutral-700 mb-1">Target Rate (₹ / KG)</label>
                                <input type="number" step="1" name="target_rate_per_kg" x-model="rate" class="w-full px-3 py-2 border border-neutral-300 rounded-xl bg-white font-bold focus:ring-2 focus:ring-[#091315]">
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-xs pt-2 border-t border-neutral-200 text-neutral-800">
                            <span>Calculated Total Deal Value:</span>
                            <span class="text-sm font-black text-emerald-700 font-mono" x-text="'₹ ' + Number(qty * 1000 * rate).toLocaleString('en-IN')"></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Initial Stage</label>
                            <select name="stage" class="w-full px-3 py-2 border border-neutral-300 rounded-xl bg-white focus:ring-2 focus:ring-[#091315]">
                                <option value="LEAD">1. New Inquiry / RFQ</option>
                                <option value="SAMPLE_REQUESTED" selected>2. Sample Requested / Trial</option>
                                <option value="QUOTATION_SENT">3. Rate Quotation Sent</option>
                                <option value="NEGOTIATION">4. Commercial Negotiation</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Priority</label>
                            <select name="priority" class="w-full px-3 py-2 border border-neutral-300 rounded-xl bg-white focus:ring-2 focus:ring-[#091315]">
                                <option value="URGENT">🔴 Urgent</option>
                                <option value="HIGH" selected>🟠 High</option>
                                <option value="MEDIUM">🟡 Medium</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Mandatory Scheduled Next Action <span class="text-rose-500">*</span></label>
                            <input type="text" name="next_action" placeholder="e.g. Dispatch 500g Garlic Powder sample via DTDC" class="w-full px-3 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315]" required>
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Next Action Due Date</label>
                            <input type="date" name="next_action_date" value="{{ date('Y-m-d', strtotime('+2 days')) }}" class="w-full px-3 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315]">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Buyer Notes / Quality Specs</label>
                        <textarea name="notes" rows="2" placeholder="e.g. Buyer requires max 5% moisture, 80-100 mesh, SO2 under 30 ppm." class="w-full px-3 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315]"></textarea>
                    </div>

                    <div class="pt-4 border-t border-neutral-100 flex justify-end gap-2 shrink-0">
                        <button type="button" @click="isNewDealOpen = false" class="px-4 py-2 font-bold text-neutral-600 hover:bg-neutral-100 rounded-full cursor-pointer">Cancel</button>
                        <button type="submit" class="px-5 py-2 font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs cursor-pointer border border-[#c8f043]">
                            Register Deal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- 6. MODAL: WHATSAPP DEAL CLOSER DRAWER -->
    <template x-teleport="body">
        <div x-show="isWhatsAppCloserOpen" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-2 sm:p-4">
            <div @click.outside="isWhatsAppCloserOpen = false" class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-neutral-200/80 w-full max-w-lg max-h-[92vh] overflow-hidden text-xs flex flex-col">
                <div class="p-4 sm:p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5">
                        <span class="text-lg">💬</span>
                        <div>
                            <h3 class="font-extrabold text-sm font-display">WhatsApp Deal Acceleration</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono" x-text="whatsAppData.recipientName"></p>
                        </div>
                    </div>
                    <button @click="isWhatsAppCloserOpen = false" class="text-neutral-400 hover:text-white text-base cursor-pointer p-1">✕</button>
                </div>

                <div class="p-4 sm:p-5 space-y-4 overflow-y-auto flex-1 touch-scroll">
                    <!-- Template Selector Chips -->
                    <div>
                        <label class="block text-xs font-bold text-neutral-700 mb-1.5">Choose Business Message Template</label>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <button
                                type="button"
                                @click="applyTemplate('specs')"
                                class="px-3 py-1 rounded-full font-bold text-[10px] bg-neutral-100 hover:bg-[#D7FF53] hover:text-[#091315] border border-neutral-200 transition-colors cursor-pointer"
                            >
                                🧪 Lab COA & Specs
                            </button>
                            <button
                                type="button"
                                @click="applyTemplate('sample_awb')"
                                class="px-3 py-1 rounded-full font-bold text-[10px] bg-neutral-100 hover:bg-[#D7FF53] hover:text-[#091315] border border-neutral-200 transition-colors cursor-pointer"
                            >
                                📦 Sample Dispatch AWB
                            </button>
                            <button
                                type="button"
                                @click="applyTemplate('quote')"
                                class="px-3 py-1 rounded-full font-bold text-[10px] bg-neutral-100 hover:bg-[#D7FF53] hover:text-[#091315] border border-neutral-200 transition-colors cursor-pointer"
                            >
                                📄 Price Quotation
                            </button>
                            <button
                                type="button"
                                @click="applyTemplate('po_ack')"
                                class="px-3 py-1 rounded-full font-bold text-[10px] bg-neutral-100 hover:bg-[#D7FF53] hover:text-[#091315] border border-neutral-200 transition-colors cursor-pointer"
                            >
                                🤝 PO Confirmation
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700 mb-1">Message Content</label>
                        <textarea x-model="whatsAppData.message" rows="6" class="w-full p-3.5 border border-neutral-300 rounded-2xl leading-relaxed font-sans focus:ring-2 focus:ring-[#091315]"></textarea>
                    </div>

                    <div class="pt-3 border-t border-neutral-100 flex justify-end gap-2 shrink-0">
                        <button type="button" @click="isWhatsAppCloserOpen = false" class="px-4 py-2 font-bold text-neutral-600 hover:bg-neutral-100 rounded-full cursor-pointer">Cancel</button>
                        <button type="button" @click="launchWhatsApp()" class="px-5 py-2 font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs cursor-pointer flex items-center gap-1.5 border border-[#c8f043]">
                            <span>🚀 Launch WhatsApp Web/App</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- 7. MODAL: CONVERT LEAD TO CUSTOMER & CREATE PO -->
    <template x-teleport="body">
        <div x-show="isConvertModalOpen" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-2 sm:p-4">
            <div @click.outside="isConvertModalOpen = false" class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-neutral-200/80 w-full max-w-lg max-h-[92vh] overflow-hidden text-xs flex flex-col">
                <div class="p-4 sm:p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div>
                        <h3 class="font-extrabold text-sm font-display">Convert Prospect to Active Customer</h3>
                        <p class="text-[11px] text-[#D7FF53] font-mono" x-text="convertData.companyName"></p>
                    </div>
                    <button @click="isConvertModalOpen = false" class="text-neutral-400 hover:text-white cursor-pointer p-1">✕</button>
                </div>

                <form :action="'/pipeline/' + convertData.id + '/convert'" method="POST" class="p-4 sm:p-5 space-y-4 overflow-y-auto flex-1 touch-scroll">
                    @csrf
                    <div class="p-3.5 bg-[#F5F6F8] rounded-2xl border border-neutral-200/80 text-neutral-800 leading-relaxed">
                        This will automatically create an active profile in your <b>Customer 360 directory</b>, register the primary contact, and transition this deal into <b>Closed Won</b>.
                    </div>

                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Customer GST Number</label>
                        <input type="text" name="gst_number" value="24AAACR1234A1Z5" class="w-full px-3 py-2 border border-neutral-300 rounded-xl font-mono text-xs focus:ring-2 focus:ring-[#091315]">
                    </div>

                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Credit Payment Terms</label>
                        <select name="payment_terms_days" class="w-full px-3 py-2 border border-neutral-300 rounded-xl bg-white focus:ring-2 focus:ring-[#091315]">
                            <option value="0">100% Advance / Immediate RTGS</option>
                            <option value="15">15 Days Credit</option>
                            <option value="30" selected>30 Days Credit (Standard)</option>
                            <option value="45">45 Days Credit</option>
                        </select>
                    </div>

                    <div class="pt-4 border-t border-neutral-100 flex justify-end gap-2 shrink-0">
                        <button type="button" @click="isConvertModalOpen = false" class="px-4 py-2 font-bold text-neutral-600 hover:bg-neutral-100 rounded-full cursor-pointer">Cancel</button>
                        <button type="submit" class="px-5 py-2 font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs cursor-pointer flex items-center gap-1.5 border border-[#c8f043]">
                            <span>🎉 Confirm & Convert to Customer</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- 8. MODAL: LOG COMMUNICATION NOTE -->
    <template x-teleport="body">
        <div x-show="isLogModalOpen" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-2 sm:p-4">
            <div @click.outside="isLogModalOpen = false" class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-neutral-200/80 w-full max-w-lg max-h-[92vh] overflow-hidden text-xs flex flex-col">
                <div class="p-4 sm:p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div>
                        <h3 class="font-extrabold text-sm font-display">Log Buyer Communication Note</h3>
                        <p class="text-[11px] text-[#D7FF53] font-mono" x-text="logData.companyName"></p>
                    </div>
                    <button @click="isLogModalOpen = false" class="text-neutral-400 hover:text-white cursor-pointer p-1">✕</button>
                </div>

                <form :action="'/pipeline/' + logData.id + '/activity'" method="POST" class="p-4 sm:p-5 space-y-4 overflow-y-auto flex-1 touch-scroll">
                    @csrf
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Communication Summary <span class="text-rose-500">*</span></label>
                        <textarea name="notes" rows="3" placeholder="e.g. Spoke with purchase team. They approved the 80 mesh Garlic Powder sample. Waiting on rate signoff." class="w-full px-3 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315]" required></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Scheduled Next Action</label>
                            <input type="text" name="next_action" placeholder="e.g. Send revised quotation" class="w-full px-3 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315]">
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Next Action Due Date</label>
                            <input type="date" name="next_action_date" value="{{ date('Y-m-d', strtotime('+2 days')) }}" class="w-full px-3 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315]">
                        </div>
                    </div>

                    <div class="pt-3 border-t border-neutral-100 flex justify-end gap-2 shrink-0">
                        <button type="button" @click="isLogModalOpen = false" class="px-4 py-2 font-bold text-neutral-600 hover:bg-neutral-100 rounded-full cursor-pointer">Cancel</button>
                        <button type="submit" class="px-5 py-2 font-bold text-white bg-[#091315] hover:bg-neutral-800 rounded-full shadow-xs cursor-pointer">
                            Save Communication Log
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- 9. MODAL: GENERATE PROSPECT QUOTATION -->
    <template x-teleport="body">
        <div x-show="isLeadQuoteModalOpen" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-2 sm:p-4">
            <div @click.outside="isLeadQuoteModalOpen = false" class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-neutral-200/80 w-full max-w-2xl overflow-hidden text-xs flex flex-col max-h-[92vh]">
                <div class="p-4 sm:p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5">
                        <span class="text-lg">📄</span>
                        <div>
                            <h3 class="font-extrabold text-sm font-display">Generate Formal Quotation / Proforma</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono" x-text="'For ' + leadQuoteData.companyName"></p>
                        </div>
                    </div>
                    <button @click="isLeadQuoteModalOpen = false" class="text-neutral-400 hover:text-white text-base cursor-pointer p-1">✕</button>
                </div>

                <form action="{{ route('quotations.store') }}" method="POST" class="p-4 sm:p-6 space-y-4 overflow-y-auto flex-1 touch-scroll" x-data="{ items: [{ product_id: '{{ $products->first()?->id ?? 1 }}', quantity: 10000, rate: 280, packaging: '25 KG Bag / Carton' }] }">
                    @csrf
                    <input type="hidden" name="recipient_company" :value="leadQuoteData.companyName">
                    <input type="hidden" name="recipient_name" :value="leadQuoteData.contactPerson">
                    <input type="hidden" name="recipient_phone" :value="leadQuoteData.phone">
                    <input type="hidden" name="recipient_email" :value="leadQuoteData.email">

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
                            <label class="block font-bold text-neutral-700 mb-1">Delivery Timeline</label>
                            <input type="text" name="delivery_timeline" value="7-10 Days from PO Confirmation" class="w-full px-3 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315]">
                        </div>
                    </div>

                    <!-- Quotation Items Repeater -->
                    <div class="space-y-3 pt-2">
                        <div class="flex items-center justify-between">
                            <h4 class="font-bold text-neutral-800">Quotation Line Items</h4>
                            <button
                                type="button"
                                @click="items.push({ product_id: '{{ $products->first()?->id ?? 1 }}', quantity: 2000, rate: 250, packaging: '25 KG Bag / Carton' })"
                                class="text-[11px] font-bold text-neutral-900 bg-[#D7FF53] px-3 py-1 rounded-full cursor-pointer hover:bg-[#c8f043]"
                            >
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
                        <button type="button" @click="isLeadQuoteModalOpen = false" class="px-4 py-2 font-bold text-neutral-600 hover:bg-neutral-100 rounded-full cursor-pointer">Cancel</button>
                        <button type="submit" class="px-5 py-2 font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs cursor-pointer flex items-center gap-1.5 border border-[#c8f043]">
                            <span>📄 Generate Official Quotation</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- 10. MODAL: DISPATCH PROSPECT EVALUATION SAMPLE -->
    <template x-teleport="body">
        <div x-show="isLeadSampleModalOpen" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-2 sm:p-4">
            <div @click.outside="isLeadSampleModalOpen = false" class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-neutral-200/80 w-full max-w-lg overflow-hidden text-xs flex flex-col max-h-[92vh]">
                <div class="p-4 sm:p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5">
                        <span class="text-lg">🧪</span>
                        <div>
                            <h3 class="font-extrabold text-sm font-display">Dispatch Evaluation Sample & Courier AWB</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono" x-text="'For ' + leadSampleData.companyName"></p>
                        </div>
                    </div>
                    <button @click="isLeadSampleModalOpen = false" class="text-neutral-400 hover:text-white text-base cursor-pointer p-1">✕</button>
                </div>

                <form action="{{ route('samples.store') }}" method="POST" class="p-4 sm:p-6 space-y-4 overflow-y-auto flex-1 touch-scroll">
                    @csrf
                    <input type="hidden" name="lead_id" :value="leadSampleData.id">
                    <input type="hidden" name="recipient_name" :value="leadSampleData.contactPerson">

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
                        <input type="text" name="next_action" value="Follow up for sensory trial confirmation & lab approval" class="w-full px-3 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315]" required>
                    </div>

                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Sample Remarks / Quality Mesh</label>
                        <textarea name="remarks" rows="2" placeholder="e.g. 80-100 mesh Garlic Powder sample for seasoning blending trial." class="w-full px-3 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315]"></textarea>
                    </div>

                    <div class="pt-3 border-t border-neutral-100 flex justify-end gap-2 shrink-0">
                        <button type="button" @click="isLeadSampleModalOpen = false" class="px-4 py-2 font-bold text-neutral-600 hover:bg-neutral-100 rounded-full cursor-pointer">Cancel</button>
                        <button type="submit" class="px-5 py-2 font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs cursor-pointer flex items-center gap-1.5 border border-[#c8f043]">
                            <span>🚀 Dispatch Sample & Advance Stage</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

</div>

<script>
    function pipelineEngineApp() {
        return {
            activeStageFilter: 'ALL',
            searchQuery: '',
            isNewDealOpen: false,
            isWhatsAppCloserOpen: false,
            isConvertModalOpen: false,
            isLogModalOpen: false,
            isLeadQuoteModalOpen: false,
            isLeadSampleModalOpen: false,
            whatsAppData: {
                recipientName: '',
                recipientPhone: '',
                message: '',
                leadObj: null
            },
            convertData: {
                id: '',
                companyName: ''
            },
            logData: {
                id: '',
                companyName: ''
            },
            leadQuoteData: {
                id: '',
                companyName: '',
                contactPerson: '',
                phone: '',
                email: ''
            },
            leadSampleData: {
                id: '',
                companyName: '',
                contactPerson: '',
                phone: ''
            },
            leadsList: @js($leads),

            shouldShowLead(lead) {
                const matchesStage = this.activeStageFilter === 'ALL' || lead.stage === this.activeStageFilter;
                if (!matchesStage) return false;

                if (!this.searchQuery.trim()) return true;

                const q = this.searchQuery.toLowerCase();
                const comp = (lead.company_name || '').toLowerCase();
                const contact = (lead.contact_person || '').toLowerCase();
                const city = (lead.city || '').toLowerCase();
                const prod = (lead.interested_products || '').toLowerCase();

                return comp.includes(q) || contact.includes(q) || city.includes(q) || prod.includes(q);
            },

            filteredLeadsCount() {
                return this.leadsList.filter(l => this.shouldShowLead(l)).length;
            },

            async advanceStage(leadId, targetStage) {
                try {
                    const res = await fetch(`/pipeline/${leadId}/stage`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ stage: targetStage })
                    });
                    window.location.reload();
                } catch (e) {
                    alert('Could not update stage. Please try again.');
                }
            },

            openWhatsAppCloser(lead) {
                this.whatsAppData.leadObj = lead;
                this.whatsAppData.recipientName = lead.contact_person || lead.company_name;
                this.whatsAppData.recipientPhone = lead.phone || '+919820155443';
                this.applyTemplate('specs');
                this.isWhatsAppCloserOpen = true;
            },

            applyTemplate(type) {
                const lead = this.whatsAppData.leadObj;
                const contact = lead ? (lead.contact_person || lead.company_name || 'there') : 'there';
                const comp = lead ? lead.company_name : 'Customer';
                const prod = lead ? (lead.interested_products || 'Dehydrated Garlic & Onion') : 'Dehydrated Spices';

                if (type === 'specs') {
                    this.whatsAppData.message = `Hi ${contact},\n\nSharing technical specifications for ${prod}:\n• Moisture: Max 5.0%\n• Ash content: Max 4.0%\n• SO2: Below 30 ppm\n• Mesh Size: Standard 80-100 Mesh\n\nPlease let me know if you require our NABL lab Certificate of Analysis (COA).`;
                } else if (type === 'sample_awb') {
                    this.whatsAppData.message = `Hi ${contact},\n\nYour evaluation sample of ${prod} has been dispatched via DTDC Express (AWB: DTDC-882190). Expected delivery within 48 hours. Please let me know once received for sensory trial.`;
                } else if (type === 'quote') {
                    this.whatsAppData.message = `Hi ${contact},\n\nFollowing up regarding our commercial quotation for ${prod}. We can offer our best ex-factory Mahuva rate with guaranteed export-grade quality. Please let me know your expected PO schedule so we can reserve manufacturing batch slots.`;
                } else if (type === 'po_ack') {
                    this.whatsAppData.message = `Hi ${contact},\n\nThank you for confirming the requirements for ${prod}.\n\nWe are preparing the formal Proforma Invoice & Order Confirmation. Kindly share your billing GST and dispatch destination details.`;
                }
            },

            launchWhatsApp() {
                const phone = this.whatsAppData.recipientPhone.replace(/[^0-9]/g, '');
                const target = phone.length === 10 ? '91' + phone : phone;
                const encoded = encodeURIComponent(this.whatsAppData.message);
                window.open(`https://wa.me/${target}?text=${encoded}`, '_blank');
                this.isWhatsAppCloserOpen = false;
            },

            openConvertModal(lead) {
                this.convertData = {
                    id: lead.id,
                    companyName: lead.company_name
                };
                this.isConvertModalOpen = true;
            },

            openLeadQuoteModal(lead) {
                this.leadQuoteData = {
                    id: lead.id,
                    companyName: lead.company_name,
                    contactPerson: lead.contact_person || '',
                    phone: lead.phone || '',
                    email: lead.email || ''
                };
                this.isLeadQuoteModalOpen = true;
            },

            openLeadSampleModal(lead) {
                this.leadSampleData = {
                    id: lead.id,
                    companyName: lead.company_name,
                    contactPerson: lead.contact_person || '',
                    phone: lead.phone || ''
                };
                this.isLeadSampleModalOpen = true;
            },

            openLogModal(lead) {
                this.logData = {
                    id: lead.id,
                    companyName: lead.company_name
                };
                this.isLogModalOpen = true;
            },

            async quickSnoozeDeal(lead, days) {
                const date = new Date();
                date.setDate(date.getDate() + days);
                const dateStr = date.toISOString().split('T')[0];

                try {
                    const res = await fetch(`/pipeline/${lead.id}/activity`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            notes: `Snoozed for ${days} days by Managing Director`,
                            next_action: lead.next_action || 'Follow up with buyer',
                            next_action_date: dateStr
                        })
                    });
                    window.location.reload();
                } catch (e) {
                    alert('Could not snooze deal. Please try again.');
                }
            }
        }
    }
</script>
@endsection
