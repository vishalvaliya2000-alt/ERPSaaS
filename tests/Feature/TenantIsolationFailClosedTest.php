<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductDocumentShare;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    TenantManager::clear();
});

afterEach(function () {
    TenantManager::clear();
});

test('TenantManager returns null when no user is authenticated', function () {
    expect(TenantManager::getTenant())->toBeNull();
    expect(TenantManager::getTenantId())->toBeNull();
});

test('TenantManager returns null when authenticated user has null tenant_id', function () {
    $userWithoutTenant = User::factory()->create([
        'tenant_id' => null,
    ]);

    $this->actingAs($userWithoutTenant);

    expect(TenantManager::getTenant())->toBeNull();
    expect(TenantManager::getTenantId())->toBeNull();
});

test('authenticated user with null tenant_id receives 403 on protected routes', function () {
    $userWithoutTenant = User::factory()->create([
        'tenant_id' => null,
    ]);

    $response = $this->actingAs($userWithoutTenant)->get(route('customers.index'));
    $response->assertStatus(403);

    $responseSettings = $this->actingAs($userWithoutTenant)->get(route('organization.settings'));
    $responseSettings->assertStatus(403);
});

test('authenticated user with inactive tenant receives 403 on protected routes', function () {
    $inactiveTenant = Tenant::create([
        'name' => 'Suspended Corp',
        'slug' => 'suspended-corp-'.rand(1000, 9999),
        'is_active' => false,
    ]);

    $userOfInactiveTenant = User::factory()->create([
        'tenant_id' => $inactiveTenant->id,
    ]);

    $response = $this->actingAs($userOfInactiveTenant)->get(route('customers.index'));
    $response->assertStatus(403);
    expect(TenantManager::getTenant())->toBeNull();
    expect(TenantManager::getTenantId())->toBeNull();
});

test('same-tenant users cannot see each other cached navigation identity in view composer', function () {
    $tenant = Tenant::create([
        'name' => 'View Composer Shared Corp',
        'slug' => 'view-comp-'.rand(1000, 9999),
        'is_active' => true,
    ]);

    $userA = User::factory()->create([
        'name' => 'Alice CorpAdmin',
        'tenant_id' => $tenant->id,
    ]);

    $userB = User::factory()->create([
        'name' => 'Bob FinanceMgr',
        'tenant_id' => $tenant->id,
    ]);

    // Request as User A
    $respA = $this->actingAs($userA)->get(route('dashboard'));
    $respA->assertStatus(200);
    $respA->assertSee('Alice');
    $respA->assertDontSee('Bob FinanceMgr');

    // Request as User B (same tenant, simulated within same process)
    $respB = $this->actingAs($userB)->get(route('dashboard'));
    $respB->assertStatus(200);
    $respB->assertSee('Bob');
    $respB->assertDontSee('Alice CorpAdmin');
});

test('TenantScope fails closed when no tenant context is active', function () {
    $tenant = Tenant::create([
        'name' => 'Isolation Tenant Alpha',
        'slug' => 'isolation-alpha-'.rand(1000, 9999),
        'is_active' => true,
    ]);

    // Create customer safely within tenant context
    TenantManager::runAsTenant($tenant, function () use ($tenant) {
        Customer::create([
            'tenant_id' => $tenant->id,
            'customer_code' => 'CUST-ISO1',
            'company_name' => 'Alpha Private Ltd',
        ]);
    });

    // Ensure TenantManager has no active context
    TenantManager::clear();

    // Query without tenant context returns 0 results due to 1 = 0 fail-closed scope
    expect(Customer::count())->toBe(0);

    // With explicit tenant set, customer is visible
    TenantManager::setTenant($tenant);
    expect(Customer::count())->toBe(1);
});

test('creating BelongsToTenant model without tenant context throws InvalidArgumentException', function () {
    TenantManager::clear();

    expect(function () {
        Customer::create([
            'customer_code' => 'CUST-FAIL',
            'company_name' => 'Fail Co',
        ]);
    })->toThrow(InvalidArgumentException::class, 'without a valid tenant context');
});

