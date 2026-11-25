<?php

use App\Models\CreditDebitNote;
use App\Models\Customer;
use App\Models\FollowupTask;
use App\Models\Invoice;
use App\Models\PaymentReceipt;
use App\Models\SalesOrder;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->tenant = Tenant::first() ?? Tenant::create([
        'name' => 'Acme Test Processing Corp',
        'slug' => 'acme-test-processing-corp',
        'industry' => 'Agro Exports',
        'plan' => 'Enterprise',
        'currency_code' => 'INR',
        'currency_symbol' => '₹',
        'tax_id_number' => '24AAACT1234Z1Z5',
        'state' => 'Gujarat',
    ]);

    $this->user = User::first() ?? User::factory()->create(['tenant_id' => $this->tenant->id]);
    if (! $this->user->tenant_id) {
        $this->user->tenant_id = $this->tenant->id;
        $this->user->save();
    }

    TenantManager::setTenant($this->tenant);

    $this->customer = Customer::create([
        'tenant_id' => $this->tenant->id,
        'customer_code' => 'CUST-TEST-'.rand(1000, 9999),
        'company_name' => 'Gujarat Spices Export Private Limited',
        'city' => 'Mahuva',
        'state' => 'Gujarat',
        'gst_number' => '24BBBBT5678Z1Z2',
        'payment_terms_days' => 30,
        'stage' => 'ACTIVE',
        'outstanding_amount' => 0.00,
    ]);
});

test('primary scenario: invoice 114k with 7k debit note and 8k credit note yields 113k receivable and customer outstanding', function () {
    $invoice = Invoice::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_number' => 'INV-TEST-001',
        'invoice_date' => Carbon::now(),
        'due_date' => Carbon::now()->addDays(30),
        'subtotal' => 108571.43,
        'gst_amount' => 5428.57,
        'total_amount' => 114000.00,
        'amount_received' => 0.00,
        'balance_due' => 114000.00,
        'status' => 'ISSUED',
    ]);
    $this->customer->recalculateOutstandingAmount();

    // 1. Initially outstanding is 114,000
    expect((float) $invoice->adjusted_balance_due)->toBe(114000.00);
    expect((float) $this->customer->fresh()->outstanding_amount)->toBe(114000.00);

    // 2. Add Debit Note of 7,000
    $dnPayload = [
        'note_type' => 'DEBIT_NOTE',
        'note_number' => 'DN-2026-T01',
        'note_date' => '2026-10-07',
        'customer_id' => $this->customer->id,
        'invoice_id' => $invoice->id,
        'reason' => 'Supplementary / Extra Charges',
        'items' => [
            [
                'description' => 'Freight and transport surcharge',
                'quantity' => 1.00,
                'rate' => 7000.00,
                'tax_rate_percent' => 0.00,
            ],
        ],
    ];
    $dnResp = $this->actingAs($this->user)->postJson(route('credit-debit-notes.store'), $dnPayload);
    $dnResp->assertOk();

    expect((float) $invoice->fresh()->adjusted_balance_due)->toBe(121000.00);
    expect((float) $this->customer->fresh()->outstanding_amount)->toBe(121000.00);

    // 3. Add Credit Note of 8,000
    $cnPayload = [
        'note_type' => 'CREDIT_NOTE',
        'note_number' => 'CN-2026-T01',
        'note_date' => '2026-10-07',
        'customer_id' => $this->customer->id,
        'invoice_id' => $invoice->id,
        'reason' => 'Post-Sale Discount / Rate Difference',
        'items' => [
            [
                'description' => 'Quality concession rebate',
                'quantity' => 1.00,
                'rate' => 8000.00,
                'tax_rate_percent' => 0.00,
            ],
        ],
    ];
    $cnResp = $this->actingAs($this->user)->postJson(route('credit-debit-notes.store'), $cnPayload);
    $cnResp->assertOk();

    $invFresh = $invoice->fresh();
    expect((float) $invFresh->total_amount)->toBe(114000.00); // Unaltered historical original total
    expect((float) $invFresh->total_debited_amount)->toBe(7000.00);
    expect((float) $invFresh->total_credited_amount)->toBe(8000.00);
    expect((float) $invFresh->adjusted_total_amount)->toBe(113000.00);
    expect((float) $invFresh->adjusted_balance_due)->toBe(113000.00);
    expect((float) $this->customer->fresh()->outstanding_amount)->toBe(113000.00);
});

