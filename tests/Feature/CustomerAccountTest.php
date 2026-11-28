<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;

beforeEach(function () {
    $this->tenant = Tenant::firstOrCreate(
        ['slug' => 'test-customer-tenant'],
        ['name' => 'Customer Test Tenant']
    );

    $this->user = User::firstOrCreate(
        ['email' => 'test_client_mgr@example.com'],
        [
            'tenant_id' => $this->tenant->id,
            'name' => 'Client Account Tester',
            'password' => bcrypt('password123'),
            'role' => 'ADMIN',
        ]
    );
    if ($this->user->tenant_id !== $this->tenant->id || empty($this->user->role)) {
        $this->user->tenant_id = $this->tenant->id;
        $this->user->role = 'ADMIN';
        $this->user->save();
    }

    TenantManager::setTenant($this->tenant);
});

test('Customer::generateNextCode generates sequential zero-padded CUST-xxxx codes', function () {
    $initialCode = Customer::generateNextCode($this->tenant->id);
    expect($initialCode)->toMatch('/^CUST-\d{4}$/');

    $customer = Customer::create([
        'tenant_id' => $this->tenant->id,
        'company_name' => 'Sequential Test Client 1',
        'customer_code' => $initialCode,
    ]);

    $nextCode = Customer::generateNextCode($this->tenant->id);
    expect($nextCode)->toMatch('/^CUST-\d{4}$/');

    preg_match('/CUST-(\d+)/', $initialCode, $m1);
    preg_match('/CUST-(\d+)/', $nextCode, $m2);
    expect((int) $m2[1])->toBeGreaterThan((int) $m1[1]);
});

test('customer creation stores Legal Name and Trade Name and auto-generates Customer Code when omitted', function () {
    $response = $this->actingAs($this->user)->post(route('customers.store'), [
        'customer_code' => '',
        'company_name' => 'Balaji Agro Industries Pvt Ltd',
        'trade_name' => 'Balaji Agro Brand',
        'gst_number' => '24AAACB'.rand(1000, 9999).'F1Z5',
        'primary_contact_person' => 'Bhavesh Patel',
        'primary_phone' => '9898012345',
        'city' => 'Mahuva',
        'state' => 'Gujarat',
        'payment_terms_days' => 30,
    ]);

    $created = Customer::where('company_name', 'Balaji Agro Industries Pvt Ltd')->first();
    expect($created)->not->toBeNull();
    expect($created->trade_name)->toBe('Balaji Agro Brand');
    expect($created->customer_code)->toStartWith('CUST-');
    expect($created->legal_name)->toBe('Balaji Agro Industries Pvt Ltd');

    $response->assertRedirect(route('customers.show', $created->id));
});

test('customer creation accepts legal_name attribute directly', function () {
    $response = $this->actingAs($this->user)->post(route('customers.store'), [
        'legal_name' => 'Kisan Food Processors LLP',
        'trade_name' => 'Kisan Pure Foods',
        'gst_number' => '24AAACK'.rand(1000, 9999).'F1Z6',
        'city' => 'Bhavnagar',
    ]);

    $created = Customer::where('company_name', 'Kisan Food Processors LLP')->first();
    expect($created)->not->toBeNull();
    expect($created->trade_name)->toBe('Kisan Pure Foods');
    expect($created->customer_code)->toStartWith('CUST-');
    $response->assertRedirect(route('customers.show', $created->id));
});

test('customers index displays Legal Name, Trade Name and auto-generated customer code in modal', function () {
    $cust = Customer::create([
        'tenant_id' => $this->tenant->id,
        'company_name' => 'Maruti Dehydration Foods Pvt Ltd',
        'trade_name' => 'Maruti Foods',
        'customer_code' => 'CUST-8899',
    ]);

    $response = $this->actingAs($this->user)->get(route('customers.index'));
    $response->assertStatus(200);
    $response->assertSee('Maruti Dehydration Foods Pvt Ltd');
    $response->assertSee('Maruti Foods');
    $response->assertSee('Legal Name of Business');
    $response->assertSee('Trade Name');
    $response->assertSee('Auto-Generated');
    $response->assertViewHas('nextCustomerCode');
});

test('executive dashboard does NOT render Documents Requiring Attention card', function () {
    $response = $this->actingAs($this->user)->get(route('dashboard'));
    $response->assertStatus(200);
    $response->assertDontSee('Documents Requiring Attention');
});