test('creating BelongsToTenant model with explicit matching tenant_id succeeds', function () {
    $tenant = Tenant::create([
        'name' => 'Explicit Matching Tenant',
        'slug' => 'explicit-match-'.rand(1000, 9999),
        'is_active' => true,
    ]);

    TenantManager::setTenant($tenant);

    $customer = Customer::create([
        'tenant_id' => $tenant->id,
        'customer_code' => 'CUST-MATCH1',
        'company_name' => 'Matching Corp',
    ]);

    expect($customer->tenant_id)->toBe($tenant->id);
});

test('creating BelongsToTenant model with explicit mismatched tenant_id throws InvalidArgumentException', function () {
    $tenantA = Tenant::create(['name' => 'Tenant Active', 'slug' => 't-active-'.rand(1000, 9999), 'is_active' => true]);
    $tenantB = Tenant::create(['name' => 'Tenant Target', 'slug' => 't-target-'.rand(1000, 9999), 'is_active' => true]);

    TenantManager::setTenant($tenantA);

    // Active context is Tenant A, but payload specifies Tenant B
    expect(function () use ($tenantB) {
        Customer::create([
            'tenant_id' => $tenantB->id,
            'customer_code' => 'CUST-INJECT',
            'company_name' => 'Injected Corp',
        ]);
    })->toThrow(InvalidArgumentException::class, 'when active tenant context is');
});

test('creating BelongsToTenant model inside runAsTenant succeeds and auto-populates tenant_id', function () {
    $tenant = Tenant::create([
        'name' => 'Auto Populate Tenant',
        'slug' => 'auto-pop-'.rand(1000, 9999),
        'is_active' => true,
    ]);

    $customer = TenantManager::runAsTenant($tenant, function () {
        return Customer::create([
            'customer_code' => 'CUST-AUTO1',
            'company_name' => 'Auto Populated Co',
        ]);
    });

    expect($customer->tenant_id)->toBe($tenant->id);
});

test('TenantManager::runAsTenant safely switches and restores tenant context', function () {
    $tenantA = Tenant::create(['name' => 'Tenant A', 'slug' => 'tenant-a-'.rand(1000, 9999), 'is_active' => true]);
    $tenantB = Tenant::create(['name' => 'Tenant B', 'slug' => 'tenant-b-'.rand(1000, 9999), 'is_active' => true]);

    TenantManager::setTenant($tenantA);
    expect(TenantManager::getTenantId())->toBe($tenantA->id);

    $executedTenantId = TenantManager::runAsTenant($tenantB, function ($tenant) {
        expect(TenantManager::getTenantId())->toBe($tenant->id);

        return TenantManager::getTenantId();
    });

    expect($executedTenantId)->toBe($tenantB->id);
    expect(TenantManager::getTenantId())->toBe($tenantA->id);
});

test('TenantManager::runAsTenant restores context even when callback throws an exception', function () {
    $tenantA = Tenant::create(['name' => 'Tenant Origin', 'slug' => 'tenant-orig-'.rand(1000, 9999), 'is_active' => true]);
    $tenantB = Tenant::create(['name' => 'Tenant Error', 'slug' => 'tenant-err-'.rand(1000, 9999), 'is_active' => true]);

    TenantManager::setTenant($tenantA);

    try {
        TenantManager::runAsTenant($tenantB, function () {
            throw new RuntimeException('Simulated worker crash');
        });
    } catch (RuntimeException $e) {
        // Exception caught
    }

    expect(TenantManager::getTenantId())->toBe($tenantA->id);
    expect(TenantManager::getRunAsDepth())->toBe(0);
});

test('TenantManager::runAsTenant nested calls restore context correctly in LIFO order', function () {
    $tenant1 = Tenant::create(['name' => 'Tenant 1', 'slug' => 't1-'.rand(1000, 9999), 'is_active' => true]);
    $tenant2 = Tenant::create(['name' => 'Tenant 2', 'slug' => 't2-'.rand(1000, 9999), 'is_active' => true]);
    $tenant3 = Tenant::create(['name' => 'Tenant 3', 'slug' => 't3-'.rand(1000, 9999), 'is_active' => true]);

    TenantManager::setTenant($tenant1);

    TenantManager::runAsTenant($tenant2, function ($t2) use ($tenant3) {
        expect(TenantManager::getTenantId())->toBe($t2->id);

        TenantManager::runAsTenant($tenant3, function ($t3) {
            expect(TenantManager::getTenantId())->toBe($t3->id);
        });

        expect(TenantManager::getTenantId())->toBe($t2->id);
    });

    expect(TenantManager::getTenantId())->toBe($tenant1->id);
    expect(TenantManager::getRunAsDepth())->toBe(0);
});

