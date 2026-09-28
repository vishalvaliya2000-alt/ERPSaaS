@extends('layouts.app')

@section('title', 'AI Copilot & Natural Language Query')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="assistantChatApp()">
    <!-- Header -->
    <div class="bg-[#0a0a0a] text-white p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-neutral-800 shadow-xl flex items-center justify-between gap-3">
        <div class="flex items-center gap-3.5">
            <div class="w-10 sm:w-12 h-10 sm:h-12 rounded-2xl bg-[#f53003] text-white font-black text-xl flex items-center justify-center shadow-md shrink-0">
                ⚡
            </div>
            <div>
                <h2 class="text-lg sm:text-xl font-black text-white">{{ $currentTenant->name ?? config('app.name', 'Vyapar ERP') }} AI Assistant</h2>
                <p class="text-xs text-neutral-400">Ask anything about your customers, open orders, overdue invoices, stock balances, or courier tracking.</p>
            </div>
        </div>

        <span class="text-xs font-bold text-emerald-400 bg-emerald-500/10 px-2.5 sm:px-3 py-1.5 rounded-full border border-emerald-500/20 flex items-center gap-1.5 sm:gap-2 shrink-0">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            Online
        </span>
    </div>

    <!-- Suggested Quick Prompts -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs touch-scroll">
        <span class="text-slate-400 font-bold text-[11px] shrink-0">Try asking:</span>
        <button @click="askQuery('What is the outstanding for Monk Foods?')" class="px-3 py-1.5 rounded-full bg-white hover:bg-amber-50 text-slate-700 font-bold border border-slate-200 shadow-2xs shrink-0 cursor-pointer">
            💳 Monk Foods Outstanding
        </button>
        <button @click="askQuery('Which shipments and LRs are in transit?')" class="px-3 py-1.5 rounded-full bg-white hover:bg-amber-50 text-slate-700 font-bold border border-slate-200 shadow-2xs shrink-0 cursor-pointer">
            🚚 Freight Carrier & DTDC In-Transit
        </button>
        <button @click="askQuery('What is our garlic stock available?')" class="px-3 py-1.5 rounded-full bg-white hover:bg-amber-50 text-slate-700 font-bold border border-slate-200 shadow-2xs shrink-0 cursor-pointer">
            📦 Garlic Inventory Stock
        </button>
        <button @click="askQuery('Who should I follow up with today?')" class="px-3 py-1.5 rounded-full bg-white hover:bg-amber-50 text-slate-700 font-bold border border-slate-200 shadow-2xs shrink-0 cursor-pointer">
            🎯 Today's Priority Follow-ups
        </button>
    </div>

    <!-- Chat Messages Container -->
    <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200 p-4 sm:p-6 shadow-xs min-h-[420px] flex flex-col justify-between space-y-4">
        <div class="space-y-4 overflow-y-auto max-h-[500px] p-1" x-ref="messagesBox">
            <!-- Initial Welcome Message -->
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-xl bg-slate-900 text-amber-400 font-bold text-xs flex items-center justify-center shrink-0">
                    AI
                </div>
                <div class="bg-slate-100 rounded-2xl p-4 text-xs text-slate-800 space-y-2 max-w-xl">
                    <p class="font-bold text-slate-900">Good day {{ $currentUser?->first_name ?? 'Sir' }}, I am your {{ $currentTenant->name ?? config('app.name', 'ERP') }} AI Copilot.</p>
                    <p class="text-slate-600 leading-relaxed">
                        I am directly connected to your MySQL ERP database. You can ask me to look up invoice payment statuses, check pending dispatches on POs, check available inventory stocks, or draft WhatsApp messages for your clients.
                    </p>
                </div>
            </div>

            <!-- Dynamic Messages -->
            <template x-for="(msg, idx) in messages" :key="idx">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex items-start gap-3'">
                    <!-- AI Avatar -->
                    <template x-if="msg.role === 'assistant'">
                        <div class="w-8 h-8 rounded-xl bg-slate-900 text-amber-400 font-bold text-xs flex items-center justify-center shrink-0">
                            AI
                        </div>
                    </template>

                    <!-- Bubble -->
                    <div :class="msg.role === 'user' ? 'bg-slate-900 text-white rounded-2xl p-3.5 text-xs max-w-md shadow-xs' : 'bg-amber-50/70 border border-amber-200/80 rounded-2xl p-4 text-xs text-slate-900 space-y-2.5 max-w-xl shadow-xs'">
                        <div class="font-bold text-slate-900" x-show="msg.title" x-text="msg.title"></div>
                        <p class="leading-relaxed" :class="msg.role === 'user' ? 'text-white' : 'text-slate-700'" x-text="msg.text"></p>

                        <!-- Structured Details -->
                        <template x-if="msg.details">
                            <div class="p-3 bg-white rounded-xl border border-slate-200 space-y-1 text-slate-600 text-[11px]">
                                <template x-for="(val, key) in msg.details" :key="key">
                                    <div class="flex justify-between gap-2">
                                        <span class="font-bold text-slate-700" x-text="isNaN(key) ? key + ':' : '•'"></span>
                                        <span class="text-slate-900 font-semibold" x-text="val"></span>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <!-- Action Button -->
                        <template x-if="msg.action_url">
                            <div class="pt-1">
                                <a :href="msg.action_url" class="inline-flex items-center gap-1 font-bold text-brand-700 hover:text-brand-800 text-xs">
                                    <span x-text="msg.action_label || 'View in ERP'"></span> →
                                </a>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <!-- Loading Spinner -->
            <div x-show="isLoading" class="flex items-center gap-3 text-xs text-slate-400">
                <div class="w-8 h-8 rounded-xl bg-slate-900 text-amber-400 font-bold text-xs flex items-center justify-center shrink-0">
                    AI
                </div>
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                    <span>Querying {{ $currentTenant->name ?? config('app.name', 'ERP') }} Database...</span>
                </div>
            </div>
        </div>

        <!-- Input Bar -->
        <form @submit.prevent="sendMessage()" class="flex items-center gap-2 pt-4 border-t border-neutral-100">
            <input
                type="text"
                x-model="inputQuery"
                placeholder="Ask e.g. What is the status of PO138? or How much garlic flakes are in stock?"
                class="flex-1 px-4 py-3 text-xs border border-neutral-200 rounded-full focus:ring-2 focus:ring-[#091315] outline-hidden font-medium"
                :disabled="isLoading"
            >
            <button
                type="submit"
                class="px-6 py-3 rounded-full text-xs font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] shadow-2xs border border-[#c8f043] transition-all flex items-center gap-1.5 shrink-0 disabled:opacity-50 cursor-pointer"
                :disabled="isLoading || !inputQuery.trim()"
            >
                <span>Ask AI</span>
                <span>→</span>
            </button>
        </form>
    </div>
</div>

<script>
    function assistantChatApp() {
        return {
            inputQuery: '',
            isLoading: false,
            messages: [],
            askQuery(text) {
                this.inputQuery = text;
                this.sendMessage();
            },
            async sendMessage() {
                const query = this.inputQuery.trim();
                if (!query || this.isLoading) return;

                this.messages.push({
                    role: 'user',
                    text: query
                });
                this.inputQuery = '';
                this.isLoading = true;

                try {
                    const res = await fetch('{{ route("assistant.query") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ query })
                    });
                    const data = await res.json();
                    this.messages.push({
                        role: 'assistant',
                        title: data.title || null,
                        text: data.answer || 'Query processed.',
                        details: data.details || null,
                        action_url: data.action_url || null,
                        action_label: data.action_label || null
                    });
                } catch (e) {
                    this.messages.push({
                        role: 'assistant',
                        text: 'Sorry, I encountered an issue connecting to the database.'
                    });
                } finally {
                    this.isLoading = false;
                }
            }
        }
    }
</script>
@endsection