test('partial payment of 30k reduces receivable to 83k and marks status as PART_PAID', function () {
    $invoice = Invoice::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_number' => 'INV-TEST-002',
        'invoice_date' => Carbon::now(),
        'due_date' => Carbon::now()->addDays(30),
        'total_amount' => 114000.00,
        'amount_received' => 0.00,
        'balance_due' => 114000.00,
        'status' => 'ISSUED',
    ]);

    // Issue DN 7k and CN 8k
    CreditDebitNote::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_id' => $invoice->id,
        'note_type' => 'DEBIT_NOTE',
        'note_number' => 'DN-2026-T02',
        'note_date' => Carbon::now(),
        'total_amount' => 7000.00,
        'status' => 'ISSUED',
    ]);
    CreditDebitNote::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_id' => $invoice->id,
        'note_type' => 'CREDIT_NOTE',
        'note_number' => 'CN-2026-T02',
        'note_date' => Carbon::now(),
        'total_amount' => 8000.00,
        'status' => 'ISSUED',
    ]);
    $this->customer->recalculateOutstandingAmount();

    expect((float) $invoice->fresh()->adjusted_balance_due)->toBe(113000.00);

    // Pay 30,000 partial payment
    $rcpResp = $this->actingAs($this->user)->post(route('invoices.receipt'), [
        'invoice_id' => $invoice->id,
        'receipt_date' => Carbon::now()->format('Y-m-d'),
        'amount_received' => 30000.00,
        'payment_mode' => 'NEFT',
        'reference_number' => 'NEFT-PART-01',
    ]);
    $rcpResp->assertRedirect(route('invoices.index'));

    $invFresh = $invoice->fresh();
    expect((float) $invFresh->amount_received)->toBe(30000.00);
    expect((float) $invFresh->adjusted_balance_due)->toBe(83000.00);
    expect((float) $invFresh->balance_due)->toBe(83000.00);
    expect($invFresh->status)->toBe('PART_PAID');
    expect((float) $this->customer->fresh()->outstanding_amount)->toBe(83000.00);
});

test('full settlement of remaining 83k sets invoice to PAID, balance to zero, and completes followups', function () {
    $invoice = Invoice::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_number' => 'INV-TEST-003',
        'invoice_date' => Carbon::now(),
        'due_date' => Carbon::now()->addDays(30),
        'total_amount' => 114000.00,
        'amount_received' => 30000.00,
        'balance_due' => 83000.00,
        'status' => 'PART_PAID',
    ]);

    CreditDebitNote::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_id' => $invoice->id,
        'note_type' => 'DEBIT_NOTE',
        'note_number' => 'DN-2026-T03',
        'note_date' => Carbon::now(),
        'total_amount' => 7000.00,
        'status' => 'ISSUED',
    ]);
    CreditDebitNote::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_id' => $invoice->id,
        'note_type' => 'CREDIT_NOTE',
        'note_number' => 'CN-2026-T03',
        'note_date' => Carbon::now(),
        'total_amount' => 8000.00,
        'status' => 'ISSUED',
    ]);

    // Create a follow-up task
    $task = FollowupTask::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'type' => 'PAYMENT_FOLLOWUP',
        'status' => 'PENDING',
        'reason' => "Payment pending on {$invoice->invoice_number}",
        'pending_item' => "Payment pending on {$invoice->invoice_number}",
        'next_action' => "Follow up with accounts for payment of {$invoice->invoice_number}",
        'due_date' => Carbon::now()->addDays(5),
    ]);

    $this->customer->recalculateOutstandingAmount();
    expect((float) $invoice->fresh()->adjusted_balance_due)->toBe(83000.00);

    // Pay final settlement of 83,000
    $rcpResp = $this->actingAs($this->user)->post(route('invoices.receipt'), [
        'invoice_id' => $invoice->id,
        'receipt_date' => Carbon::now()->format('Y-m-d'),
        'amount_received' => 83000.00,
        'payment_mode' => 'RTGS',
        'reference_number' => 'RTGS-FULL-01',
    ]);
    $rcpResp->assertRedirect(route('invoices.index'));

    $invFresh = $invoice->fresh();
    expect((float) $invFresh->amount_received)->toBe(113000.00);
    expect((float) $invFresh->adjusted_balance_due)->toBe(0.00);
    expect((float) $invFresh->balance_due)->toBe(0.00);
    expect($invFresh->status)->toBe('PAID');
    expect($invFresh->is_settled)->toBeTrue();
    expect((float) $this->customer->fresh()->outstanding_amount)->toBe(0.00);

    // Verify follow-up task was completed
    expect($task->fresh()->status)->toBe('COMPLETED');
});

