<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
        'designation',
        'phone',
        'is_active',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function tenants(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function getFirstNameAttribute(): string
    {
        $parts = explode(' ', trim($this->name ?? 'User'));
        return $parts[0] ?? 'User';
    }

    public function getInitialsAttribute(): string
    {
        $name = trim($this->name ?? 'RD');
        $parts = explode(' ', $name);
        if (count($parts) >= 2) {
            return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
        }
        return strtoupper(substr($name, 0, 2));
    }

    // Role Checks
    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['ADMIN', 'OWNER', 'MANAGING_DIRECTOR']) || in_array(strtoupper($this->role ?? ''), ['ADMIN', 'OWNER', 'MANAGING_DIRECTOR']);
    }

    public function isSales(): bool
    {
        return $this->hasRole('SALES') || strtoupper($this->role ?? '') === 'SALES';
    }

    public function isDispatch(): bool
    {
        return $this->hasRole('DISPATCH') || strtoupper($this->role ?? '') === 'DISPATCH';
    }

    public function isAccounts(): bool
    {
        return $this->hasRole('ACCOUNTS') || strtoupper($this->role ?? '') === 'ACCOUNTS';
    }
}