@extends('layouts.guest')

@section('title', 'Two-Factor Challenge')

@section('content')
<div class="space-y-6" x-data="{ recovery: false }">

    <!-- Header & Instructions -->
    <div class="text-center space-y-1">
        <div class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-neutral-100 text-[#f53003] text-lg mb-1">
            🔐
        </div>
        <h2 class="text-2xl font-bold text-neutral-900 tracking-tight">Two-Factor Authentication</h2>
        <p class="text-xs text-neutral-500 max-w-xs mx-auto" x-show="!recovery">
            Please confirm access to your account by entering the authentication code provided by your authenticator application.
        </p>
        <p class="text-xs text-neutral-500 max-w-xs mx-auto" x-cloak x-show="recovery">
            Please confirm access to your account by entering one of your emergency recovery codes.
        </p>
    </div>

    <!-- Validation Errors -->
    @if ($errors->any())
        <div class="p-3.5 bg-red-50 border border-red-200 rounded-xl text-xs text-red-700 space-y-1">
            <p class="font-bold flex items-center gap-1.5">
                <span>⚠️</span> Verification Error:
            </p>
            <ul class="list-disc list-inside text-[11px] pl-1 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-4 text-xs">
        @csrf

        <!-- 6-digit TOTP Code -->
        <div x-show="!recovery">
            <label for="code" class="block font-bold text-neutral-700 mb-1.5 text-center">
                6-Digit Authentication Code
            </label>
            <input
                type="text"
                id="code"
                name="code"
                x-ref="code"
                inputmode="numeric"
                autofocus
                autocomplete="one-time-code"
                placeholder="000000"
                class="w-full px-3.5 py-3 bg-white border border-neutral-300 rounded-xl text-neutral-900 placeholder-neutral-300 focus:outline-hidden focus:ring-2 focus:ring-[#f53003] focus:border-[#f53003] text-center font-mono text-xl tracking-[0.4em] font-bold"
            >
        </div>

        <!-- Emergency Recovery Code -->
        <div x-cloak x-show="recovery">
            <label for="recovery_code" class="block font-bold text-neutral-700 mb-1.5 text-center">
                Emergency Recovery Code
            </label>
            <input
                type="text"
                id="recovery_code"
                name="recovery_code"
                x-ref="recovery_code"
                autocomplete="one-time-code"
                placeholder="abcdef1234-..."
                class="w-full px-3.5 py-2.5 bg-white border border-neutral-300 rounded-xl text-neutral-900 placeholder-neutral-400 focus:outline-hidden focus:ring-2 focus:ring-[#f53003] focus:border-[#f53003] text-center font-mono text-sm"
            >
        </div>

        <!-- Submit Button -->
        <button
            type="submit"
            class="w-full py-3 px-4 rounded-xl bg-[#f53003] hover:bg-[#c42602] active:scale-[0.99] text-white font-bold text-sm shadow-md shadow-[#f53003]/20 transition-all cursor-pointer flex items-center justify-center gap-2"
        >
            <span>Verify & Continue</span>
            <span>→</span>
        </button>

        <!-- Toggle Recovery Option -->
        <div class="pt-2 text-center">
            <button
                type="button"
                class="text-xs text-neutral-600 hover:text-[#f53003] font-semibold underline cursor-pointer"
                @click="recovery = !recovery; $nextTick(() => { if (recovery) { $refs.recovery_code.focus(); } else { $refs.code.focus(); } })"
            >
                <span x-show="!recovery">Use an emergency recovery code</span>
                <span x-cloak x-show="recovery">Use an authenticator app code</span>
            </button>
        </div>
    </form>

    <!-- Back to Login / Cancel -->
    <div class="pt-4 border-t border-neutral-100 text-center">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-xs text-neutral-400 hover:text-neutral-600 cursor-pointer">
                Cancel and back to sign in
            </button>
        </form>
    </div>

</div>
@endsection