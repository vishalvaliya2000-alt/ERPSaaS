@extends('layouts.guest')

@section('title', 'Protected Document Share — ' . config('app.name', 'ERPSaaS'))

@section('content')
<div class="w-full max-w-md bg-white rounded-3xl border border-neutral-200/80 shadow-xl overflow-hidden p-6 sm:p-8">
    <div class="text-center space-y-3 mb-6">
        <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 border border-amber-200/60 flex items-center justify-center text-2xl mx-auto shadow-2xs">
            🔒
        </div>
        <div>
            <h2 class="text-lg font-black text-neutral-900 font-display">Password Protected Share</h2>
            <p class="text-xs text-neutral-500 mt-1">
                Please enter the security PIN or password provided by the supplier to access documents for:
            </p>
            <p class="text-xs font-bold text-neutral-800 font-mono mt-1 px-2.5 py-1 bg-neutral-100 rounded-lg inline-block">
                {{ $share->title ?: ($share->product?->product_code . ' Documentation') }}
            </p>
        </div>
    </div>

    @if(session('error'))
    <div class="mb-5 p-3 rounded-2xl bg-rose-50 border border-rose-200/80 text-rose-700 text-xs font-semibold flex items-center gap-2">
        <span class="text-base shrink-0">⚠️</span>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    <form method="POST" action="{{ route('products.documents.shared.verify', $share->share_token) }}" class="space-y-4">
        @csrf
        <div>
            <label for="password" class="block text-xs font-bold text-neutral-700 mb-1.5">
                Access Password / PIN
            </label>
            <div class="relative">
                <input type="password" id="password" name="password" required autofocus
                    placeholder="Enter access code..."
                    class="w-full px-4 py-3 text-sm font-mono font-bold tracking-wider rounded-2xl border border-neutral-300 focus:outline-none focus:ring-2 focus:ring-[#091315] focus:border-transparent text-center bg-neutral-50/50">
            </div>
        </div>

        <button type="submit"
            class="w-full py-3 px-4 bg-[#091315] hover:bg-black text-[#D7FF53] font-black text-xs uppercase tracking-wider rounded-2xl shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer border border-neutral-800">
            <span>Unlock Documents</span>
            <span>→</span>
        </button>
    </form>

    <div class="mt-6 pt-5 border-t border-neutral-100 text-center">
        <p class="text-[11px] text-neutral-400">
            Need access? Contact the organization who generated this document share link.
        </p>
    </div>
</div>
@endsection
