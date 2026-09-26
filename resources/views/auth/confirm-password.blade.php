@extends('layouts.guest')

@section('title', 'Confirm Password')

@section('content')
<div class="space-y-6">

    <!-- Header & Instructions -->
    <div class="text-center space-y-1">
        <div class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-neutral-100 text-[#f53003] text-lg mb-1">
            🔒
        </div>
        <h2 class="text-2xl font-bold text-neutral-900 tracking-tight">Confirm Password</h2>
        <p class="text-xs text-neutral-500 max-w-xs mx-auto">
            This is a secure area of the application. Please confirm your password before continuing.
        </p>
    </div>

    <!-- Validation Errors -->
    @if ($errors->any())
        <div class="p-3.5 bg-red-50 border border-red-200 rounded-xl text-xs text-red-700 space-y-1">
            <p class="font-bold flex items-center gap-1.5">
                <span>⚠️</span> Confirmation Error:
            </p>
            <ul class="list-disc list-inside text-[11px] pl-1 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4 text-xs">
        @csrf

        <!-- Password -->
        <div>
            <label for="password" class="block font-bold text-neutral-700 mb-1.5">
                Current Password
            </label>
            <input
                type="password"
                id="password"
                name="password"
                required
                autofocus
                autocomplete="current-password"
                placeholder="••••••••"
                class="w-full px-3.5 py-2.5 bg-white border border-neutral-300 rounded-xl text-neutral-900 placeholder-neutral-400 focus:outline-hidden focus:ring-2 focus:ring-[#f53003] focus:border-[#f53003] transition-all font-medium"
            >
        </div>

        <!-- Submit Button -->
        <button
            type="submit"
            class="w-full py-3 px-4 rounded-xl bg-[#f53003] hover:bg-[#c42602] active:scale-[0.99] text-white font-bold text-sm shadow-md shadow-[#f53003]/20 transition-all cursor-pointer flex items-center justify-center gap-2"
        >
            <span>Confirm & Continue</span>
            <span>→</span>
        </button>
    </form>

</div>
@endsection