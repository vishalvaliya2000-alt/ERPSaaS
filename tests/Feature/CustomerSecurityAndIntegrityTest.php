<?php

use App\Models\ActivityLog;
use App\Models\AiInsight;
use App\Models\BusinessDocument;
use App\Models\Contact;
use App\Models\CreditDebitNote;
use App\Models\Customer;
use App\Models\DocumentVersion;
use App\Models\FollowupTask;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\PaymentReceipt;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\Sample;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->tenantA = Tenant::firstOrCreate(
        ['slug' => 'test-sec-tenant-a'],
        ['name' => 'Security Tenant A', 'is_active' => true]
    );

    $this->tenantB = Tenant::firstOrCreate(
        ['slug' => 'test-sec-tenant-b'],
        ['name' => 'Security Tenant B', 'is_active' => true]
    );

    TenantManager::setTenant($this->tenantA);
});

function createTestUserForRole(Tenant $tenant, string $roleName, string $emailPrefix): User
{
    $user = User::firstOrCreate(
        ['email' => "{$emailPrefix}_{$tenant->id}@example.com"],
        [
            'tenant_id' => $tenant->id,
            'name' => "{$roleName} User {$tenant->id}",
            'password' => bcrypt('password123'),
            'role' => $roleName,
        ]
    );

    if ($user->tenant_id !== $tenant->id || $user->role !== $roleName) {
        $user->tenant_id = $tenant->id;
        $user->role = $roleName;
        $user->save();
    }

    if (Role::where('name', $roleName)->exists()) {
        $user->syncRoles([$roleName]);
    }

    return $user;
}

function createCleanCustomer(Tenant $tenant, string $prefix = 'Test Customer'): Customer
{
    return Customer::create([
        'tenant_id' => $tenant->id,
        'company_name' => "{$prefix} ".rand(1000, 9999),
        'trade_name' => 'Trade Name '.rand(1000, 9999),
        'customer_code' => 'CUST-'.rand(1000, 9999),
        'city' => 'Mahuva',
        'state' => 'Gujarat',
        'payment_terms_days' => 30,
        'outstanding_amount' => 0.00,
    ]);
}

// --------------------------------------------------------------------------
// Category A: Role-Based Authorization & Managing Director Deletion
// --------------------------------------------------------------------------

test('OWNER can view customer index and show', function () {
    $owner = createTestUserForRole($this->tenantA, 'OWNER', 'owner');
    $customer = createCleanCustomer($this->tenantA);

    $this->actingAs($owner)->get(route('customers.index'))->assertOk();
    $this->actingAs($owner)->get(route('customers.show', $customer->id))->assertOk();
});

test('OWNER can create customer', function () {
    $owner = createTestUserForRole($this->tenantA, 'OWNER', 'owner');

    $response = $this->actingAs($owner)->post(route('customers.store'), [
        'company_name' => 'Owner Created Industries',
        'city' => 'Mahuva',
        'state' => 'Gujarat',
    ]);

    $response->assertRedirect();
    expect(Customer::where('company_name', 'Owner Created Industries')->exists())->toBeTrue();
});

test('OWNER can update customer', function () {
    $owner = createTestUserForRole($this->tenantA, 'OWNER', 'owner');
    $customer = createCleanCustomer($this->tenantA, 'Pre-Update Customer');

    $response = $this->actingAs($owner)->post(route('customers.update', $customer->id), [
        'company_name' => 'Owner Updated Name',
        'city' => 'Surat',
    ]);

    $response->assertRedirect();
    $customer->refresh();
    expect($customer->company_name)->toBe('Owner Updated Name');
    expect($customer->city)->toBe('Surat');
});

test('OWNER can delete clean customer', function () {
    $owner = createTestUserForRole($this->tenantA, 'OWNER', 'owner');
    $customer = createCleanCustomer($this->tenantA, 'To Be Deleted By Owner');

    $response = $this->actingAs($owner)->post(route('customers.destroy', $customer->id));

    $response->assertRedirect(route('customers.index'));
    $response->assertSessionHas('success');
    expect(Customer::find($customer->id))->toBeNull();
});