test('debit note only permits payment of 121k without rejection', function () {
    $invoice = Invoice::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_number' => 'INV-TEST-004',
        'invoice_date' => Carbon::now(),
        'due_date' => Carbon::now()->addDays(30),
        'total_amount' => 114000.00,
        'amount_received' => 0.00,
        'balance_due' => 114000.00,
        'status' => 'ISSUED',
    ]);

    CreditDebitNote::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_id' => $invoice->id,
        'note_type' => 'DEBIT_NOTE',
        'note_number' => 'DN-2026-T04',
        'note_date' => Carbon::now(),
        'total_amount' => 7000.00,
        'status' => 'ISSUED',
    ]);
    $this->customer->recalculateOutstandingAmount();

    expect((float) $invoice->fresh()->adjusted_balance_due)->toBe(121000.00);

    // Attempt paying full 121,000 (exceeds original 114k, but valid against 121k adjusted due)
    $rcpResp = $this->actingAs($this->user)->post(route('invoices.receipt'), [
        'invoice_id' => $invoice->id,
        'receipt_date' => Carbon::now()->format('Y-m-d'),
        'amount_received' => 121000.00,
        'payment_mode' => 'RTGS',
        'reference_number' => 'RTGS-OVERBILL-01',
    ]);
    $rcpResp->assertRedirect(route('invoices.index'));

    $invFresh = $invoice->fresh();
    expect((float) $invFresh->amount_received)->toBe(121000.00);
    expect((float) $invFresh->adjusted_balance_due)->toBe(0.00);
    expect((float) $invFresh->balance_due)->toBe(0.00);
    expect($invFresh->status)->toBe('PAID');
    expect((float) $this->customer->fresh()->outstanding_amount)->toBe(0.00);
});

test('payment exceeding adjusted receivable is rejected with error', function () {
    $invoice = Invoice::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_number' => 'INV-TEST-005',
        'invoice_date' => Carbon::now(),
        'due_date' => Carbon::now()->addDays(30),
        'total_amount' => 114000.00,
        'amount_received' => 0.00,
        'balance_due' => 114000.00,
        'status' => 'ISSUED',
    ]);

    // Issue Credit Note of 8k -> adjusted due is 106,000
    CreditDebitNote::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_id' => $invoice->id,
        'note_type' => 'CREDIT_NOTE',
        'note_number' => 'CN-2026-T05',
        'note_date' => Carbon::now(),
        'total_amount' => 8000.00,
        'status' => 'ISSUED',
    ]);
    $this->customer->recalculateOutstandingAmount();

    expect((float) $invoice->fresh()->adjusted_balance_due)->toBe(106000.00);

    // Try paying original 114,000 (exceeds 106k adjusted due)
    $rcpResp = $this->actingAs($this->user)->post(route('invoices.receipt'), [
        'invoice_id' => $invoice->id,
        'receipt_date' => Carbon::now()->format('Y-m-d'),
        'amount_received' => 114000.00,
        'payment_mode' => 'NEFT',
    ]);
    $rcpResp->assertSessionHas('error');

    // Confirm nothing was updated
    expect((float) $invoice->fresh()->amount_received)->toBe(0.00);
    expect((float) $invoice->fresh()->adjusted_balance_due)->toBe(106000.00);
});

