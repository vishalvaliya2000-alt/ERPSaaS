@extends('layouts.guest', ['maxWidth' => 'sm:max-w-xl'])

@section('title', 'Register Your Business')

@section('content')
<div class="space-y-6" x-data="{
    step: {{ ($errors->has('name') || $errors->has('email') || $errors->has('phone') || $errors->has('password')) && !$errors->has('company_name') && !$errors->has('industry') && !$errors->has('state') ? 2 : 1 }},
    showPassword: false,
    companyName: '{{ old('company_name', '') }}',
    industry: '{{ old('industry', 'Manufacturing & Food Processing') }}',
    email: '{{ old('email', '') }}',
    ownerName: '{{ old('name', '') }}'
}">

    <!-- Header -->
    <div class="text-center space-y-1.5">
        <h2 class="text-2xl font-black text-neutral-900 tracking-tight font-display">Launch Your ERP Workspace</h2>
        <p class="text-xs text-neutral-500">Dedicated multi-tenant enterprise operations cloud setup in 60 seconds.</p>
    </div>

    <!-- 3-Step Progress Indicator -->
    <div class="grid grid-cols-3 gap-2 border-b border-neutral-100 pb-4">
        <div class="space-y-1 cursor-pointer" @click="step = 1">
            <div class="h-1.5 rounded-full transition-all duration-300"
                :class="step >= 1 ? 'bg-[#091315]' : 'bg-neutral-200'"></div>
            <span class="text-[10px] font-mono font-bold block"
                :class="step === 1 ? 'text-[#091315]' : 'text-neutral-400'">01 · BUSINESS</span>
        </div>
        <div class="space-y-1 cursor-pointer" @click="if(companyName) step = 2">
            <div class="h-1.5 rounded-full transition-all duration-300"
                :class="step >= 2 ? 'bg-[#091315]' : 'bg-neutral-200'"></div>
            <span class="text-[10px] font-mono font-bold block"
                :class="step === 2 ? 'text-[#091315]' : 'text-neutral-400'">02 · ACCOUNT</span>
        </div>
        <div class="space-y-1 cursor-pointer" @click="if(companyName && email) step = 3">
            <div class="h-1.5 rounded-full transition-all duration-300"
                :class="step === 3 ? 'bg-[#091315]' : 'bg-neutral-200'"></div>
            <span class="text-[10px] font-mono font-bold block"
                :class="step === 3 ? 'text-[#091315]' : 'text-neutral-400'">03 · REVIEW</span>
        </div>
    </div>

    <!-- Validation Errors -->
    @if ($errors->any())
    <x-alert type="error" title="Registration Incomplete">
        <ul class="list-disc list-inside text-[11px] space-y-0.5 mt-1">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-alert>
    @endif

    <form method="POST" action="{{ route('register') }}" class="space-y-5 text-xs" novalidate>
        @csrf

        <!-- STEP 1: BUSINESS INFORMATION -->
        <div x-show="step === 1" x-transition:enter="transition ease-out duration-200" class="space-y-4">
            <div class="flex items-center gap-2 pb-1">
                <span
                    class="w-6 h-6 rounded-lg bg-[#091315] text-[#D7FF53] flex items-center justify-center font-bold text-xs">1</span>
                <h3 class="text-xs font-bold text-neutral-900 uppercase tracking-wider font-mono">Business Entity
                    Details</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div class="sm:col-span-2">
                    <x-input label="Company / Business Legal Name" name="company_name" value="{{ old('company_name') }}"
                        required placeholder="e.g. Acme Industries Private Limited" x-model="companyName" />
                </div>

                <div class="sm:col-span-2">
                    <x-select label="Industry / Operational Sector" name="industry" required x-model="industry">
                        <option value="Manufacturing & Food Processing" {{
                            old('industry')=='Manufacturing & Food Processing' ? 'selected' : '' }}>🏭 Manufacturing &
                            Processing</option>
                        <option value="Agri-Commodities & Dehydrates" {{
                            old('industry')=='Agri-Commodities & Dehydrates' ? 'selected' : '' }}>🌾 Agri-Commodities &
                            Dehydrates</option>
                        <option value="Wholesale & Merchant Trading" {{ old('industry')=='Wholesale & Merchant Trading'
                            ? 'selected' : '' }}>📦 Wholesale & Merchant Trading</option>
                        <option value="International Export & Import" {{
                            old('industry')=='International Export & Import' ? 'selected' : '' }}>🌐 International
                            Export & Import</option>
                        <option value="Engineering & Fabrication" {{ old('industry')=='Engineering & Fabrication'
                            ? 'selected' : '' }}>⚙️ Engineering & Fabrication</option>
                        <option value="Other Business" {{ old('industry')=='Other Business' ? 'selected' : '' }}>💼
                            Other Enterprise</option>
                    </x-select>
                </div>

                <div class="sm:col-span-2">
                    <x-input label="GSTIN Number (Optional)" name="gstin" value="{{ old('gstin') }}"
                        placeholder="24AAACR1234F1Z5"
                        hint="For automated GST e-Invoicing and e-Way bill synchronization." />
                </div>

                <div>
                    <x-input label="State / Province" name="state" value="{{ old('state', 'Gujarat') }}" required
                        placeholder="e.g. Gujarat" />
                </div>

                <div>
                    <x-input label="City / Headquarters" name="city" value="{{ old('city') }}"
                        placeholder="e.g. Mahuva, Bhavnagar" />
                </div>
            </div>

            <div class="pt-2 flex justify-end">
                <x-button type="button" variant="obsidian" size="md" @click="if(companyName) step = 2"
                    class="w-full sm:w-auto">
                    <span>Continue to Owner Account</span>
                    <span>→</span>
                </x-button>
            </div>
        </div>

        <!-- STEP 2: OWNER ACCOUNT CREDENTIALS -->
        <div x-show="step === 2" x-cloak x-transition:enter="transition ease-out duration-200" class="space-y-4">
            <div class="flex items-center gap-2 pb-1">
                <span
                    class="w-6 h-6 rounded-lg bg-[#091315] text-[#D7FF53] flex items-center justify-center font-bold text-xs">2</span>
                <h3 class="text-xs font-bold text-neutral-900 uppercase tracking-wider font-mono">Business Owner Account
                </h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div class="sm:col-span-2">
                    <x-input label="Full Legal Name" name="name" value="{{ old('name') }}" required
                        placeholder="e.g. Rajesh Kumar" x-model="ownerName" />
                </div>

                <div>
                    <x-input label="Business Work Email" type="email" name="email" value="{{ old('email') }}" required
                        autocomplete="username" placeholder="admin@company.com" x-model="email" />
                </div>

                <div>
                    <x-input label="Phone / WhatsApp Number" type="tel" name="phone" value="{{ old('phone') }}" required
                        placeholder="9825012345" />
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-neutral-700 mb-1.5">
                        Account Password <span class="text-rose-500 font-bold ml-0.5">*</span>
                    </label>
                    <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required
                        autocomplete="new-password" placeholder="Min 6 characters"
                        class="w-full px-3.5 py-2.5 bg-white border border-neutral-300 rounded-xl text-neutral-900 placeholder-neutral-400 focus:outline-hidden focus:ring-2 focus:ring-[#091315] focus:border-[#091315] transition-all font-medium text-xs">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-neutral-700 mb-1.5">
                        Confirm Password <span class="text-rose-500 font-bold ml-0.5">*</span>
                    </label>
                    <input :type="showPassword ? 'text' : 'password'" id="password_confirmation"
                        name="password_confirmation" required autocomplete="new-password" placeholder="Repeat password"
                        class="w-full px-3.5 py-2.5 bg-white border border-neutral-300 rounded-xl text-neutral-900 placeholder-neutral-400 focus:outline-hidden focus:ring-2 focus:ring-[#091315] focus:border-[#091315] transition-all font-medium text-xs">
                </div>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" id="togglePass" @click="showPassword = !showPassword"
                    class="w-4 h-4 rounded border-neutral-300 text-[#091315] focus:ring-[#091315] cursor-pointer">
                <label for="togglePass" class="text-neutral-600 font-medium cursor-pointer text-xs">Reveal
                    password</label>
            </div>

            <div class="pt-2 flex items-center justify-between gap-3">
                <x-button type="button" variant="secondary" size="md" @click="step = 1">
                    <span>← Back</span>
                </x-button>

                <x-button type="button" variant="obsidian" size="md" @click="if(email && ownerName) step = 3">
                    <span>Review & Launch →</span>
                </x-button>
            </div>
        </div>

        <!-- STEP 3: REVIEW & LAUNCH -->
        <div x-show="step === 3" x-cloak x-transition:enter="transition ease-out duration-200" class="space-y-4">
            <div class="flex items-center gap-2 pb-1">
                <span
                    class="w-6 h-6 rounded-lg bg-[#091315] text-[#D7FF53] flex items-center justify-center font-bold text-xs">3</span>
                <h3 class="text-xs font-bold text-neutral-900 uppercase tracking-wider font-mono">Review Workspace
                    Architecture</h3>
            </div>

            <!-- Summary Card -->
            <div class="bg-[#F5F6F8] p-5 rounded-2xl border border-neutral-200/80 space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-neutral-200">
                    <span class="text-xs text-neutral-500 font-medium">Business Name</span>
                    <span class="text-xs font-bold text-neutral-900" x-text="companyName || 'Not specified'"></span>
                </div>
                <div class="flex items-center justify-between pb-2 border-b border-neutral-200">
                    <span class="text-xs text-neutral-500 font-medium">Industry</span>
                    <span class="text-xs font-bold text-neutral-900" x-text="industry"></span>
                </div>
                <div class="flex items-center justify-between pb-2 border-b border-neutral-200">
                    <span class="text-xs text-neutral-500 font-medium">Owner Administrator</span>
                    <span class="text-xs font-bold text-neutral-900" x-text="ownerName || 'Not specified'"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-neutral-500 font-medium">Login Identity</span>
                    <span class="text-xs font-mono font-bold text-neutral-900" x-text="email || 'Not specified'"></span>
                </div>
            </div>

            <div class="p-3.5 bg-[#F3FED4] rounded-2xl border border-[#D7FF53] flex items-start gap-2.5">
                <span class="text-base">⚡</span>
                <p class="text-[11px] text-[#091315] font-medium leading-relaxed">
                    By launching, your tenant database workspace will be provisioned with isolated CRM, sales orders,
                    tax ledger, and real-time logistics tracking.
                </p>
            </div>

            <div class="pt-2 flex items-center justify-between gap-3">
                <x-button type="button" variant="secondary" size="md" @click="step = 2">
                    <span>← Back</span>
                </x-button>

                <x-button type="submit" variant="obsidian" size="lg" class="flex-1 shadow-md">
                    <span>Launch ERPSaaS Workspace</span>
                    <span>→</span>
                </x-button>
            </div>
        </div>
    </form>

    <!-- Sign In Link -->
    <div class="pt-4 border-t border-neutral-100 text-center space-y-1">
        <p class="text-xs text-neutral-500">
            Already have an active account?
            <a href="{{ route('login') }}" class="font-bold text-[#091315] hover:underline ml-1">
                Sign in to your workspace →
            </a>
        </p>
    </div>

</div>
@endsection