test('TenantManager::runAsTenant throws InvalidArgumentException on non-existent tenant ID', function () {
    expect(function () {
        TenantManager::runAsTenant(999999, function () {
            // Should not execute
        });
    })->toThrow(InvalidArgumentException::class, 'does not exist');
});

test('previous request tenant cannot override next request tenant', function () {
    $tenant1 = Tenant::create(['name' => 'Tenant Alpha 1', 'slug' => 't-alpha1-'.rand(1000, 9999), 'is_active' => true]);
    $tenant2 = Tenant::create(['name' => 'Tenant Beta 2', 'slug' => 't-beta2-'.rand(1000, 9999), 'is_active' => true]);

    $user1 = User::factory()->create(['tenant_id' => $tenant1->id]);
    $user2 = User::factory()->create(['tenant_id' => $tenant2->id]);

    // Request 1 by User 1
    $resp1 = $this->actingAs($user1)->get(route('customers.index'));
    $resp1->assertStatus(200);

    // Statically simulate residual tenant from previous request in persistent worker
    TenantManager::setTenant($tenant1);
    expect(TenantManager::getTenantId())->toBe($tenant1->id);

    // Request 2 by User 2 must resolve to Tenant 2, NOT Tenant 1
    $resp2 = $this->actingAs($user2)->get(route('customers.index'));
    $resp2->assertStatus(200);

    // Residual Tenant 1 must NOT override User 2's tenant
    expect(TenantManager::getTenantId())->not->toBe($tenant1->id);

    // Log out user2 to verify guest state has null tenant
    auth()->logout();
    TenantManager::clear();
    expect(TenantManager::getTenantId())->toBeNull();
});

test('guest requests do not inherit stale tenant context', function () {
    $tenant = Tenant::create(['name' => 'Stale Tenant', 'slug' => 'stale-t-'.rand(1000, 9999), 'is_active' => true]);

    // Statically set tenant simulating un-cleared worker state
    TenantManager::setTenant($tenant);

    // Guest visits /login
    $response = $this->get(route('login'));
    $response->assertStatus(200);

    // After guest request, TenantManager must be cleared
    expect(TenantManager::getTenant())->toBeNull();
    expect(TenantManager::getTenantId())->toBeNull();
});

test('public product document share portal works for Tenant 2 without falling back to Tenant 1', function () {
    $tenant2 = Tenant::create([
        'name' => 'Export Specialty Ltd',
        'slug' => 'export-specialty-'.rand(1000, 9999),
        'is_active' => true,
    ]);

    $product = TenantManager::runAsTenant($tenant2, function () use ($tenant2) {
        return Product::create([
            'tenant_id' => $tenant2->id,
            'product_code' => 'PRD-T2',
            'product_name' => 'Dehydrated White Onion Flakes',
            'uom' => 'KGS',
            'is_active' => true,
        ]);
    });

    $token = 'share-token-tenant2-public-'.bin2hex(random_bytes(16));
    $share = TenantManager::runAsTenant($tenant2, function () use ($tenant2, $product, $token) {
        return ProductDocumentShare::create([
            'tenant_id' => $tenant2->id,
            'product_id' => $product->id,
            'share_token' => $token,
            'is_active' => true,
            'expires_at' => Carbon::now()->addDays(7),
        ]);
    });

    TenantManager::clear();

    // Guest accessing public share link
    $response = $this->get(route('products.documents.shared', $token));

    $response->assertStatus(200);
    $response->assertSee('Dehydrated White Onion Flakes');
});

test('public share token lookup uses withoutGlobalScope and fails on invalid token', function () {
    $invalidToken = 'invalid-share-token-'.bin2hex(random_bytes(16));

    $response = $this->get(route('products.documents.shared', $invalidToken));
    $response->assertStatus(404);
});

test('authenticated null-tenant user while Tenant 1 is already in TenantManager receives 403', function () {
    $tenant1 = Tenant::create(['name' => 'Existing Tenant 1', 'slug' => 't1-null-'.rand(1000, 9999), 'is_active' => true]);
    TenantManager::setTenant($tenant1);

    $userWithoutTenant = User::factory()->create(['tenant_id' => null]);
    $response = $this->actingAs($userWithoutTenant)->get(route('customers.index'));
    $response->assertStatus(403);
});

