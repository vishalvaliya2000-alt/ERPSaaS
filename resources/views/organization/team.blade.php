@extends('layouts.app')

@section('title', 'Team Management & Spatie RBAC')

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="{ showInviteModal: false }">
    <!-- Header -->
    <div class="bg-white p-6 rounded-2xl border border-neutral-200/80 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-neutral-900 text-[#f53003] font-black text-xl flex items-center justify-center shadow-md">
                👥
            </div>
            <div>
                <h1 class="text-2xl font-black text-neutral-900">{{ $tenant?->name }} Team & Access</h1>
                <p class="text-xs text-neutral-500">Manage user accounts, roles, and operational permissions for this organization</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('organization.settings') }}" class="px-4 py-2 text-xs font-bold text-neutral-700 bg-neutral-100 hover:bg-neutral-200 rounded-xl transition-all">
                ← Company Settings
            </a>
            <button @click="showInviteModal = true" class="px-4 py-2 text-xs font-bold text-white bg-[#f53003] hover:bg-[#c42602] rounded-xl shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer">
                <span>+ Add Team Member</span>
            </button>
        </div>
    </div>

    <!-- Team Members Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <span class="text-xs font-bold text-slate-700">Active Members ({{ $members->count() }})</span>
            <span class="text-[11px] text-slate-500">Each member only has access to {{ $tenant?->name }}</span>
        </div>

        <div class="divide-y divide-slate-100">
            @foreach($members as $m)
                <div class="p-4 flex items-center justify-between hover:bg-slate-50/60 transition-colors">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-slate-800 to-slate-950 text-white font-bold text-xs flex items-center justify-center shadow-xs">
                            {{ substr($m->name, 0, 2) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-xs text-slate-900">{{ $m->name }}</span>
                                @if(strtoupper($m->role) === 'OWNER')
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-amber-100 text-amber-800 border border-amber-200">
                                        👑 OWNER
                                    </span>
                                @elseif(strtoupper($m->role) === 'ADMIN')
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-200">
                                        🛡️ ADMIN
                                    </span>
                                @elseif(strtoupper($m->role) === 'SALES')
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        💼 SALES
                                    </span>
                                @elseif(strtoupper($m->role) === 'DISPATCH')
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                        🚚 DISPATCH
                                    </span>
                                @elseif(strtoupper($m->role) === 'ACCOUNTS')
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                        💳 ACCOUNTS
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        👁️ VIEWER
                                    </span>
                                @endif
                            </div>
                            <span class="text-[11px] text-slate-500">{{ $m->email }} · {{ $m->phone ?: 'No phone' }}</span>
                        </div>
                    </div>

                    <div>
                        @if($m->id !== auth()->id() && strtoupper($m->role) !== 'OWNER')
                            <form action="{{ route('organization.team.remove', $m->id) }}" method="POST" onsubmit="return confirm('Remove {{ $m->name }} from {{ $tenant?->name }}?');">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 text-xs font-bold text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer">
                                    Remove
                                </button>
                            </form>
                        @else
                            <span class="text-xs text-slate-400 font-medium italic">Active Account</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Invite Team Member Modal -->
    <template x-teleport="body">
        <div x-show="showInviteModal" x-cloak class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-4">
            <div @click.outside="showInviteModal = false" class="bg-white rounded-3xl shadow-2xl border border-neutral-200/80 max-w-lg w-full overflow-hidden flex flex-col">
                <div class="p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-2xl bg-[#D7FF53]/20 border border-[#D7FF53]/30 text-[#D7FF53] flex items-center justify-center font-bold text-sm">
                            👥
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm tracking-tight text-white font-display">Add Team Member</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono">Grant workspace access to {{ $tenant?->name }}</p>
                        </div>
                    </div>
                    <button @click="showInviteModal = false" class="text-neutral-400 hover:text-white cursor-pointer transition-colors">✕</button>
                </div>

                <form action="{{ route('organization.team.invite') }}" method="POST" class="p-5 space-y-4 text-xs">
                    @csrf

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-neutral-700 mb-1">First Name *</label>
                            <input type="text" name="first_name" required class="w-full px-3 py-2 text-xs border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-neutral-700 mb-1">Last Name *</label>
                            <input type="text" name="last_name" required class="w-full px-3 py-2 text-xs border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700 mb-1">Email Address *</label>
                        <input type="email" name="email" required class="w-full px-3 py-2 text-xs border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700 mb-1">Phone Number</label>
                        <input type="text" name="phone" placeholder="+91..." class="w-full px-3 py-2 text-xs border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-neutral-700 mb-1">Role / Permissions *</label>
                            <select name="role" required class="w-full px-3 py-2 text-xs border border-neutral-200 bg-white rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden font-bold">
                                <option value="ADMIN">🛡️ Admin (Full Access)</option>
                                <option value="SALES">💼 Sales (Pipelines & Quotes)</option>
                                <option value="DISPATCH">🚚 Dispatch (LRs & Shipments)</option>
                                <option value="ACCOUNTS">💳 Accounts (Invoices & Receipts)</option>
                                <option value="VIEWER">👁️ Viewer (Read Only)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-neutral-700 mb-1">Initial Password *</label>
                            <input type="password" name="password" required placeholder="Min 6 characters" class="w-full px-3 py-2 text-xs border border-neutral-200 rounded-xl focus:ring-2 focus:ring-[#091315] focus:outline-hidden font-mono">
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-neutral-100">
                        <button type="button" @click="showInviteModal = false" class="px-5 py-2 rounded-full text-xs font-bold text-neutral-600 hover:bg-neutral-100 transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-full text-xs font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] shadow-2xs border border-[#c8f043] transition-all cursor-pointer">
                            Add Member
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
@endsection