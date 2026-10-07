<?php

use App\Models\CreditDebitNote;
use App\Models\CreditDebitNoteItem;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;
use Carbon\Carbon;

beforeEach(function () {
    $this->tenant = Tenant::first() ?? Tenant::create([
        'name' => 'Acme Dehydration & Exports',
        'slug' => 'acme-dehydration-exports',
        'industry' => 'Agro Processing',
        'plan' => 'Enterprise',
        'currency_code' => 'INR',
        'currency_symbol' => '₹',
        'tax_id_number' => '24AAACT1234Z1Z5', // Gujarat (24)
        'state' => 'Gujarat',
    ]);

    $this->user = User::first() ?? User::factory()->create(['tenant_id' => $this->tenant->id]);
    if (!$this->user->tenant_id) {
        $this->user->tenant_id = $this->tenant->id;
        $this->user->save();
    }

    TenantManager::setTenant($this->tenant);

    $this->customerGujarat = Customer::create([
        'tenant_id' => $this->tenant->id,
        'customer_code' => 'CUST-GJ-' . rand(100, 999),
        'company_name' => 'Gujarat Spices Private Limited',
        'city' => 'Mahuva',
        'state' => 'Gujarat',
        'gst_number' => '24BBBBT5678Z1Z2', // Same state -> CGST + SGST
        'payment_terms_days' => 15,
        'stage' => 'ACTIVE',
    ]);

    $this->customerDelhi = Customer::create([
        'tenant_id' => $this->tenant->id,
        'customer_code' => 'CUST-DL-' . rand(100, 999),
        'company_name' => 'Delhi Agro Hub',
        'city' => 'New Delhi',
        'state' => 'Delhi',
        'gst_number' => '07CCCCP9999Z1Z8', // Different state -> IGST
        'payment_terms_days' => 30,
        'stage' => 'ACTIVE',
    ]);

    $this->product = Product::create([
        'tenant_id' => $this->tenant->id,
        'product_code' => 'DGP-100-TEST',
        'product_name' => 'Dehydrated Garlic Powder (80-100 Mesh)',
        'standard_rate' => 140.00,
        'hsn_code' => '07129020',
        'packaging' => '20 KGs Poly Liner Paper Bag',
        'is_active' => true,
    ]);

    $this->invoice = Invoice::create([
        'tenant_id' => $this->tenant->id,
        'invoice_number' => 'INV-2026-TEST-001',
        'customer_id' => $this->customerGujarat->id,
        'invoice_date' => Carbon::now()->subDays(5),
        'due_date' => Carbon::now()->addDays(10),
        'material_subtotal' => 14000.00,
        'freight_amount' => 1000.00,
        'subtotal' => 15000.00,
        'gst_amount' => 750.00,
        'total_amount' => 15750.00,
        'amount_received' => 0.00,
        'balance_due' => 15750.00,
        'status' => 'ISSUED',
    ]);

    $this->invoiceItem = InvoiceItem::create([
        'tenant_id' => $this->tenant->id,
        'invoice_id' => $this->invoice->id,
        'product_id' => $this->product->id,
        'quantity' => 100.00,
        'rate' => 140.00,
        'amount' => 14000.00,
    ]);
});

test('credit debit notes index page loads with statistics', function () {
    $response = $this->actingAs($this->user)->get(route('credit-debit-notes.index'));

    $response->assertOk();
    $response->assertSee('Credit &amp; Debit Notes', false);
    $response->assertSee('Credit Notes Issued');
    $response->assertSee('Debit Notes Issued');
});

test('api returns invoice details and items for prefilling', function () {
    $response = $this->actingAs($this->user)->getJson(route('api.invoices.details', $this->invoice->id));

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'invoice' => [
            'id' => $this->invoice->id,
            'invoice_number' => 'INV-2026-TEST-001',
            'customer_name' => 'Gujarat Spices Private Limited',
        ],
    ]);
    expect($response->json('items'))->toHaveCount(1);
});

