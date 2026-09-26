@extends('layouts.guest')

@section('title', 'Sign In')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="text-center space-y-1.5">
        <h2 class="text-2xl font-black text-neutral-900 tracking-tight font-display">Log in to ERPSaaS</h2>
        <p class="text-xs text-neutral-500">Access your multi-tenant operations cockpit and daily decision queue.</p>
    </div>

    <!-- Status Alert -->
    @if (session('status'))
    <x-alert type="success">
        {{ session('status') }}
    </x-alert>
    @endif

    <!-- Validation Errors -->
    @if ($errors->any())
    <x-alert type="error" title="Authentication Error">
        <ul class="list-disc list-inside text-[11px] space-y-0.5 mt-1">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-alert>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4 text-xs" x-data="{ showPassword: false }">
        @csrf

        <!-- Email Address -->
        <x-input label="Email Address" type="email" name="email" value="{{ old('email') }}" required autofocus
            autocomplete="username" placeholder="name@company.com" />

        <!-- Password -->
        <div class="space-y-1.5">
            <div class="flex items-center justify-between">
                <label for="password" class="block text-xs font-bold text-neutral-700">
                    Password <span class="text-rose-500 font-bold ml-0.5">*</span>
                </label>
                @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}"
                    class="text-[11px] text-neutral-500 hover:text-[#091315] transition-colors font-medium">
                    Forgot password?
                </a>
                @endif
            </div>
            <div class="relative rounded-xl shadow-2xs">
                <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required
                    autocomplete="current-password" placeholder="••••••••"
                    class="w-full px-3.5 py-2.5 bg-white border border-neutral-300 rounded-xl text-neutral-900 placeholder-neutral-400 focus:outline-hidden focus:ring-2 focus:ring-[#091315] focus:border-[#091315] transition-all font-medium pr-10 text-xs">
                <button type="button" @click="showPassword = !showPassword"
                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-neutral-400 hover:text-neutral-600 cursor-pointer">
                    <span x-text="showPassword ? '👁️' : '👁️‍🗨️'" class="text-xs"></span>
                </button>
            </div>
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between pt-1">
            <label class="flex items-center gap-2 cursor-pointer select-none">
                <input type="checkbox" name="remember"
                    class="w-4 h-4 rounded border-neutral-300 text-[#091315] focus:ring-[#091315] cursor-pointer">
                <span class="text-neutral-600 font-medium text-xs">Remember me on this device</span>
            </label>
        </div>

        <!-- Submit Button -->
        <x-button type="submit" variant="obsidian" size="lg" class="w-full shadow-md">
            <span>Sign In to Workspace</span>
            <span>→</span>
        </x-button>
    </form>

    <!-- Register Link -->
    @if (Route::has('register'))
    <div class="pt-5 border-t border-neutral-100 text-center space-y-2.5">
        <p class="text-xs text-neutral-500 font-medium">
            New business enterprise?
        </p>
        <x-button href="{{ route('register') }}" variant="secondary" size="md" class="w-full">
            <span>🏢</span>
            <span>Register Your Business</span>
        </x-button>
    </div>
    @endif

</div>
@endsection