test('cancelled notes are completely excluded and note cancellation reverses effect exactly once', function () {
    $invoice = Invoice::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_number' => 'INV-TEST-006',
        'invoice_date' => Carbon::now(),
        'due_date' => Carbon::now()->addDays(30),
        'total_amount' => 114000.00,
        'amount_received' => 0.00,
        'balance_due' => 114000.00,
        'status' => 'ISSUED',
    ]);

    $dn = CreditDebitNote::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_id' => $invoice->id,
        'note_type' => 'DEBIT_NOTE',
        'note_number' => 'DN-2026-T06',
        'note_date' => Carbon::now(),
        'total_amount' => 7000.00,
        'status' => 'ISSUED',
    ]);
    $this->customer->recalculateOutstandingAmount();

    expect((float) $invoice->fresh()->adjusted_balance_due)->toBe(121000.00);
    expect((float) $this->customer->fresh()->outstanding_amount)->toBe(121000.00);

    // Cancel Debit Note
    $cancelResp = $this->actingAs($this->user)->post(route('credit-debit-notes.cancel', $dn->id));
    $cancelResp->assertSessionHas('success');

    expect($dn->fresh()->status)->toBe('CANCELLED');
    expect((float) $invoice->fresh()->total_debited_amount)->toBe(0.00);
    expect((float) $invoice->fresh()->adjusted_balance_due)->toBe(114000.00);
    expect((float) $this->customer->fresh()->outstanding_amount)->toBe(114000.00);

    // Cancel again (idempotent, does not double revert)
    $cancelResp2 = $this->actingAs($this->user)->post(route('credit-debit-notes.cancel', $dn->id));
    $cancelResp2->assertSessionHas('info');
    expect((float) $this->customer->fresh()->outstanding_amount)->toBe(114000.00);
});

test('credit note cannot exceed invoice allowable credit ceiling', function () {
    $invoice = Invoice::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_number' => 'INV-TEST-007',
        'invoice_date' => Carbon::now(),
        'due_date' => Carbon::now()->addDays(30),
        'total_amount' => 10000.00,
        'amount_received' => 0.00,
        'balance_due' => 10000.00,
        'status' => 'ISSUED',
    ]);

    // Attempt creating Credit Note of 15,000 on 10,000 invoice
    $cnPayload = [
        'note_type' => 'CREDIT_NOTE',
        'note_number' => 'CN-EXCEED-01',
        'note_date' => '2026-10-07',
        'customer_id' => $this->customer->id,
        'invoice_id' => $invoice->id,
        'reason' => 'Sales Return',
        'items' => [
            [
                'description' => 'Huge credit',
                'quantity' => 1.00,
                'rate' => 15000.00,
                'tax_rate_percent' => 0.00,
            ],
        ],
    ];

    $resp = $this->actingAs($this->user)->postJson(route('credit-debit-notes.store'), $cnPayload);
    $resp->assertStatus(422);
    $resp->assertJson(['success' => false]);

    expect(CreditDebitNote::where('note_number', 'CN-EXCEED-01')->exists())->toBeFalse();
});

test('tenant isolation ensures notes and receipts cannot cross tenants', function () {
    $tenantB = Tenant::create([
        'name' => 'Competitor Corp',
        'slug' => 'competitor-corp-'.rand(100, 999),
    ]);
    $userB = User::factory()->create(['tenant_id' => $tenantB->id]);

    $invoiceA = Invoice::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_number' => 'INV-TENANT-A',
        'invoice_date' => Carbon::now(),
        'due_date' => Carbon::now()->addDays(30),
        'total_amount' => 50000.00,
        'balance_due' => 50000.00,
        'status' => 'ISSUED',
    ]);

    // User B tries to record receipt against Invoice A
    TenantManager::setTenant($tenantB);
    $rcpResp = $this->actingAs($userB)->post(route('invoices.receipt'), [
        'invoice_id' => $invoiceA->id,
        'receipt_date' => Carbon::now()->format('Y-m-d'),
        'amount_received' => 10000.00,
        'payment_mode' => 'UPI',
    ]);

    // Should fail with 404
    $rcpResp->assertStatus(404);
});