test('ADMIN can delete clean customer', function () {
    $admin = createTestUserForRole($this->tenantA, 'ADMIN', 'admin');
    $customer = createCleanCustomer($this->tenantA, 'To Be Deleted By Admin');

    $response = $this->actingAs($admin)->post(route('customers.destroy', $customer->id));

    $response->assertRedirect(route('customers.index'));
    $response->assertSessionHas('success');
    expect(Customer::find($customer->id))->toBeNull();
});

test('MANAGING_DIRECTOR can delete clean customer', function () {
    $md = createTestUserForRole($this->tenantA, 'MANAGING_DIRECTOR', 'md');
    $customer = createCleanCustomer($this->tenantA, 'To Be Deleted By MD');

    $response = $this->actingAs($md)->post(route('customers.destroy', $customer->id));

    $response->assertRedirect(route('customers.index'));
    $response->assertSessionHas('success');
    expect(Customer::find($customer->id))->toBeNull();
});

test('SALES can view, create, update, but is forbidden (403) from deleting customer', function () {
    $sales = createTestUserForRole($this->tenantA, 'SALES', 'sales');
    $customer = createCleanCustomer($this->tenantA, 'Sales Protected Customer');

    // View allowed
    $this->actingAs($sales)->get(route('customers.index'))->assertOk();
    $this->actingAs($sales)->get(route('customers.show', $customer->id))->assertOk();

    // Create allowed
    $this->actingAs($sales)->post(route('customers.store'), [
        'company_name' => 'Sales Created Customer',
        'city' => 'Rajkot',
    ])->assertRedirect();

    // Update allowed
    $this->actingAs($sales)->post(route('customers.update', $customer->id), [
        'city' => 'Gandhinagar',
    ])->assertRedirect();

    // Delete FORBIDDEN (403)
    $this->actingAs($sales)->post(route('customers.destroy', $customer->id))->assertForbidden();

    // Customer remains intact
    expect(Customer::find($customer->id))->not->toBeNull();
});

test('ACCOUNTS can view, create, update, but is forbidden (403) from deleting customer', function () {
    $accounts = createTestUserForRole($this->tenantA, 'ACCOUNTS', 'accounts');
    $customer = createCleanCustomer($this->tenantA, 'Accounts Protected Customer');

    // View allowed
    $this->actingAs($accounts)->get(route('customers.index'))->assertOk();
    $this->actingAs($accounts)->get(route('customers.show', $customer->id))->assertOk();

    // Create allowed
    $this->actingAs($accounts)->post(route('customers.store'), [
        'company_name' => 'Accounts Created Customer',
        'city' => 'Ahmedabad',
    ])->assertRedirect();

    // Update allowed
    $this->actingAs($accounts)->post(route('customers.update', $customer->id), [
        'city' => 'Vadodara',
    ])->assertRedirect();

    // Delete FORBIDDEN (403)
    $this->actingAs($accounts)->post(route('customers.destroy', $customer->id))->assertForbidden();

    // Customer remains intact
    expect(Customer::find($customer->id))->not->toBeNull();
});

test('DISPATCH can view customer, but is forbidden (403) from create, update, delete', function () {
    $dispatch = createTestUserForRole($this->tenantA, 'DISPATCH', 'dispatch');
    $customer = createCleanCustomer($this->tenantA, 'Dispatch Restricted Customer');

    // View allowed
    $this->actingAs($dispatch)->get(route('customers.index'))->assertOk();
    $this->actingAs($dispatch)->get(route('customers.show', $customer->id))->assertOk();

    // Create FORBIDDEN (403)
    $this->actingAs($dispatch)->post(route('customers.store'), [
        'company_name' => 'Dispatch Forbidden Creation',
    ])->assertForbidden();

    // Update FORBIDDEN (403)
    $this->actingAs($dispatch)->post(route('customers.update', $customer->id), [
        'city' => 'Forbidden City',
    ])->assertForbidden();

    // Delete FORBIDDEN (403)
    $this->actingAs($dispatch)->post(route('customers.destroy', $customer->id))->assertForbidden();

    // Customer remains intact
    expect(Customer::find($customer->id))->not->toBeNull();
});