test('every new tenant starts customer code sequence from CUST-0001 independently', function () {
    $tenantA = Tenant::create([
        'name' => 'Alpha Agro Exports',
        'slug' => 'alpha-agro-'.rand(1000, 9999),
    ]);

    $tenantB = Tenant::create([
        'name' => 'Beta Spices Global',
        'slug' => 'beta-spices-'.rand(1000, 9999),
    ]);

    // For Tenant A: First customer must be CUST-0001
    TenantManager::setTenant($tenantA);
    $codeA1 = Customer::generateNextCode($tenantA->id);
    expect($codeA1)->toBe('CUST-0001');

    $custA1 = Customer::create([
        'tenant_id' => $tenantA->id,
        'company_name' => 'Tenant A First Customer',
        'customer_code' => $codeA1,
    ]);
    expect($custA1->customer_code)->toBe('CUST-0001');

    $codeA2 = Customer::generateNextCode($tenantA->id);
    expect($codeA2)->toBe('CUST-0002');

    // For Tenant B (brand new tenant): Must also start from CUST-0001
    TenantManager::setTenant($tenantB);
    $codeB1 = Customer::generateNextCode($tenantB->id);
    expect($codeB1)->toBe('CUST-0001');

    // Creating customer for Tenant B with auto-generation should generate CUST-0001
    $custB1 = Customer::create([
        'tenant_id' => $tenantB->id,
        'company_name' => 'Tenant B First Customer',
        // customer_code omitted to trigger booted auto-generation
    ]);
    expect($custB1->customer_code)->toBe('CUST-0001');

    // Tenant B's next customer is CUST-0002
    $codeB2 = Customer::generateNextCode($tenantB->id);
    expect($codeB2)->toBe('CUST-0002');

    // Verify both tenants possess a CUST-0001 record in the database simultaneously
    expect(Customer::withoutGlobalScopes()->where('tenant_id', $tenantA->id)->where('customer_code', 'CUST-0001')->exists())->toBeTrue();
    expect(Customer::withoutGlobalScopes()->where('tenant_id', $tenantB->id)->where('customer_code', 'CUST-0001')->exists())->toBeTrue();
});

test('Customer::generateNextCode fails closed without valid tenant and isolates sequences between tenants', function () {
    TenantManager::clear();

    // 1. Missing tenant context cannot query customer codes across tenants (fails closed)
    expect(function () {
        Customer::generateNextCode(null);
    })->toThrow(InvalidArgumentException::class, 'without a valid tenant context');

    // Also fails closed when explicit tenant ID does not exist or is inactive
    expect(function () {
        Customer::generateNextCode(999999);
    })->toThrow(InvalidArgumentException::class, 'invalid or inactive tenant');

    $tenantA = Tenant::create(['name' => 'Tenant Code A', 'slug' => 't-code-a-'.rand(1000, 9999), 'is_active' => true]);
    $tenantB = Tenant::create(['name' => 'Tenant Code B', 'slug' => 't-code-b-'.rand(1000, 9999), 'is_active' => true]);

    // 2. Tenant A generates its sequence using only Tenant A records
    TenantManager::setTenant($tenantA);
    expect(Customer::generateNextCode())->toBe('CUST-0001');

    $custA1 = Customer::create([
        'company_name' => 'Client A1',
    ]);
    expect($custA1->customer_code)->toBe('CUST-0001');
    expect(Customer::generateNextCode())->toBe('CUST-0002');

    $custA2 = Customer::create([
        'company_name' => 'Client A2',
    ]);
    expect($custA2->customer_code)->toBe('CUST-0002');
    expect(Customer::generateNextCode())->toBe('CUST-0003');

    // 3. Tenant B generates its sequence using only Tenant B records (starts at CUST-0001 despite Tenant A having 2 records)
    TenantManager::setTenant($tenantB);
    expect(Customer::generateNextCode())->toBe('CUST-0001');

    // 4. One tenant's customer records cannot influence another tenant's generated code
    $custB1 = Customer::create([
        'company_name' => 'Client B1',
    ]);
    expect($custB1->customer_code)->toBe('CUST-0001');
    expect(Customer::generateNextCode())->toBe('CUST-0002');

    // Switch back to Tenant A: Tenant A should still be at CUST-0003, unaffected by Tenant B
    TenantManager::setTenant($tenantA);
    expect(Customer::generateNextCode())->toBe('CUST-0003');

    // Explicit tenant ID passed to generateNextCode works legitimately for active tenant
    expect(Customer::generateNextCode($tenantB->id))->toBe('CUST-0002');
    expect(Customer::generateNextCode($tenantA->id))->toBe('CUST-0003');

    // 5. Existing legitimate customer creation workflows still work
    $custA3 = Customer::create([
        'company_name' => 'Client A3',
    ]);
    expect($custA3->customer_code)->toBe('CUST-0003');
});