test('concurrency row locking prevents simultaneous payment over-settlement on innoDB', function () {
    $invoice = Invoice::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_number' => 'INV-CONCURRENCY-01',
        'invoice_date' => Carbon::now(),
        'due_date' => Carbon::now()->addDays(30),
        'total_amount' => 10000.00,
        'amount_received' => 0.00,
        'balance_due' => 10000.00,
        'status' => 'ISSUED',
    ]);

    // First transaction locks invoice and processes 6,000
    DB::beginTransaction();
    $lockedInv = Invoice::where('id', $invoice->id)->lockForUpdate()->first();
    $amt1 = 6000.00;
    PaymentReceipt::create([
        'tenant_id' => $this->tenant->id,
        'receipt_number' => 'RCP-C1',
        'invoice_id' => $lockedInv->id,
        'customer_id' => $this->customer->id,
        'receipt_date' => Carbon::now(),
        'amount_received' => $amt1,
        'payment_mode' => 'UPI',
    ]);
    $lockedInv->amount_received += $amt1;
    $lockedInv->syncAdjustedBalances();
    DB::commit();

    // Verify balance is 4,000
    expect((float) $invoice->fresh()->adjusted_balance_due)->toBe(4000.00);

    // Second transaction attempts 6,000 (which exceeds remaining 4,000)
    $resp = $this->actingAs($this->user)->post(route('invoices.receipt'), [
        'invoice_id' => $invoice->id,
        'receipt_date' => Carbon::now()->format('Y-m-d'),
        'amount_received' => 6000.00,
        'payment_mode' => 'UPI',
    ]);
    $resp->assertSessionHas('error');

    // Confirm balance remains 4,000
    expect((float) $invoice->fresh()->adjusted_balance_due)->toBe(4000.00);
    expect((float) $invoice->fresh()->amount_received)->toBe(6000.00);
});

test('credit note against partially paid invoice allowed up to unpaid balance', function () {
    $invoice = Invoice::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_number' => 'INV-TEST-PARTIAL-CN',
        'invoice_date' => Carbon::now(),
        'due_date' => Carbon::now()->addDays(30),
        'total_amount' => 100000.00,
        'amount_received' => 40000.00,
        'balance_due' => 60000.00,
        'status' => 'PART_PAID',
    ]);
    $this->customer->recalculateOutstandingAmount();
    expect((float) $invoice->fresh()->adjusted_balance_due)->toBe(60000.00);

    // Issue Credit Note equal to remaining unpaid 60,000
    $cnResp = $this->actingAs($this->user)->postJson(route('credit-debit-notes.store'), [
        'note_type' => 'CREDIT_NOTE',
        'note_number' => 'CN-PART-SETTLE',
        'note_date' => '2026-10-08',
        'customer_id' => $this->customer->id,
        'invoice_id' => $invoice->id,
        'reason' => 'Rate Revision',
        'items' => [
            [
                'description' => 'Agreed discount on open balance',
                'quantity' => 1.00,
                'rate' => 60000.00,
                'tax_rate_percent' => 0.00,
            ],
        ],
    ]);
    $cnResp->assertOk();

    $invFresh = $invoice->fresh();
    expect((float) $invFresh->adjusted_balance_due)->toBe(0.00);
    expect($invFresh->is_settled)->toBeTrue();
    expect((float) $this->customer->fresh()->outstanding_amount)->toBe(0.00);

    // Attempting another credit note on this settled invoice is rejected
    $cnResp2 = $this->actingAs($this->user)->postJson(route('credit-debit-notes.store'), [
        'note_type' => 'CREDIT_NOTE',
        'note_number' => 'CN-PART-EXCEED',
        'note_date' => '2026-10-08',
        'customer_id' => $this->customer->id,
        'invoice_id' => $invoice->id,
        'reason' => 'Excess credit',
        'items' => [
            [
                'description' => 'Excess rebate',
                'quantity' => 1.00,
                'rate' => 1000.00,
                'tax_rate_percent' => 0.00,
            ],
        ],
    ]);
    $cnResp2->assertStatus(422);
});

test('credit note against fully paid invoice rejected with clear message', function () {
    $invoice = Invoice::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_number' => 'INV-TEST-PAID-CN',
        'invoice_date' => Carbon::now(),
        'due_date' => Carbon::now()->addDays(30),
        'total_amount' => 50000.00,
        'amount_received' => 50000.00,
        'balance_due' => 0.00,
        'status' => 'PAID',
    ]);
    $this->customer->recalculateOutstandingAmount();

    $cnResp = $this->actingAs($this->user)->postJson(route('credit-debit-notes.store'), [
        'note_type' => 'CREDIT_NOTE',
        'note_number' => 'CN-PAID-REJECT',
        'note_date' => '2026-10-08',
        'customer_id' => $this->customer->id,
        'invoice_id' => $invoice->id,
        'reason' => 'Quality Concession',
        'items' => [
            [
                'description' => 'Rebate after full payment',
                'quantity' => 1.00,
                'rate' => 5000.00,
                'tax_rate_percent' => 0.00,
            ],
        ],
    ]);

    $cnResp->assertStatus(422);
    $cnResp->assertJson(['success' => false]);
    expect($cnResp->json('message'))->toContain('already fully settled');
});