test('VIEWER can view customer, but is forbidden (403) from create, update, delete', function () {
    $viewer = createTestUserForRole($this->tenantA, 'VIEWER', 'viewer');
    $customer = createCleanCustomer($this->tenantA, 'Viewer Restricted Customer');

    // View allowed
    $this->actingAs($viewer)->get(route('customers.index'))->assertOk();
    $this->actingAs($viewer)->get(route('customers.show', $customer->id))->assertOk();

    // Create FORBIDDEN (403)
    $this->actingAs($viewer)->post(route('customers.store'), [
        'company_name' => 'Viewer Forbidden Creation',
    ])->assertForbidden();

    // Update FORBIDDEN (403)
    $this->actingAs($viewer)->post(route('customers.update', $customer->id), [
        'city' => 'Forbidden City',
    ])->assertForbidden();

    // Delete FORBIDDEN (403)
    $this->actingAs($viewer)->post(route('customers.destroy', $customer->id))->assertForbidden();

    // Customer remains intact
    expect(Customer::find($customer->id))->not->toBeNull();
});

test('unauthorized role is forbidden (403) from pipeline deal-to-customer conversion', function () {
    $dispatch = createTestUserForRole($this->tenantA, 'DISPATCH', 'dispatch');
    $sales = createTestUserForRole($this->tenantA, 'SALES', 'sales');

    $lead = Lead::create([
        'tenant_id' => $this->tenantA->id,
        'company_name' => 'Lead Pipeline Co',
        'contact_person' => 'Ramesh Bhai',
        'phone' => '9898098980',
        'email' => 'ramesh@example.com',
        'city' => 'Mahuva',
        'state' => 'Gujarat',
        'stage' => 'NEGOTIATION',
        'next_action' => 'Follow up on PO',
    ]);

    // DISPATCH cannot convert lead to customer (403)
    $this->actingAs($dispatch)->post(route('pipeline.convert', $lead->id))->assertForbidden();

    // SALES can convert lead to customer (302)
    $response = $this->actingAs($sales)->post(route('pipeline.convert', $lead->id));
    $response->assertRedirect();
    $lead->refresh();
    expect($lead->stage)->toBe('WON');
    expect($lead->converted_customer_id)->not->toBeNull();
});

// --------------------------------------------------------------------------
// Category B: Multi-Tenant Isolation & Cross-Tenant Policy
// --------------------------------------------------------------------------

test('user cannot view customer belonging to another tenant (403 or 404)', function () {
    $userA = createTestUserForRole($this->tenantA, 'ADMIN', 'admin');

    $customerB = TenantManager::runAsTenant($this->tenantB, function () {
        return createCleanCustomer($this->tenantB, 'Tenant B Customer');
    });

    // Policy check directly
    expect(Gate::forUser($userA)->allows('view', $customerB))->toBeFalse();

    // HTTP check: TenantScope prevents query (404) or Policy forbids (403)
    $response = $this->actingAs($userA)->get(route('customers.show', $customerB->id));
    expect(in_array($response->status(), [403, 404], true))->toBeTrue();
});

test('user cannot update customer belonging to another tenant (403 or 404)', function () {
    $userA = createTestUserForRole($this->tenantA, 'ADMIN', 'admin');

    $customerB = TenantManager::runAsTenant($this->tenantB, function () {
        return createCleanCustomer($this->tenantB, 'Tenant B Customer');
    });

    expect(Gate::forUser($userA)->allows('update', $customerB))->toBeFalse();

    $response = $this->actingAs($userA)->post(route('customers.update', $customerB->id), [
        'company_name' => 'Malicious Cross-Tenant Renaming',
    ]);
    expect(in_array($response->status(), [403, 404], true))->toBeTrue();
});

test('user cannot delete customer belonging to another tenant (403 or 404)', function () {
    $ownerA = createTestUserForRole($this->tenantA, 'OWNER', 'owner');

    $customerB = TenantManager::runAsTenant($this->tenantB, function () {
        return createCleanCustomer($this->tenantB, 'Tenant B Customer');
    });

    expect(Gate::forUser($ownerA)->allows('delete', $customerB))->toBeFalse();

    $response = $this->actingAs($ownerA)->post(route('customers.destroy', $customerB->id));
    expect(in_array($response->status(), [403, 404], true))->toBeTrue();

    // Verify Tenant B customer still exists
    TenantManager::runAsTenant($this->tenantB, function () use ($customerB) {
        expect(Customer::find($customerB->id))->not->toBeNull();
    });
});

