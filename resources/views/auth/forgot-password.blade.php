@extends('layouts.guest')

@section('title', 'Forgot Password')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="text-center space-y-1.5">
        <h2 class="text-2xl font-black text-neutral-900 tracking-tight font-display">Forgot password?</h2>
        <p class="text-xs text-neutral-500">No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.</p>
    </div>

    <!-- Status Alert -->
    @if (session('status'))
        <x-alert type="success">
            {{ session('status') }}
        </x-alert>
    @endif

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

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4 text-xs">
        @csrf

        <!-- Email Address -->
        <x-input
            label="Email Address"
            type="email"
            name="email"
            value="{{ old('email') }}"
            required
            autofocus
            placeholder="name@company.com"
        />

        <div class="pt-2 flex items-center justify-between gap-4">
            <x-button
                href="{{ route('login') }}"
                variant="secondary"
                size="md"
                class="flex-1"
            >
                <span>&larr; Back to login</span>
            </x-button>

            <x-button
                type="submit"
                variant="obsidian"
                size="md"
                class="flex-1 shadow-md"
            >
                <span>Email Password Reset Link</span>
            </x-button>
        </div>
    </form>
</div>
@endsection