test('user can issue a credit note for sales return with intra-state CGST and SGST', function () {
    $originalInvTotal = $this->invoice->total_amount;
    $originalBalanceDue = $this->invoice->balance_due;

    $payload = [
        'note_type' => 'CREDIT_NOTE',
        'note_number' => 'CN-2026-0001',
        'note_date' => '2026-10-07',
        'customer_id' => $this->customerGujarat->id,
        'invoice_id' => $this->invoice->id,
        'original_invoice_number' => $this->invoice->invoice_number,
        'reason' => 'Sales Return / Rejection',
        'notes' => '10 KGs rejected due to packet puncture',
        'items' => [
            [
                'description' => 'Dehydrated Garlic Powder (80-100 Mesh)',
                'hsn_code' => '07129020',
                'quantity' => 10.00,
                'uom' => 'KGS',
                'rate' => 140.00,
                'tax_rate_percent' => 5.00,
                'product_id' => $this->product->id,
                'invoice_item_id' => $this->invoiceItem->id,
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->postJson(route('credit-debit-notes.store'), $payload);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $note = CreditDebitNote::where('note_number', 'CN-2026-0001')->first();
    expect($note)->not->toBeNull();
    expect($note->is_credit_note)->toBeTrue();
    expect((float)$note->subtotal)->toBe(1400.00); // 10 * 140
    expect((float)$note->tax_amount)->toBe(70.00);  // 5% of 1400
    expect((float)$note->total_amount)->toBe(1470.00);

    // Intra-state check (Both in Gujarat)
    expect($note->is_interstate)->toBeFalse();
    expect((float)$note->cgst_amount)->toBe(35.00);
    expect((float)$note->sgst_amount)->toBe(35.00);
    expect((float)$note->igst_amount)->toBe(0.00);

    // Verify items
    expect($note->items)->toHaveCount(1);
    expect($note->items->first()->description)->toBe('Dehydrated Garlic Powder (80-100 Mesh)');

    // VERY IMPORTANT: Verify that existing invoice record remained completely untouched!
    $freshInvoice = $this->invoice->fresh();
    expect((float)$freshInvoice->total_amount)->toBe((float)$originalInvTotal);
    expect((float)$freshInvoice->balance_due)->toBe((float)$originalBalanceDue);

    // And dynamic adjustment accessor reflects the credit
    expect($freshInvoice->total_credited_amount)->toBe(1470.00);
    expect($freshInvoice->adjusted_balance_due)->toBe(15750.00 - 1470.00);
});

test('user can issue an interstate credit note with IGST', function () {
    $payload = [
        'note_type' => 'CREDIT_NOTE',
        'note_number' => 'CN-2026-0002',
        'note_date' => '2026-10-07',
        'customer_id' => $this->customerDelhi->id,
        'reason' => 'Post-Sale Discount / Rate Difference',
        'items' => [
            [
                'description' => 'Rate difference rebate',
                'hsn_code' => '07129020',
                'quantity' => 100.00,
                'uom' => 'KGS',
                'rate' => 5.00, // ₹5 discount per kg
                'tax_rate_percent' => 5.00,
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->postJson(route('credit-debit-notes.store'), $payload);

    $response->assertOk();
    $note = CreditDebitNote::where('note_number', 'CN-2026-0002')->first();
    expect($note)->not->toBeNull();

    // Interstate check (Gujarat to Delhi)
    expect($note->is_interstate)->toBeTrue();
    expect((float)$note->subtotal)->toBe(500.00);
    expect((float)$note->tax_amount)->toBe(25.00);
    expect((float)$note->igst_amount)->toBe(25.00);
    expect((float)$note->cgst_amount)->toBe(0.00);
    expect((float)$note->sgst_amount)->toBe(0.00);
});

test('user can issue a supplementary debit note', function () {
    $payload = [
        'note_type' => 'DEBIT_NOTE',
        'note_number' => 'DN-2026-0001',
        'note_date' => '2026-10-07',
        'customer_id' => $this->customerGujarat->id,
        'reason' => 'Supplementary / Extra Charges',
        'items' => [
            [
                'description' => 'Unbilled Export Fumigation & Wooden Palletizing',
                'hsn_code' => '998599',
                'quantity' => 1.00,
                'uom' => 'LOT',
                'rate' => 3000.00,
                'tax_rate_percent' => 18.00,
            ]
        ]
    ];

    $response = $this->actingAs($this->user)->postJson(route('credit-debit-notes.store'), $payload);

    $response->assertOk();
    $note = CreditDebitNote::where('note_number', 'DN-2026-0001')->first();
    expect($note)->not->toBeNull();
    expect($note->is_debit_note)->toBeTrue();
    expect((float)$note->subtotal)->toBe(3000.00);
    expect((float)$note->tax_amount)->toBe(540.00); // 18% of 3000
    expect((float)$note->total_amount)->toBe(3540.00);
});

test('note detail and printable voucher pages render cleanly', function () {
    $note = CreditDebitNote::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customerGujarat->id,
        'invoice_id' => $this->invoice->id,
        'note_type' => 'CREDIT_NOTE',
        'note_number' => 'CN-VIEW-001',
        'note_date' => '2026-10-07',
        'original_invoice_number' => $this->invoice->invoice_number,
        'reason' => 'Sales Return',
        'subtotal' => 1000.00,
        'tax_amount' => 50.00,
        'total_amount' => 1050.00,
        'status' => 'ISSUED',
    ]);

    // Detail view
    $showResp = $this->actingAs($this->user)->get(route('credit-debit-notes.show', $note->id));
    $showResp->assertOk();
    $showResp->assertSee('CN-VIEW-001');
    $showResp->assertSee('Gujarat Spices Private Limited');

    // Print view
    $printResp = $this->actingAs($this->user)->get(route('credit-debit-notes.print', $note->id));
    $printResp->assertOk();
    $printResp->assertSee('CN-VIEW-001');
    $printResp->assertSee('Print Voucher');
});

test('user can cancel a note without deleting it', function () {
    $note = CreditDebitNote::create([
        'tenant_id' => $this->tenant->id,
        'customer_id' => $this->customerGujarat->id,
        'note_type' => 'CREDIT_NOTE',
        'note_number' => 'CN-CANCEL-001',
        'note_date' => '2026-10-07',
        'reason' => 'Test Cancel',
        'subtotal' => 500.00,
        'total_amount' => 525.00,
        'status' => 'ISSUED',
    ]);

    $resp = $this->actingAs($this->user)->post(route('credit-debit-notes.cancel', $note->id));
    $resp->assertSessionHas('success');

    expect($note->fresh()->status)->toBe('CANCELLED');
});