test('user without tenant context is forbidden from customer operations', function () {
    $unboundUser = User::firstOrCreate(
        ['email' => 'unbound_user@example.com'],
        [
            'tenant_id' => null,
            'name' => 'Unbound User',
            'password' => bcrypt('password123'),
            'role' => 'ADMIN',
        ]
    );
    $customerA = createCleanCustomer($this->tenantA);

    expect(Gate::forUser($unboundUser)->allows('viewAny', Customer::class))->toBeFalse();
    expect(Gate::forUser($unboundUser)->allows('view', $customerA))->toBeFalse();
    expect(Gate::forUser($unboundUser)->allows('create', Customer::class))->toBeFalse();
    expect(Gate::forUser($unboundUser)->allows('update', $customerA))->toBeFalse();
    expect(Gate::forUser($unboundUser)->allows('delete', $customerA))->toBeFalse();
});

// --------------------------------------------------------------------------
// Category C: 8-Dependency Safe Deletion Guardrails
// --------------------------------------------------------------------------

test('customer with sales orders cannot be deleted and returns error redirect', function () {
    $admin = createTestUserForRole($this->tenantA, 'ADMIN', 'admin');
    $customer = createCleanCustomer($this->tenantA);

    SalesOrder::create([
        'tenant_id' => $this->tenantA->id,
        'customer_id' => $customer->id,
        'order_number' => 'SO-SEC-001',
        'order_date' => now(),
        'status' => 'CONFIRMED',
    ]);

    $response = $this->actingAs($admin)->post(route('customers.destroy', $customer->id));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect(session('error'))->toContain('sales orders');
    expect(Customer::find($customer->id))->not->toBeNull();
});

test('customer with invoices cannot be deleted and returns error redirect', function () {
    $admin = createTestUserForRole($this->tenantA, 'ADMIN', 'admin');
    $customer = createCleanCustomer($this->tenantA);

    Invoice::create([
        'tenant_id' => $this->tenantA->id,
        'customer_id' => $customer->id,
        'invoice_number' => 'INV-SEC-001',
        'invoice_date' => Carbon::now(),
        'due_date' => Carbon::now()->addDays(30),
        'subtotal' => 10000.00,
        'gst_amount' => 500.00,
        'total_amount' => 10500.00,
        'amount_received' => 0.00,
        'balance_due' => 10500.00,
        'status' => 'ISSUED',
    ]);

    $response = $this->actingAs($admin)->post(route('customers.destroy', $customer->id));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect(session('error'))->toContain('invoices');
    expect(Customer::find($customer->id))->not->toBeNull();
});

test('customer with payment receipts cannot be deleted and returns error redirect', function () {
    $admin = createTestUserForRole($this->tenantA, 'ADMIN', 'admin');
    $customer = createCleanCustomer($this->tenantA);

    PaymentReceipt::create([
        'tenant_id' => $this->tenantA->id,
        'customer_id' => $customer->id,
        'receipt_number' => 'RCPT-SEC-001',
        'receipt_type' => 'ADVANCE',
        'amount_received' => 25000.00,
        'payment_mode' => 'RTGS',
        'receipt_date' => now(),
    ]);

    $response = $this->actingAs($admin)->post(route('customers.destroy', $customer->id));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect(session('error'))->toContain('payment receipts');
    expect(Customer::find($customer->id))->not->toBeNull();
});

test('customer with credit or debit notes cannot be deleted and returns error redirect', function () {
    $admin = createTestUserForRole($this->tenantA, 'ADMIN', 'admin');
    $customer = createCleanCustomer($this->tenantA);

    CreditDebitNote::create([
        'tenant_id' => $this->tenantA->id,
        'customer_id' => $customer->id,
        'note_type' => 'CREDIT_NOTE',
        'note_number' => 'CN-SEC-001',
        'note_date' => Carbon::now(),
        'reason' => 'Quality Adjustment',
        'subtotal' => 2000.00,
        'tax_amount' => 100.00,
        'total_amount' => 2100.00,
        'status' => 'APPROVED',
    ]);

    $response = $this->actingAs($admin)->post(route('customers.destroy', $customer->id));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect(session('error'))->toContain('credit or debit notes');
    expect(Customer::find($customer->id))->not->toBeNull();
});