test('customer product fulfillment summary accurately calculates quantities per product', function () {
    TenantManager::setTenant($this->tenant);

    $customer = Customer::create([
        'tenant_id' => $this->tenant->id,
        'company_name' => 'Fulfillment Test Client',
        'customer_code' => Customer::generateNextCode($this->tenant->id),
    ]);

    $prodGarlic = Product::create([
        'tenant_id' => $this->tenant->id,
        'product_name' => 'Garlic Powder Special Grade',
        'product_code' => 'GPSG-01',
        'uom' => 'KG',
        'is_active' => true,
    ]);

    $prodOnion = Product::create([
        'tenant_id' => $this->tenant->id,
        'product_name' => 'White Onion Flakes Premium',
        'product_code' => 'WOFP-01',
        'uom' => 'KG',
        'is_active' => true,
    ]);

    // Order 1: 10,000 KG Garlic, 5,000 KG Onion
    $order1 = SalesOrder::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $customer->id,
        'order_number' => 'SO-TEST-001',
        'order_date' => now(),
        'status' => 'CONFIRMED',
    ]);

    SalesOrderItem::create([
        'tenant_id' => $this->tenant->id,
        'sales_order_id' => $order1->id,
        'product_id' => $prodGarlic->id,
        'order_qty' => 10000,
        'shipped_qty' => 4000,
        'balance_qty' => 6000,
        'rate' => 150,
        'order_value' => 1500000,
        'status' => 'PARTIAL',
    ]);

    SalesOrderItem::create([
        'tenant_id' => $this->tenant->id,
        'sales_order_id' => $order1->id,
        'product_id' => $prodOnion->id,
        'order_qty' => 5000,
        'shipped_qty' => 5000,
        'balance_qty' => 0,
        'rate' => 120,
        'order_value' => 600000,
        'status' => 'COMPLETED',
    ]);

    // Order 2: 5,000 KG more Garlic (Shipped 1,000 KG, Balance 4,000 KG)
    $order2 = SalesOrder::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $customer->id,
        'order_number' => 'SO-TEST-002',
        'order_date' => now(),
        'status' => 'PARTIALLY_DISPATCHED',
    ]);

    SalesOrderItem::create([
        'tenant_id' => $this->tenant->id,
        'sales_order_id' => $order2->id,
        'product_id' => $prodGarlic->id,
        'order_qty' => 5000,
        'shipped_qty' => 1000,
        'balance_qty' => 4000,
        'rate' => 155,
        'order_value' => 775000,
        'status' => 'PARTIAL',
    ]);

    // Cancelled Order 3: 20,000 KG Garlic (Should be excluded)
    $orderCancelled = SalesOrder::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $customer->id,
        'order_number' => 'SO-TEST-003',
        'order_date' => now(),
        'status' => 'CANCELLED',
    ]);

    SalesOrderItem::create([
        'tenant_id' => $this->tenant->id,
        'sales_order_id' => $orderCancelled->id,
        'product_id' => $prodGarlic->id,
        'order_qty' => 20000,
        'shipped_qty' => 0,
        'balance_qty' => 20000,
        'rate' => 140,
        'order_value' => 2800000,
        'status' => 'CANCELLED',
    ]);

    // Test Model Accessor (DB Fallback)
    $customerFresh = Customer::find($customer->id);
    $summary = $customerFresh->product_fulfillment_summary;

    expect($summary)->toHaveCount(2);

    $garlicSummary = $summary->firstWhere('product_id', $prodGarlic->id);
    expect($garlicSummary)->not->toBeNull();
    expect($garlicSummary->total_ordered)->toEqual(15000.0);
    expect($garlicSummary->total_shipped)->toEqual(5000.0);
    expect($garlicSummary->total_pending)->toEqual(10000.0);
    expect($garlicSummary->fulfillment_percentage)->toEqual(33.3);
    expect($garlicSummary->status)->toBe('PARTIAL');
    expect($garlicSummary->orders_count)->toBe(2);

    $onionSummary = $summary->firstWhere('product_id', $prodOnion->id);
    expect($onionSummary)->not->toBeNull();
    expect($onionSummary->total_ordered)->toEqual(5000.0);
    expect($onionSummary->total_shipped)->toEqual(5000.0);
    expect($onionSummary->total_pending)->toEqual(0.0);
    expect($onionSummary->fulfillment_percentage)->toEqual(100.0);
    expect($onionSummary->status)->toBe('COMPLETED');
    expect($onionSummary->orders_count)->toBe(1);

    // Test Eager-Loaded Collection Branch
    $customerEager = Customer::with('salesOrders.items.product')->find($customer->id);
    $eagerSummary = $customerEager->product_fulfillment_summary;

    expect($eagerSummary)->toHaveCount(2);
    $garlicEager = $eagerSummary->firstWhere('product_id', $prodGarlic->id);
    expect($garlicEager->total_ordered)->toEqual(15000.0);
    expect($garlicEager->total_shipped)->toEqual(5000.0);
    expect($garlicEager->total_pending)->toEqual(10000.0);

    // Test Customer Show HTTP Response
    $response = $this->actingAs($this->user)->get(route('customers.show', $customer->id));
    $response->assertStatus(200);
    $response->assertSee('Product-Wise Orders');
    $response->assertSee('Garlic Powder Special Grade');
    $response->assertSee('15,000');
    $response->assertSee('5,000');
    $response->assertSee('10,000');
    $response->assertSee('White Onion Flakes Premium');
});
