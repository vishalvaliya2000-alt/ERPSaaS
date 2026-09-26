<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('spatie roles exist in the database', function () {
    $roles = Role::pluck('name')->toArray();

    expect($roles)->toContain('OWNER');
    expect($roles)->toContain('ADMIN');
    expect($roles)->toContain('SALES');
    expect($roles)->toContain('DISPATCH');
    expect($roles)->toContain('ACCOUNTS');
    expect($roles)->toContain('VIEWER');
});

test('user model backward compatible role helper methods work with spatie roles', function () {
    $adminUser = User::factory()->create(['role' => 'STAFF']);
    $adminUser->assignRole('ADMIN');
    expect($adminUser->isAdmin())->toBeTrue();
    expect($adminUser->isSales())->toBeFalse();

    $salesUser = User::factory()->create(['role' => 'STAFF']);
    $salesUser->assignRole('SALES');
    expect($salesUser->isSales())->toBeTrue();
    expect($salesUser->isAdmin())->toBeFalse();

    $dispatchUser = User::factory()->create(['role' => 'STAFF']);
    $dispatchUser->assignRole('DISPATCH');
    expect($dispatchUser->isDispatch())->toBeTrue();

    $accountsUser = User::factory()->create(['role' => 'STAFF']);
    $accountsUser->assignRole('ACCOUNTS');
    expect($accountsUser->isAccounts())->toBeTrue();
});

test('spatie role permissions can be checked on user instance', function () {
    $ownerRole = Role::findByName('OWNER');
    $permission = Permission::firstOrCreate(['name' => 'manage-everything', 'guard_name' => 'web']);
    $ownerRole->givePermissionTo($permission);

    $user = User::factory()->create();
    $user->assignRole('OWNER');

    expect($user->hasPermissionTo('manage-everything'))->toBeTrue();
    expect($user->can('manage-everything'))->toBeTrue();
});

test('user can synchronize and swap spatie roles', function () {
    $user = User::factory()->create();
    $user->assignRole('VIEWER');
    expect($user->hasRole('VIEWER'))->toBeTrue();
    expect($user->hasRole('ADMIN'))->toBeFalse();

    $user->syncRoles(['ADMIN']);
    expect($user->hasRole('ADMIN'))->toBeTrue();
    expect($user->hasRole('VIEWER'))->toBeFalse();
    expect($user->isAdmin())->toBeTrue();
});