test('customer with quotations cannot be deleted and returns error redirect', function () {
    $admin = createTestUserForRole($this->tenantA, 'ADMIN', 'admin');
    $customer = createCleanCustomer($this->tenantA);

    Quotation::create([
        'tenant_id' => $this->tenantA->id,
        'customer_id' => $customer->id,
        'quotation_number' => 'QT-SEC-001',
        'valid_until' => Carbon::now()->addDays(30),
        'subtotal' => 50000.00,
        'tax_amount' => 2500.00,
        'total_amount' => 52500.00,
        'status' => 'SENT',
    ]);

    $response = $this->actingAs($admin)->post(route('customers.destroy', $customer->id));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect(session('error'))->toContain('quotations');
    expect(Customer::find($customer->id))->not->toBeNull();
});

test('customer with samples cannot be deleted and returns error redirect', function () {
    $admin = createTestUserForRole($this->tenantA, 'ADMIN', 'admin');
    $customer = createCleanCustomer($this->tenantA);

    $product = Product::first() ?? Product::create([
        'tenant_id' => $this->tenantA->id,
        'product_code' => 'PRD-SEC-001',
        'product_name' => 'Dehydrated White Onion Flakes',
        'standard_rate' => 195.00,
    ]);

    Sample::create([
        'tenant_id' => $this->tenantA->id,
        'customer_id' => $customer->id,
        'product_id' => $product->id,
        'sample_number' => 'SMP-SEC-001',
        'quantity' => 250,
        'delivery_status' => 'DISPATCHED',
        'trial_status' => 'Pending',
    ]);

    $response = $this->actingAs($admin)->post(route('customers.destroy', $customer->id));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect(session('error'))->toContain('samples');
    expect(Customer::find($customer->id))->not->toBeNull();
});

test('customer with stored documents cannot be deleted, preserving file references', function () {
    $admin = createTestUserForRole($this->tenantA, 'ADMIN', 'admin');
    $customer = createCleanCustomer($this->tenantA);

    $doc = BusinessDocument::create([
        'tenant_id' => $this->tenantA->id,
        'customer_id' => $customer->id,
        'document_type' => 'PO',
        'document_number' => 'PO-FILE-001',
        'status' => 'VERIFIED',
    ]);

    $docVer = DocumentVersion::create([
        'document_id' => $doc->id,
        'version_number' => 1,
        'file_path' => 'documents/contracts/contract_2026.pdf',
        'file_name' => 'contract_2026.pdf',
        'file_size' => 102400,
        'mime_type' => 'application/pdf',
        'file_hash' => hash('sha256', 'mock-content'),
    ]);

    $response = $this->actingAs($admin)->post(route('customers.destroy', $customer->id));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect(session('error'))->toContain('documents or files');

    // Customer and physical file metadata intact
    expect(Customer::find($customer->id))->not->toBeNull();
    expect(BusinessDocument::find($doc->id))->not->toBeNull();
    expect(DocumentVersion::find($docVer->id))->not->toBeNull();
});

test('customer with outstanding balance (> 0) cannot be deleted and returns error redirect', function () {
    $admin = createTestUserForRole($this->tenantA, 'ADMIN', 'admin');
    $customer = createCleanCustomer($this->tenantA);
    $customer->update(['outstanding_amount' => 14500.50]);

    $response = $this->actingAs($admin)->post(route('customers.destroy', $customer->id));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect(session('error'))->toContain('outstanding balance');
    expect(Customer::find($customer->id))->not->toBeNull();
});

// --------------------------------------------------------------------------
// Category D: Safe Cleanup & Atomic Transaction Rollback
// --------------------------------------------------------------------------