test('multiple credit and debit notes calculated accurately with chronological independence', function () {
    $invoice = Invoice::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_number' => 'INV-TEST-MULTI-NOTES',
        'invoice_date' => Carbon::now(),
        'due_date' => Carbon::now()->addDays(30),
        'total_amount' => 200000.00,
        'amount_received' => 0.00,
        'balance_due' => 200000.00,
        'status' => 'ISSUED',
    ]);

    // DN1: 15k, DN2: 10k -> total debited = 25k
    CreditDebitNote::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_id' => $invoice->id,
        'note_type' => 'DEBIT_NOTE',
        'note_number' => 'DN-MULTI-1',
        'note_date' => Carbon::now(),
        'total_amount' => 15000.00,
        'status' => 'ISSUED',
    ]);
    CreditDebitNote::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_id' => $invoice->id,
        'note_type' => 'DEBIT_NOTE',
        'note_number' => 'DN-MULTI-2',
        'note_date' => Carbon::now(),
        'total_amount' => 10000.00,
        'status' => 'ISSUED',
    ]);

    // CN1: 20k, CN2: 5k -> total credited = 25k
    CreditDebitNote::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_id' => $invoice->id,
        'note_type' => 'CREDIT_NOTE',
        'note_number' => 'CN-MULTI-1',
        'note_date' => Carbon::now(),
        'total_amount' => 20000.00,
        'status' => 'ISSUED',
    ]);
    $cn2 = CreditDebitNote::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_id' => $invoice->id,
        'note_type' => 'CREDIT_NOTE',
        'note_number' => 'CN-MULTI-2',
        'note_date' => Carbon::now(),
        'total_amount' => 5000.00,
        'status' => 'ISSUED',
    ]);

    $invFresh = $invoice->fresh();
    expect((float) $invFresh->total_debited_amount)->toBe(25000.00);
    expect((float) $invFresh->total_credited_amount)->toBe(25000.00);
    expect((float) $invFresh->adjusted_total_amount)->toBe(200000.00);
    expect((float) $invFresh->adjusted_balance_due)->toBe(200000.00);

    // Pay 100k
    $this->actingAs($this->user)->post(route('invoices.receipt'), [
        'invoice_id' => $invoice->id,
        'receipt_date' => Carbon::now()->format('Y-m-d'),
        'amount_received' => 100000.00,
        'payment_mode' => 'RTGS',
    ]);

    expect((float) $invoice->fresh()->adjusted_balance_due)->toBe(100000.00);

    // Cancel CN2 (5,000 credit) -> active credits become 20,000 -> adjusted total becomes 205,000 -> balance becomes 105,000
    $this->actingAs($this->user)->post(route('credit-debit-notes.cancel', $cn2->id));

    $invFresh2 = $invoice->fresh();
    expect((float) $invFresh2->total_credited_amount)->toBe(20000.00);
    expect((float) $invFresh2->adjusted_total_amount)->toBe(205000.00);
    expect((float) $invFresh2->adjusted_balance_due)->toBe(105000.00);
    expect((float) $this->customer->fresh()->outstanding_amount)->toBe(105000.00);
});

