<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;
use App\Services\ColorPaletteService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class TenantController extends Controller
{
    public function settings()
    {
        $tenant = TenantManager::getTenant();
        return view('organization.settings', compact('tenant'));
    }

    public function updateSettings(Request $request)
    {
        $tenant = TenantManager::getTenant();

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'tagline' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:4096',
            'remove_logo' => 'nullable|boolean',
            'primary_color' => 'nullable|string|max:20',
            'accent_color' => 'nullable|string|max:20',
            'currency_code' => 'required|string|max:10',
            'currency_symbol' => 'required|string|max:10',
            'tax_id_label' => 'required|string|max:50',
            'tax_id_number' => 'nullable|string|max:50',
            'pan_number' => 'nullable|string|max:50',
            'iec_code' => 'nullable|string|max:50',
            'lut_arn' => 'nullable|string|max:100',
            'fssai_number' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:150',
            'address_line' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_ifsc_code' => 'nullable|string|max:50',
            'bank_swift_code' => 'nullable|string|max:50',
            'bank_branch' => 'nullable|string|max:100',
            'invoice_prefix' => 'required|string|max:20',
            'quotation_prefix' => 'required|string|max:20',
            'po_prefix' => 'required|string|max:20',
            'shipment_prefix' => 'required|string|max:20',
        ]);

        // 1. Handle Logo Upload or Removal
        if ($request->boolean('remove_logo')) {
            if ($tenant->logo_url && Storage::disk('public')->exists(str_replace('storage/', '', $tenant->logo_url))) {
                Storage::disk('public')->delete(str_replace('storage/', '', $tenant->logo_url));
            }
            $tenant->logo_url = null;
        } elseif ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $path = $file->store('logos', 'public');
            $tenant->logo_url = 'storage/' . $path;

            // If user didn't explicitly specify custom colors, auto-extract from newly uploaded logo
            if (!$request->filled('primary_color')) {
                $fullPath = storage_path('app/public/' . $path);
                $extracted = ColorPaletteService::extractFromImage($fullPath);
                $validated['primary_color'] = $extracted['primary_color'];
                $validated['accent_color'] = $extracted['accent_color'];
            }
        }

        // 2. Handle Theme Settings
        $settings = $tenant->settings ?? [];
        if ($request->filled('primary_color') || isset($validated['primary_color'])) {
            $primary = $validated['primary_color'] ?? $request->input('primary_color');
            $accent = $validated['accent_color'] ?? $request->input('accent_color') ?: '#70c040';
            $settings['theme'] = [
                'primary_color' => $primary,
                'accent_color'  => $accent,
                'shades'        => ColorPaletteService::generateShades($primary),
                'updated_at'    => now()->toIso8601String(),
            ];
        }
        $tenant->settings = $settings;

        // Remove non-column inputs before updating
        unset($validated['logo'], $validated['remove_logo'], $validated['primary_color'], $validated['accent_color']);

        $tenant->fill($validated);
        $tenant->save();

        return back()->with('success', "✓ Organization profile, logo & brand theme updated for {$tenant->name}!");
    }

    public function team()
    {
        $tenant = TenantManager::getTenant();
        $members = User::where('tenant_id', $tenant->id)->get();
        return view('organization.team', compact('tenant', 'members'));
    }

    public function inviteMember(Request $request)
    {
        $tenant = TenantManager::getTenant();

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'role' => 'required|in:OWNER,ADMIN,SALES,DISPATCH,ACCOUNTS,VIEWER',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
            'email' => strtolower($validated['email']),
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        $user->assignRole($validated['role']);

        return back()->with('success', "✓ Team member {$user->name} created for {$tenant->name} with role {$validated['role']}!");
    }

    public function removeMember($userId)
    {
        $tenant = TenantManager::getTenant();
        $currentUser = auth()->user();

        if ($userId == $currentUser->id) {
            return back()->with('error', "You cannot remove your own active account.");
        }

        $targetUser = User::where('tenant_id', $tenant->id)->findOrFail($userId);
        $targetUser->delete();

        return back()->with('success', "✓ User removed from {$tenant->name}.");
    }
}