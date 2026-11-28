<?php

use App\Models\Tenant;
use App\Models\User;

test('unauthenticated users are redirected to login', function () {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});

test('authenticated users can access the dashboard', function () {
    $tenant = Tenant::create([
        'name' => 'Example Tenant',
        'slug' => 'example-tenant-'.rand(1000, 9999),
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $response = $this->actingAs($user)->get('/');

    $response->assertStatus(200);
});
