@extends('layouts.guest')

@section('title', 'Reset Password')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="text-center space-y-1.5">
        <h2 class="text-2xl font-black text-neutral-900 tracking-tight font-display">Reset Password</h2>
        <p class="text-xs text-neutral-500">Please choose a new, secure password for your account.</p>
    </div>

    <!-- Validation Errors -->
    @if ($errors->any())
        <x-alert type="error" title="Validation Error">
            <ul class="list-disc list-inside text-[11px] space-y-0.5 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4 text-xs" x-data="{ showPassword: false }">
        @csrf
        
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <x-input
            label="Email Address"
            type="email"
            name="email"
            value="{{ old('email', $request->email) }}"
            required
            autofocus
            autocomplete="username"
            placeholder="name@company.com"
        />

        <!-- Password -->
        <div class="space-y-1.5">
            <label for="password" class="block text-xs font-bold text-neutral-700">
                New Password <span class="text-rose-500 font-bold ml-0.5">*</span>
            </label>
            <div class="relative rounded-xl shadow-2xs">
                <input
                    :type="showPassword ? 'text' : 'password'"
                    id="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                    class="w-full px-3.5 py-2.5 bg-white border border-neutral-300 rounded-xl text-neutral-900 placeholder-neutral-400 focus:outline-hidden focus:ring-2 focus:ring-[#091315] focus:border-[#091315] transition-all font-medium pr-10 text-xs"
                >
                <button
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-neutral-400 hover:text-neutral-600 cursor-pointer"
                >
                    <span x-show="!showPassword" class="text-xs font-medium">Show</span>
                    <span x-show="showPassword" class="text-xs font-medium" style="display:none;">Hide</span>
                </button>
            </div>
        </div>
        
        <!-- Confirm Password -->
        <div class="space-y-1.5">
            <label for="password_confirmation" class="block text-xs font-bold text-neutral-700">
                Confirm Password <span class="text-rose-500 font-bold ml-0.5">*</span>
            </label>
            <div class="relative rounded-xl shadow-2xs">
                <input
                    :type="showPassword ? 'text' : 'password'"
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                    class="w-full px-3.5 py-2.5 bg-white border border-neutral-300 rounded-xl text-neutral-900 placeholder-neutral-400 focus:outline-hidden focus:ring-2 focus:ring-[#091315] focus:border-[#091315] transition-all font-medium pr-10 text-xs"
                >
            </div>
        </div>

        <div class="pt-2">
            <x-button
                type="submit"
                variant="obsidian"
                size="lg"
                class="w-full shadow-md"
            >
                <span>Reset Password &rarr;</span>
            </x-button>
        </div>
    </form>
</div>
@endsection