test('fully credited invoice settlement status and UI representation', function () {
    $invoice = Invoice::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_number' => 'INV-TEST-FULL-CREDIT',
        'invoice_date' => Carbon::now(),
        'due_date' => Carbon::now()->addDays(30),
        'total_amount' => 75000.00,
        'amount_received' => 0.00,
        'balance_due' => 75000.00,
        'status' => 'ISSUED',
    ]);

    // Issue Credit Note matching full 75k
    $cn = CreditDebitNote::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_id' => $invoice->id,
        'note_type' => 'CREDIT_NOTE',
        'note_number' => 'CN-FULL-CREDIT',
        'note_date' => Carbon::now(),
        'total_amount' => 75000.00,
        'status' => 'ISSUED',
    ]);
    $invoice->syncAdjustedBalances();
    $this->customer->recalculateOutstandingAmount();

    $invFresh = $invoice->fresh();
    expect((float) $invFresh->adjusted_balance_due)->toBe(0.00);
    expect($invFresh->is_settled)->toBeTrue();
    // In Vertix ERP lifecycle, an invoice settled to 0 balance is marked PAID
    expect($invFresh->status)->toBe('PAID');
    expect((float) $this->customer->fresh()->outstanding_amount)->toBe(0.00);

    // Verify invoice list view counts settled invoices accurately
    $resp = $this->actingAs($this->user)->get(route('invoices.index'));
    $resp->assertOk();
    $resp->assertViewHas('pendingCount');
    $pendingCount = $resp->viewData('pendingCount');
    // Pending count must not include fully credited settled invoice
    $invoices = $resp->viewData('invoices');
    $inPending = $invoices->firstWhere('id', $invoice->id);
    expect($inPending->is_settled)->toBeTrue();
    expect($inPending->adjusted_balance_due)->toBe(0.0);
});

test('schema restriction verification: excess credit note rejected due to lack of credit allocation schema', function () {
    $invoice = Invoice::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_number' => 'INV-TEST-SCHEMA-CHECK',
        'invoice_date' => Carbon::now(),
        'due_date' => Carbon::now()->addDays(30),
        'total_amount' => 20000.00,
        'amount_received' => 0.00,
        'balance_due' => 20000.00,
        'status' => 'ISSUED',
    ]);

    // Attempting credit note of 25,000 on 20,000 invoice
    $resp = $this->actingAs($this->user)->postJson(route('credit-debit-notes.store'), [
        'note_type' => 'CREDIT_NOTE',
        'note_number' => 'CN-OVER-SCHEMA',
        'note_date' => '2026-10-08',
        'customer_id' => $this->customer->id,
        'invoice_id' => $invoice->id,
        'reason' => 'Excess credit',
        'items' => [
            [
                'description' => 'Exceeding credit',
                'quantity' => 1.00,
                'rate' => 25000.00,
                'tax_rate_percent' => 0.00,
            ],
        ],
    ]);

    $resp->assertStatus(422);
    $resp->assertJson(['success' => false]);
    expect($resp->json('message'))->toContain('exceeds invoice remaining unpaid balance');

    // Confirm database has zero negative balances or corrupt records
    expect((float) $invoice->fresh()->adjusted_balance_due)->toBe(20000.00);
});

test('D-4: PO advance before invoice issuance decrements customer outstanding via recalculateOutstandingAmount', function () {
    // 1. Customer has an existing invoice of 100,000
    Invoice::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_number' => 'INV-D4-01',
        'invoice_date' => Carbon::now(),
        'due_date' => Carbon::now()->addDays(30),
        'total_amount' => 100000.00,
        'amount_received' => 0.00,
        'balance_due' => 100000.00,
        'status' => 'ISSUED',
    ]);
    $this->customer->recalculateOutstandingAmount();
    expect((float) $this->customer->fresh()->outstanding_amount)->toBe(100000.00);

    // 2. Sales order created with advance received of 20,000
    $so = SalesOrder::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'order_number' => 'SO-D4-01',
        'order_date' => Carbon::now(),
        'status' => 'CONFIRMED',
        'total_amount' => 50000.00,
        'advance_received' => 20000.00,
        'advance_payment_terms' => '40% advance',
    ]);

    expect((float) $this->customer->totalUnadjustedAdvanceAmount())->toBe(20000.00);

    // Recalculate outstanding amount
    $this->customer->recalculateOutstandingAmount();

    // Customer outstanding must be: 100,000 - 20,000 = 80,000
    expect((float) $this->customer->fresh()->outstanding_amount)->toBe(80000.00);
});