test('authenticated nonexistent-tenant user while Tenant 1 is already in TenantManager receives 403', function () {
    $tenant1 = Tenant::create(['name' => 'Existing Tenant 1', 'slug' => 't1-nonexist-'.rand(1000, 9999), 'is_active' => true]);
    TenantManager::setTenant($tenant1);

    $userWithInvalidTenant = User::factory()->create(['tenant_id' => 999999]);
    $response = $this->actingAs($userWithInvalidTenant)->get(route('customers.index'));
    $response->assertStatus(403);
});

test('authenticated inactive-tenant user while Tenant 1 is already in TenantManager receives 403', function () {
    $tenant1 = Tenant::create(['name' => 'Existing Tenant 1', 'slug' => 't1-inactive-'.rand(1000, 9999), 'is_active' => true]);
    $inactiveTenant = Tenant::create(['name' => 'Inactive Corp', 'slug' => 'inactive-corp-'.rand(1000, 9999), 'is_active' => false]);
    TenantManager::setTenant($tenant1);

    $userOfInactive = User::factory()->create(['tenant_id' => $inactiveTenant->id]);
    $response = $this->actingAs($userOfInactive)->get(route('customers.index'));
    $response->assertStatus(403);
});

test('valid Tenant 2 user while Tenant 1 is already in TenantManager resolves to Tenant 2 and never Tenant 1', function () {
    $tenant1 = Tenant::create(['name' => 'Residual Tenant 1', 'slug' => 't1-t2check-'.rand(1000, 9999), 'is_active' => true]);
    $tenant2 = Tenant::create(['name' => 'Target Tenant 2', 'slug' => 't2-t2check-'.rand(1000, 9999), 'is_active' => true]);
    TenantManager::setTenant($tenant1);

    $user2 = User::factory()->create(['tenant_id' => $tenant2->id]);
    $response = $this->actingAs($user2)->get(route('customers.index'));
    $response->assertStatus(200);
    $response->assertSee('Customers');
});

test('sequential HTTP requests in the same application process do not inherit tenant context', function () {
    $tenant1 = Tenant::create(['name' => 'Seq Tenant 1', 'slug' => 't1-seq-'.rand(1000, 9999), 'is_active' => true]);
    $user1 = User::factory()->create(['tenant_id' => $tenant1->id]);
    $userNoTenant = User::factory()->create(['tenant_id' => null]);

    TenantManager::clear();

    // Request 1 by User 1
    $r1 = $this->actingAs($user1)->get(route('customers.index'));
    $r1->assertStatus(200);

    // Request 2 by User with null tenant in same process must not inherit Tenant 1
    $r2 = $this->actingAs($userNoTenant)->get(route('customers.index'));
    $r2->assertStatus(403);

    // Request 3 by Guest in same process
    auth()->logout();
    $r3 = $this->get(route('customers.index'));
    $r3->assertRedirect(route('login'));
    expect(TenantManager::getTenant())->toBeNull();
});

test('request cleanup occurs after both successful and exceptional request handling', function () {
    $tenant = Tenant::create(['name' => 'Cleanup Tenant', 'slug' => 'cleanup-t-'.rand(1000, 9999), 'is_active' => true]);
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $badUser = User::factory()->create(['tenant_id' => null]);

    TenantManager::clear();

    // Successful request cleanup
    $this->actingAs($user)->get(route('customers.index'))->assertStatus(200);
    auth()->logout();
    expect(TenantManager::getTenant())->toBeNull();

    // Exceptional request (403) cleanup
    $this->actingAs($badUser)->get(route('customers.index'))->assertStatus(403);
    auth()->logout();
    expect(TenantManager::getTenant())->toBeNull();
});

test('runAsTenant rejects an inactive Tenant model instance', function () {
    $inactiveTenant = Tenant::create(['name' => 'Inactive Model Corp', 'slug' => 'inact-mod-'.rand(1000, 9999), 'is_active' => false]);

    expect(function () use ($inactiveTenant) {
        TenantManager::runAsTenant($inactiveTenant, function () {});
    })->toThrow(InvalidArgumentException::class, 'not active');
});

