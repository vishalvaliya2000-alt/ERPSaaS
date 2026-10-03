@extends('layouts.guest')

@section('title', 'Link Expired — ' . config('app.name', 'ERPSaaS'))

@section('content')
<div class="w-full max-w-md bg-white rounded-3xl border border-neutral-200/80 shadow-xl overflow-hidden p-6 sm:p-8 text-center space-y-5">
    <div class="w-16 h-16 rounded-3xl bg-rose-50 text-rose-600 border border-rose-200/60 flex items-center justify-center text-3xl mx-auto shadow-2xs">
        ⏳
    </div>

    <div>
        <span class="text-[10px] font-black tracking-widest text-rose-600 uppercase font-mono px-3 py-1 bg-rose-100 rounded-full">
            Expired Link
        </span>
        <h2 class="text-xl font-black text-neutral-900 font-display mt-3">This Share Link Has Expired</h2>
        <p class="text-xs text-neutral-500 mt-2 leading-relaxed">
            The temporary access link for <span class="font-bold text-neutral-800">{{ $share->title ?: 'this product documentation' }}</span> has expired on 
            <span class="font-mono font-semibold text-neutral-700">{{ $share->expires_at ? $share->expires_at->format('d M Y, h:i A') : 'recent date' }}</span>.
        </p>
    </div>

    <div class="p-4 bg-neutral-50 rounded-2xl border border-neutral-200/60 text-xs text-neutral-600 space-y-1 text-left">
        <div class="font-bold text-neutral-800">What should you do?</div>
        <p class="text-neutral-500 text-[11px]">
            For safety and compliance, technical sheets, COAs, and specifications are shared on time-limited tokens. Please request an updated share link from your sales representative or account manager.
        </p>
    </div>

    @if($share->recipient_email)
    <div class="text-[11px] text-neutral-400">
        Originally issued for: <span class="font-mono text-neutral-600 font-medium">{{ $share->recipient_email }}</span>
    </div>
    @endif
</div>
@endsection
