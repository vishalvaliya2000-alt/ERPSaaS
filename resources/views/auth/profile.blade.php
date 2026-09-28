@extends('layouts.app')

@section('title', 'My Account & Security (2FA & RBAC)')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header -->
    <div class="bg-white p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-neutral-200/80 shadow-[0_2px_12px_rgba(0,0,0,0.02)] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-neutral-900 tracking-tight font-display">My Account & Security</h2>
            <p class="text-xs text-neutral-500 mt-1">
                Manage your user profile credentials, two-factor authentication, and RBAC permissions.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span class="px-3.5 py-1 rounded-full text-xs font-bold bg-[#F3FED4] text-[#091315] border border-[#D7FF53]">
                Role: {{ $user->role ?? 'ADMIN' }}
            </span>
        </div>
    </div>

    <!-- Account Details & Password Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- 1. Profile Information -->
        <div class="bg-white p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-neutral-200/80 shadow-[0_2px_12px_rgba(0,0,0,0.02)] flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-3.5 pb-4 mb-4 border-b border-neutral-100">
                    <div class="w-12 h-12 rounded-2xl bg-[#091315] text-[#D7FF53] font-black text-lg flex items-center justify-center shadow-xs border border-neutral-800 font-display">
                        {{ $user->initials ?? 'VV' }}
                    </div>
                    <div>
                        <h3 class="font-black text-base text-neutral-900 font-display">{{ $user->name }}</h3>
                        <p class="text-xs text-neutral-500 font-mono">{{ $user->email }}</p>
                    </div>
                </div>

                <form action="{{ route('profile.update') }}" method="POST" class="space-y-4 text-xs">
                    @csrf

                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Full Display Name *</label>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name', $user->name) }}"
                            required
                            class="w-full px-3.5 py-2.5 border border-neutral-300 rounded-xl font-bold text-neutral-900 focus:ring-2 focus:ring-[#091315] focus:outline-hidden text-xs"
                        >
                    </div>

                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Work Email (System ID)</label>
                        <input
                            type="email"
                            value="{{ $user->email }}"
                            disabled
                            class="w-full px-3.5 py-2.5 border border-neutral-200 bg-[#F5F6F8] rounded-xl text-neutral-500 cursor-not-allowed font-medium text-xs font-mono"
                        >
                        <span class="text-[10px] text-neutral-400 mt-1 block font-mono">Email is locked to primary administrator credentials.</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Designation</label>
                            <input
                                type="text"
                                name="designation"
                                value="{{ old('designation', $user->designation) }}"
                                placeholder="e.g. Managing Director"
                                class="w-full px-3.5 py-2.5 border border-neutral-300 rounded-xl text-neutral-800 focus:ring-2 focus:ring-[#091315] focus:outline-hidden text-xs font-medium"
                            >
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Contact Phone</label>
                            <input
                                type="text"
                                name="phone"
                                value="{{ old('phone', $user->phone) }}"
                                placeholder="e.g. 9825012345"
                                class="w-full px-3.5 py-2.5 border border-neutral-300 rounded-xl text-neutral-800 focus:ring-2 focus:ring-[#091315] focus:outline-hidden font-mono text-xs"
                            >
                        </div>
                    </div>

                    <div class="pt-3">
                        <button
                            type="submit"
                            class="px-5 py-2.5 rounded-full text-xs font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] shadow-xs border border-[#c8f043] cursor-pointer transition-all"
                        >
                            Save Profile Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. Change Password -->
        <div class="bg-white p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-neutral-200/80 shadow-[0_2px_12px_rgba(0,0,0,0.02)] flex flex-col justify-between">
            <div>
                <h3 class="font-bold text-neutral-900 text-sm pb-4 mb-4 border-b border-neutral-100 flex items-center gap-2 font-display">
                    <span>🔑</span> Change Password
                </h3>

                <form action="{{ route('profile.password') }}" method="POST" class="space-y-4 text-xs">
                    @csrf

                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Current Password *</label>
                        <input
                            type="password"
                            name="current_password"
                            required
                            placeholder="••••••••••••"
                            class="w-full px-3.5 py-2.5 border border-neutral-300 rounded-xl text-neutral-900 font-mono focus:ring-2 focus:ring-[#091315] focus:outline-hidden text-xs"
                        >
                    </div>

                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">New Password *</label>
                        <input
                            type="password"
                            name="password"
                            required
                            placeholder="Minimum 6 characters"
                            class="w-full px-3.5 py-2.5 border border-neutral-300 rounded-xl text-neutral-900 font-mono focus:ring-2 focus:ring-[#091315] focus:outline-hidden text-xs"
                        >
                    </div>

                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">Confirm New Password *</label>
                        <input
                            type="password"
                            name="password_confirmation"
                            required
                            placeholder="Repeat new password"
                            class="w-full px-3.5 py-2.5 border border-neutral-300 rounded-xl text-neutral-900 font-mono focus:ring-2 focus:ring-[#091315] focus:outline-hidden text-xs"
                        >
                    </div>

                    <div class="pt-3">
                        <button
                            type="submit"
                            class="px-5 py-2.5 rounded-full text-xs font-bold text-white bg-[#091315] hover:bg-black shadow-xs cursor-pointer transition-all border border-neutral-800"
                        >
                            Update Password
                        </button>
                    </div>
                </form>
            </div>

            <div class="mt-6 pt-4 border-t border-neutral-100 text-[11px] text-neutral-500 flex items-center justify-between font-mono">
                <span>Account Status: <b class="text-emerald-600">Active</b></span>
                <span>Security: <b>Encrypted</b></span>
            </div>
        </div>
    </div>

    <!-- 3. Fortify Two-Factor Authentication (2FA) -->
    <div class="bg-white p-4 sm:p-8 rounded-2xl sm:rounded-3xl border border-neutral-200/80 shadow-[0_2px_12px_rgba(0,0,0,0.02)] space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-neutral-100">
            <div>
                <h3 class="font-bold text-base text-neutral-900 flex items-center gap-2 font-display">
                    <span>🛡️</span> Two-Factor Authentication (2FA)
                </h3>
                <p class="text-xs text-neutral-500 mt-0.5">
                    Add additional security to your account using TOTP two-factor authentication.
                </p>
            </div>

            <div>
                @if ($user->hasEnabledTwoFactorAuthentication())
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        2FA Active
                    </span>
                @elseif ($user->two_factor_secret)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                        Pending Confirmation
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#F5F6F8] text-neutral-600 border border-neutral-200/80">
                        <span class="w-2 h-2 rounded-full bg-neutral-400"></span>
                        Disabled
                    </span>
                @endif
            </div>
        </div>

        @if (! $user->two_factor_secret)
            <!-- 2FA Disabled State -->
            <div class="space-y-4">
                <p class="text-xs text-neutral-600 leading-relaxed">
                    When two-factor authentication is enabled, you will be prompted for a secure, 6-digit random token during login. You can retrieve this token from applications like Google Authenticator, Microsoft Authenticator, 1Password, or Authy on your phone.
                </p>

                <form method="POST" action="{{ url('/user/two-factor-authentication') }}">
                    @csrf
                    <button
                        type="submit"
                        class="px-5 py-2.5 rounded-full text-xs font-bold text-white bg-[#091315] hover:bg-black shadow-xs cursor-pointer transition-all inline-flex items-center gap-2 border border-neutral-800"
                    >
                        <span>🔐 Enable Two-Factor Authentication</span>
                    </button>
                </form>
            </div>

        @elseif (! $user->two_factor_confirmed_at)
            <!-- 2FA Pending Confirmation State -->
            <div class="space-y-6">
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-2xl text-xs text-amber-800 space-y-1">
                    <p class="font-bold flex items-center gap-1.5">
                        <span>⚠️</span> Finish Enabling Two-Factor Authentication
                    </p>
                    <p class="text-[11px] text-amber-700">
                        To finish enabling two-factor authentication, scan the following QR code using your phone's authenticator app or enter the setup key, then provide the 6-digit confirmation code below.
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-4 sm:gap-6 p-4 sm:p-5 bg-[#F5F6F8] rounded-2xl sm:rounded-3xl border border-neutral-200/80">
                    <div class="bg-white p-3.5 rounded-2xl border border-neutral-200/80 shadow-xs">
                        {!! $user->twoFactorQrCodeSvg() !!}
                    </div>

                    <div class="space-y-3 text-xs">
                        <div>
                            <span class="font-bold text-neutral-700 block">Manual Setup Key:</span>
                            <code class="font-mono text-xs bg-white px-3 py-1.5 rounded-xl border border-neutral-200/80 select-all block mt-1 text-neutral-900 font-bold break-all">
                                {{ decrypt($user->two_factor_secret) }}
                            </code>
                        </div>
                        <p class="text-[11px] text-neutral-500">
                            Scan with Google Authenticator, Authy, or Apple Passwords.
                        </p>
                    </div>
                </div>

                <form method="POST" action="{{ url('/user/confirmed-two-factor-authentication') }}" class="space-y-4">
                    @csrf
                    <div class="max-w-xs">
                        <label for="code" class="block font-bold text-neutral-700 text-xs mb-1.5">
                            Enter 6-Digit Authenticator Code *
                        </label>
                        <input
                            type="text"
                            id="code"
                            name="code"
                            required
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            placeholder="123456"
                            class="w-full px-3.5 py-2.5 bg-white border border-neutral-300 rounded-xl text-neutral-900 font-mono text-base tracking-widest font-bold focus:ring-2 focus:ring-[#091315] text-center"
                        >
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="submit"
                            class="px-5 py-2.5 rounded-full text-xs font-bold text-white bg-[#091315] hover:bg-black shadow-xs cursor-pointer transition-all border border-neutral-800"
                        >
                            Confirm & Activate 2FA
                        </button>
                    </div>
                </form>

                <form method="POST" action="{{ url('/user/two-factor-authentication') }}">
                    @csrf
                    @method('DELETE')
                    <button
                        type="submit"
                        class="px-4 py-2 rounded-full text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 cursor-pointer transition-all"
                    >
                        Cancel Setup
                    </button>
                </form>
            </div>

        @else
            <!-- 2FA Active & Confirmed State -->
            <div class="space-y-6">
                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-xs text-emerald-800 space-y-1">
                    <p class="font-bold flex items-center gap-1.5">
                        <span>✓</span> Two-Factor Authentication is Active
                    </p>
                    <p class="text-[11px] text-emerald-700">
                        Your account is secured with two-factor authentication. You will be prompted for an authenticator token each time you log in.
                    </p>
                </div>

                <!-- Recovery Codes Section -->
                <div class="space-y-3">
                    <h4 class="font-bold text-xs text-neutral-900 uppercase tracking-wider font-mono">
                        Emergency Recovery Codes
                    </h4>
                    <p class="text-xs text-neutral-500">
                        Store these recovery codes in a safe place. If you ever lose access to your authenticator app, each code can be used once to access your account.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-2.5 p-4 bg-[#F5F6F8] rounded-2xl border border-neutral-200/80 font-mono text-xs text-neutral-800 font-semibold select-all">
                        @foreach ($user->recoveryCodes() as $code)
                            <div class="p-2.5 bg-white rounded-xl border border-neutral-200/80 text-center tracking-wider">
                                {{ $code }}
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <form method="POST" action="{{ url('/user/two-factor-recovery-codes') }}">
                        @csrf
                        <button
                            type="submit"
                            class="px-4 py-2 rounded-full text-xs font-bold text-neutral-700 bg-neutral-100 hover:bg-neutral-200 border border-neutral-200/80 cursor-pointer transition-all"
                        >
                            🔄 Regenerate Recovery Codes
                        </button>
                    </form>

                    <form method="POST" action="{{ url('/user/two-factor-authentication') }}">
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            class="px-4 py-2 rounded-full text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 cursor-pointer transition-all"
                        >
                            Disable Two-Factor Authentication
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>

    <!-- 4. Spatie RBAC Roles & Permissions Matrix -->
    <div class="bg-white p-4 sm:p-8 rounded-2xl sm:rounded-3xl border border-neutral-200/80 shadow-[0_2px_12px_rgba(0,0,0,0.02)] space-y-4">
        <h3 class="font-bold text-base text-neutral-900 flex items-center gap-2 font-display">
            <span>🛡️</span> Role-Based Access Control (Spatie RBAC)
        </h3>
        <p class="text-xs text-neutral-500">
            Assigned roles and active permissions governing your ERP workspace access.
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 text-xs">
            <div class="p-4 rounded-2xl bg-[#F5F6F8] border border-neutral-200/80 space-y-2">
                <span class="font-bold text-neutral-700 block">Assigned Spatie Roles:</span>
                <div class="flex flex-wrap gap-1.5">
                    @forelse ($user->getRoleNames() as $roleName)
                        <span class="px-2.5 py-1 rounded-full font-mono text-[11px] font-bold bg-[#091315] text-[#D7FF53]">
                            {{ $roleName }}
                        </span>
                    @empty
                        <span class="text-neutral-400">No roles assigned</span>
                    @endforelse
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-[#F5F6F8] border border-neutral-200/80 space-y-2">
                <span class="font-bold text-neutral-700 block">Active Permissions ({{ $user->getAllPermissions()->count() }}):</span>
                <div class="flex flex-wrap gap-1.5 max-h-24 overflow-y-auto">
                    @forelse ($user->getAllPermissions() as $permission)
                        <span class="px-2 py-0.5 rounded-full font-mono text-[10px] bg-white border border-neutral-200/80 text-neutral-800 font-bold">
                            {{ $permission->name }}
                        </span>
                    @empty
                        <span class="text-neutral-400">No direct permissions</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