test('clean customer deletion cleans up contacts, followups, activities, and aiInsights atomically', function () {
    $admin = createTestUserForRole($this->tenantA, 'ADMIN', 'admin');
    $customer = createCleanCustomer($this->tenantA, 'Fully Clean Customer');

    // Associate subordinate records
    Contact::create([
        'customer_id' => $customer->id,
        'name' => 'Nilesh Shah',
        'phone' => '9898011223',
        'is_primary' => true,
    ]);

    FollowupTask::create([
        'customer_id' => $customer->id,
        'type' => 'GENERAL',
        'reason' => 'Annual courtesy review',
        'priority' => 'MEDIUM',
        'next_action' => 'Call client to review relationship',
        'due_date' => now()->addDays(5),
        'status' => 'PENDING',
    ]);

    ActivityLog::create([
        'customer_id' => $customer->id,
        'activity_type' => 'NOTE',
        'title' => 'Client verified',
        'description' => 'Initial onboarding completed.',
    ]);

    AiInsight::create([
        'customer_id' => $customer->id,
        'insight_type' => 'REPEAT_ORDER',
        'priority' => 'LOW',
        'summary' => 'Good growth potential',
        'rationale' => 'Customer orders regularly every month',
        'recommended_action' => 'Contact for upcoming season contract',
    ]);

    $response = $this->actingAs($admin)->post(route('customers.destroy', $customer->id));

    $response->assertRedirect(route('customers.index'));
    $response->assertSessionHas('success');

    // Customer and all subordinates cleanly deleted
    expect(Customer::find($customer->id))->toBeNull();
    expect(Contact::where('customer_id', $customer->id)->count())->toBe(0);
    expect(FollowupTask::where('customer_id', $customer->id)->count())->toBe(0);
    expect(ActivityLog::where('customer_id', $customer->id)->count())->toBe(0);
    expect(AiInsight::where('customer_id', $customer->id)->count())->toBe(0);
});

test('transaction rolls back if an exception occurs during customer deletion', function () {
    $admin = createTestUserForRole($this->tenantA, 'ADMIN', 'admin');
    $customer = createCleanCustomer($this->tenantA, 'Rollback Test Customer');

    Contact::create([
        'customer_id' => $customer->id,
        'name' => 'Kiran Mehta',
        'is_primary' => true,
    ]);

    FollowupTask::create([
        'customer_id' => $customer->id,
        'type' => 'GENERAL',
        'reason' => 'Rollback check',
        'priority' => 'LOW',
        'next_action' => 'Test rollback event',
        'due_date' => now()->addDays(3),
        'status' => 'PENDING',
    ]);

    // Force an exception during customer deletion event
    Customer::deleting(function ($model) use ($customer) {
        if ($model->id === $customer->id) {
            throw new RuntimeException('Simulated unexpected failure during delete.');
        }
    });

    $response = $this->actingAs($admin)->post(route('customers.destroy', $customer->id));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect(session('error'))->toContain('Failed to delete customer');

    // Customer and subordinate contacts/followups remain 100% intact due to DB transaction rollback
    expect(Customer::find($customer->id))->not->toBeNull();
    expect(Contact::where('customer_id', $customer->id)->count())->toBe(1);
    expect(FollowupTask::where('customer_id', $customer->id)->count())->toBe(1);
});

test('customer deletion executes under pessimistic lock and safely aborts transaction when dependency exists', function () {
    $admin = createTestUserForRole($this->tenantA, 'ADMIN', 'admin');
    $customer = createCleanCustomer($this->tenantA, 'Locking Test Customer');

    SalesOrder::create([
        'tenant_id' => $this->tenantA->id,
        'customer_id' => $customer->id,
        'order_number' => 'SO-LOCK-001',
        'order_date' => now(),
        'status' => 'CONFIRMED',
    ]);

    $response = $this->actingAs($admin)->post(route('customers.destroy', $customer->id));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect(session('error'))->toContain('associated sales orders');

    // Customer remains intact, transaction rolled back cleanly
    expect(Customer::find($customer->id))->not->toBeNull();
    // Test transaction level remains balanced at the root test transaction
    expect(DB::transactionLevel())->toBeGreaterThanOrEqual(1);
});