test('D-4: ADVANCE_OFFSET when sales order invoiced offsets advance with zero double-deduction', function () {
    // Sales order with 20k advance
    $so = SalesOrder::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'order_number' => 'SO-D4-02',
        'order_date' => Carbon::now(),
        'status' => 'CONFIRMED',
        'total_amount' => 50000.00,
        'advance_received' => 20000.00,
        'advance_payment_terms' => '40% advance',
    ]);

    // Now an invoice is generated for the sales order
    $soInvoice = Invoice::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'sales_order_id' => $so->id,
        'invoice_number' => 'INV-D4-02',
        'invoice_date' => Carbon::now(),
        'due_date' => Carbon::now()->addDays(30),
        'total_amount' => 50000.00,
        'amount_received' => 20000.00, // Advance offset applied
        'balance_due' => 30000.00,
        'status' => 'PART_PAID',
    ]);

    // Create the ADVANCE_OFFSET payment receipt
    PaymentReceipt::create([
        'tenant_id' => $this->tenant->id,
        'receipt_number' => 'RCP-ADV-OFFSET-01',
        'customer_id' => $this->customer->id,
        'invoice_id' => $soInvoice->id,
        'sales_order_id' => $so->id,
        'receipt_type' => 'PO_ADVANCE',
        'receipt_date' => Carbon::now(),
        'amount_received' => 20000.00,
        'payment_mode' => 'ADVANCE_OFFSET',
        'notes' => 'Offset from Sales Order SO-D4-02 advance',
    ]);

    // Now unadjusted advance is: 20,000 - 20,000 = 0
    expect((float) $this->customer->totalUnadjustedAdvanceAmount())->toBe(0.00);

    // Recalculate customer outstanding
    $this->customer->recalculateOutstandingAmount();

    // Customer outstanding must be exactly 30,000 (remaining receivable on the invoice)
    // There is ZERO double deduction
    expect((float) $this->customer->fresh()->outstanding_amount)->toBe(30000.00);
});

test('D-4: unrelated invoice payment or note cancellation preserves unadjusted advance', function () {
    // Open Sales Order advance of 15,000
    SalesOrder::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'order_number' => 'SO-D4-03',
        'order_date' => Carbon::now(),
        'status' => 'CONFIRMED',
        'total_amount' => 60000.00,
        'advance_received' => 15000.00,
    ]);

    // Invoice of 50,000
    $inv = Invoice::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_number' => 'INV-D4-03',
        'invoice_date' => Carbon::now(),
        'due_date' => Carbon::now()->addDays(30),
        'total_amount' => 50000.00,
        'amount_received' => 0.00,
        'balance_due' => 50000.00,
        'status' => 'ISSUED',
    ]);

    $this->customer->recalculateOutstandingAmount();
    // 50,000 - 15,000 advance = 35,000
    expect((float) $this->customer->fresh()->outstanding_amount)->toBe(35000.00);

    // Issue DN of 5,000
    $dn = CreditDebitNote::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_id' => $inv->id,
        'note_type' => 'DEBIT_NOTE',
        'note_number' => 'DN-D4-PRESERVE',
        'note_date' => Carbon::now(),
        'total_amount' => 5000.00,
        'status' => 'ISSUED',
    ]);
    $this->customer->recalculateOutstandingAmount();
    // (50,000 + 5,000) - 15,000 = 40,000
    expect((float) $this->customer->fresh()->outstanding_amount)->toBe(40000.00);

    // Cancel DN
    $this->actingAs($this->user)->post(route('credit-debit-notes.cancel', $dn->id));
    // Reverts to 35,000, unadjusted advance is completely preserved
    expect((float) $this->customer->fresh()->outstanding_amount)->toBe(35000.00);
});

test('assistant and dashboard reporting reflect adjusted receivables accurately', function () {
    $inv = Invoice::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_number' => 'INV-901',
        'invoice_date' => Carbon::now(),
        'due_date' => Carbon::now()->addDays(30),
        'total_amount' => 100000.00,
        'amount_received' => 0.00,
        'balance_due' => 100000.00,
        'status' => 'ISSUED',
    ]);

    CreditDebitNote::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customer->id,
        'invoice_id' => $inv->id,
        'note_type' => 'CREDIT_NOTE',
        'note_number' => 'CN-REPORT-01',
        'note_date' => Carbon::now(),
        'total_amount' => 15000.00,
        'status' => 'ISSUED',
    ]);

    // Query AssistantController for this invoice
    $resp = $this->actingAs($this->user)->postJson(route('assistant.query'), ['query' => 'INV-901']);
    $resp->assertOk();
    $data = $resp->json();
    expect($data['details']['Balance Due'])->toContain('85,000');
});