test('runAsTenant rejects an unsaved Tenant model instance', function () {
    $unsavedTenant = new Tenant(['name' => 'Unsaved Corp', 'slug' => 'unsaved-corp', 'is_active' => true]);

    expect(function () use ($unsavedTenant) {
        TenantManager::runAsTenant($unsavedTenant, function () {});
    })->toThrow(InvalidArgumentException::class, 'must be persisted');
});

test('nested valid runAsTenant calls still restore the correct outer context', function () {
    $t1 = Tenant::create(['name' => 'Outer Tenant', 'slug' => 'nest-outer-'.rand(1000, 9999), 'is_active' => true]);
    $t2 = Tenant::create(['name' => 'Inner Tenant', 'slug' => 'nest-inner-'.rand(1000, 9999), 'is_active' => true]);

    TenantManager::setTenant($t1);

    TenantManager::runAsTenant($t2, function ($inner) use ($t2) {
        expect(TenantManager::getTenantId())->toBe($t2->id);
        expect(TenantManager::getRunAsDepth())->toBe(1);
    });

    expect(TenantManager::getTenantId())->toBe($t1->id);
    expect(TenantManager::getRunAsDepth())->toBe(0);
});

test('console command and job tenant execution remains functional with runAsTenant', function () {
    $tenant = Tenant::create(['name' => 'Command Tenant', 'slug' => 'cmd-tenant-'.rand(1000, 9999), 'is_active' => true]);

    $exitCode = Artisan::call('erp:generate-daily-actions', ['--tenant' => $tenant->id]);
    expect($exitCode)->toBe(0);
    expect(TenantManager::getRunAsDepth())->toBe(0);
});

test('runAsTenant rejects stale in-memory active model when deactivated in database and restores context and depth', function () {
    $outerTenant = Tenant::create(['name' => 'Outer Tenant', 'slug' => 'outer-'.rand(1000, 9999), 'is_active' => true]);
    $staleTenant = Tenant::create(['name' => 'Stale Model Tenant', 'slug' => 'stale-'.rand(1000, 9999), 'is_active' => true]);

    TenantManager::setTenant($outerTenant);
    expect(TenantManager::getTenantId())->toBe($outerTenant->id);
    expect(TenantManager::getRunAsDepth())->toBe(0);

    // Confirm in-memory model is active
    expect($staleTenant->is_active)->toBeTrue();

    // Deactivate directly in database without refreshing the already-loaded model instance
    DB::table('tenants')->where('id', $staleTenant->id)->update(['is_active' => false]);

    // In-memory model instance still reflects is_active = true
    expect($staleTenant->is_active)->toBeTrue();

    // Attempting runAsTenant must reject the stale model by checking fresh DB state
    expect(function () use ($staleTenant) {
        TenantManager::runAsTenant($staleTenant, function () {
            // Should not execute
        });
    })->toThrow(InvalidArgumentException::class, 'not active');

    // Verify that the previous tenant context and run-as depth are restored correctly
    expect(TenantManager::getTenantId())->toBe($outerTenant->id);
    expect(TenantManager::getRunAsDepth())->toBe(0);
});

test('runAsTenant rejects stale in-memory model when deleted in database and restores context and depth', function () {
    $outerTenant = Tenant::create(['name' => 'Outer Tenant', 'slug' => 'outer-del-'.rand(1000, 9999), 'is_active' => true]);
    $deletedTenant = Tenant::create(['name' => 'Deleted Model Tenant', 'slug' => 'deleted-'.rand(1000, 9999), 'is_active' => true]);

    TenantManager::setTenant($outerTenant);
    expect(TenantManager::getTenantId())->toBe($outerTenant->id);
    expect(TenantManager::getRunAsDepth())->toBe(0);

    // Delete directly in database without refreshing the in-memory model instance
    DB::table('tenants')->where('id', $deletedTenant->id)->delete();

    // Attempting runAsTenant must reject the stale deleted model by checking fresh DB state
    expect(function () use ($deletedTenant) {
        TenantManager::runAsTenant($deletedTenant, function () {
            // Should not execute
        });
    })->toThrow(InvalidArgumentException::class, 'does not exist');

    // Verify context and depth were restored correctly
    expect(TenantManager::getTenantId())->toBe($outerTenant->id);
    expect(TenantManager::getRunAsDepth())->toBe(0);
});
