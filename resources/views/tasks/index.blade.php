@extends('layouts.app')

@section('title', 'Follow-up Tasks & Daily Activities')

@section('content')
<div class="space-y-6" x-data="tasksPageApp()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-neutral-200/80 shadow-xs">
        <div>
            <h2 class="text-xl font-black text-neutral-900">Follow-up & Task Engine</h2>
            <p class="text-xs text-neutral-500 mt-1">
                Enforcing the core rule: Every open record has a mandatory outcome and scheduled next action.
            </p>
        </div>

        <!-- Filter Controls -->
        <div class="flex items-center gap-2 flex-wrap">
            <div class="flex bg-neutral-100 p-1 rounded-lg border border-neutral-200">
                <a href="{{ route('tasks.index', ['status' => 'PENDING', 'priority' => $priority]) }}" class="px-2.5 py-1 text-xs font-bold rounded-md {{ $status === 'PENDING' ? 'bg-white text-neutral-900 shadow-xs' : 'text-neutral-500' }}">Pending</a>
                <a href="{{ route('tasks.index', ['status' => 'COMPLETED', 'priority' => $priority]) }}" class="px-2.5 py-1 text-xs font-bold rounded-md {{ $status === 'COMPLETED' ? 'bg-white text-neutral-900 shadow-xs' : 'text-neutral-500' }}">Completed</a>
                <a href="{{ route('tasks.index', ['status' => 'ALL', 'priority' => $priority]) }}" class="px-2.5 py-1 text-xs font-bold rounded-md {{ $status === 'ALL' ? 'bg-white text-neutral-900 shadow-xs' : 'text-neutral-500' }}">All</a>
            </div>

            <button
                @click="isNewTaskOpen = true"
                class="px-3.5 py-1.5 rounded-xl text-xs font-bold text-white bg-[#f53003] hover:bg-[#c42602] shadow-xs transition-colors cursor-pointer"
                + New Follow-up Task
            </button>
        </div>
    </div>

    <!-- Tasks List -->
    <div class="space-y-3">
        @forelse($tasks as $t)
            @php
                $pBadge = getPriorityBadge($t->priority);
                $rel = formatRelativeDate($t->due_date);
                $customerName = $t->customer->company_name ?? $t->lead->company_name ?? 'General Task';
            @endphp
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-2 flex-1">
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <span class="font-bold text-sm text-slate-900">{{ $customerName }}</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border {{ $pBadge['bg'] }}">
                            {{ $pBadge['label'] }}
                        </span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-600 uppercase">
                            {{ str_replace('_', ' ', $t->type) }}
                        </span>
                    </div>

                    <p class="text-xs text-slate-700 font-medium leading-relaxed">
                        <b>Reason / Context:</b> {{ $t->reason }}
                    </p>

                    <div class="p-2.5 bg-amber-50/70 rounded-xl border border-amber-200/80 text-xs flex items-center justify-between">
                        <span class="text-amber-950 font-semibold">
                            👉 <b>Next Action:</b> {{ $t->next_action }}
                        </span>
                        <span class="text-[11px] font-bold {{ $rel['isOverdue'] ? 'text-rose-600' : 'text-amber-800' }}">
                            Due: {{ $rel['text'] }} ({{ $t->due_date->format('d M') }})
                        </span>
                    </div>

                    @if($t->outcome_notes)
                        <div class="p-2 bg-emerald-50 rounded-lg text-xs text-emerald-900">
                            <b>Outcome:</b> {{ $t->outcome_notes }}
                        </div>
                    @endif
                </div>

                @if($t->status === 'PENDING')
                    <div class="flex items-center gap-2 shrink-0">
                        @if($t->customer && $t->customer->primary_phone)
                            <a
                                href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $t->customer->primary_phone) }}?text={{ urlencode('Hi ' . ($t->customer->primary_contact_person ?: 'Sir') . ', this is ' . ($currentUser?->first_name ?? 'our team') . ' from ' . ($currentTenant->name ?? config('app.name', 'ERPSaaS')) . '. Following up regarding ' . $t->reason . '.') }}"
                                target="_blank"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200"
                            >
                                💬 WhatsApp
                            </a>
                        @endif

                        <button
                            type="button"
                            @click="openCompleteModal({{ $t->id }}, '{{ addslashes($customerName) }}', '{{ addslashes($t->reason) }}')"
                            class="px-4 py-1.5 rounded-lg text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 shadow-xs"
                        >
                            ✓ Complete Task
                        </button>
                    </div>
                @else
                    <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-3 py-1 rounded-lg border border-emerald-200">
                        Completed on {{ $t->completed_at ? $t->completed_at->format('d M Y') : 'Recent' }}
                    </span>
                @endif
            </div>
        @empty
            <div class="bg-white p-12 text-center rounded-2xl border border-slate-200 text-xs text-slate-400">
                No tasks found in this view.
            </div>
        @endforelse
    </div>

    <!-- Complete Task Modal -->
    <template x-teleport="body">
        <div x-show="isCompleteOpen" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-4">
            <div @click.outside="isCompleteOpen = false" class="bg-white rounded-3xl shadow-2xl border border-neutral-200/80 w-full max-w-lg overflow-hidden flex flex-col">
                <div class="p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-2xl bg-[#D7FF53]/20 border border-[#D7FF53]/30 text-[#D7FF53] flex items-center justify-center font-bold text-sm">
                            ✓
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm tracking-tight text-white font-display">Complete Task & Schedule Next Step</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono">Log Outcome & Pipeline Continuity</p>
                        </div>
                    </div>
                    <button @click="isCompleteOpen = false" class="text-neutral-400 hover:text-white cursor-pointer transition-colors">✕</button>
                </div>
                <form :action="'/tasks/' + currentTaskId + '/complete'" method="POST" class="p-5 space-y-4">
                    @csrf
                    <div class="p-3.5 bg-[#F5F6F8] rounded-2xl border border-neutral-200/80 text-xs text-neutral-800">
                        <span class="font-bold block mb-0.5 text-neutral-900" x-text="currentCustomerName"></span>
                        <span class="text-neutral-600" x-text="currentTaskReason"></span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700 mb-1">What happened? (Outcome notes) *</label>
                        <textarea name="outcome_notes" rows="3" placeholder="e.g. Spoke with client. Samples approved for test batch." class="w-full p-2.5 text-xs border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden" required></textarea>
                    </div>

                    <div class="pt-3 border-t border-neutral-100" x-data="{ sched: true }">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-neutral-800">Schedule Next Action (Mandatory Rule)</span>
                            <input type="checkbox" name="needs_next_action" value="1" x-model="sched" class="rounded accent-[#091315]">
                        </div>
                        <div x-show="sched" class="space-y-3 p-3.5 bg-[#F5F6F8] rounded-2xl border border-neutral-200/80 text-xs">
                            <div>
                                <label class="block font-bold text-neutral-700 mb-1">Next Action</label>
                                <input type="text" name="next_action" placeholder="e.g. Send proforma quotation" class="w-full px-3 py-2 border border-neutral-200 bg-white rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-neutral-700 mb-1">Due Date</label>
                                    <input type="date" name="next_due_date" value="{{ date('Y-m-d', strtotime('+3 days')) }}" class="w-full px-3 py-2 border border-neutral-200 bg-white rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                                </div>
                                <div>
                                    <label class="block font-bold text-neutral-700 mb-1">Priority</label>
                                    <select name="priority" class="w-full px-3 py-2 border border-neutral-200 bg-white rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                                        <option value="URGENT">🔴 Urgent</option>
                                        <option value="HIGH" selected>🟠 High</option>
                                        <option value="MEDIUM">🟡 Medium</option>
                                        <option value="LOW">⚪ Low</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-neutral-100 flex justify-end gap-2">
                        <button type="button" @click="isCompleteOpen = false" class="px-5 py-2 text-xs font-bold text-neutral-600 hover:bg-neutral-100 rounded-full transition-colors cursor-pointer">Cancel</button>
                        <button type="submit" class="px-5 py-2 text-xs font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs border border-[#c8f043] transition-all cursor-pointer">Complete & Save</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

<script>
    function tasksPageApp() {
        return {
            isCompleteOpen: false,
            currentTaskId: '',
            currentCustomerName: '',
            currentTaskReason: '',
            openCompleteModal(id, name, reason) {
                this.currentTaskId = id;
                this.currentCustomerName = name;
                this.currentTaskReason = reason;
                this.isCompleteOpen = true;
            }
        }
    }
</script>
@endsection
