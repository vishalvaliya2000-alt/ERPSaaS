<?php

use App\Models\CommercialShipment;
use App\Models\CommercialShipmentItem;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;
use Carbon\Carbon;

beforeEach(function () {
    $this->tenant = Tenant::first() ?? Tenant::create([
        'name' => 'Acme Test Payment Terms Tenant',
        'slug' => 'acme-test-payment-terms-tenant',
        'industry' => 'Food Processing & Exports',
        'plan' => 'Enterprise',
        'currency_code' => 'INR',
        'currency_symbol' => '₹',
        'invoice_prefix' => 'INV-',
        'quotation_prefix' => 'QTN-',
        'po_prefix' => 'PO-',
        'shipment_prefix' => 'SHP-',
    ]);

    $this->user = User::first() ?? User::factory()->create(['tenant_id' => $this->tenant->id]);
    if (! $this->user->tenant_id) {
        $this->user->tenant_id = $this->tenant->id;
        $this->user->save();
    }

    TenantManager::setTenant($this->tenant);

    $this->customer15Days = Customer::create([
        'tenant_id' => $this->tenant->id,
        'customer_code' => 'CUST-15D-' . rand(100, 999),
        'company_name' => 'Test 15 Days Credit Customer',
        'city' => 'Surat',
        'state' => 'Gujarat',
        'gst_number' => '24AAACT' . rand(1000, 9999) . 'Z1Z1',
        'payment_terms_days' => 15,
        'stage' => 'ACTIVE',
    ]);

    $this->product = Product::create([
        'tenant_id' => $this->tenant->id,
        'product_code' => 'PRD-TEST-' . rand(100, 999),
        'product_name' => 'Garlic Powder Fine Mesh',
        'granulation' => 'Fine Powder',
        'standard_rate' => 160,
        'hsn_code' => '07129020',
        'is_active' => true,
    ]);
});

test('invoices.index view receives customersMap with payment_terms_days and customer orders with payment_terms', function () {
    $response = $this->actingAs($this->user)->get(route('invoices.index'));

    $response->assertStatus(200);
    $response->assertViewHas('customersMap');
    $response->assertViewHas('shipmentOptions');
    $response->assertViewHas('customerOrdersData');

    $map = $response->viewData('customersMap');
    expect($map)->toBeArray();
    expect($map[$this->customer15Days->id]['payment_terms_days'])->toBe(15);
});

test('storing invoice uses customer payment terms days when due_date is empty', function () {
    $invDate = Carbon::today()->format('Y-m-d');
    $expectedDueDate = Carbon::today()->addDays(15)->format('Y-m-d');

    $response = $this->actingAs($this->user)->post(route('invoices.store'), [
        'customer_id' => $this->customer15Days->id,
        'invoice_number' => 'INV-TEST-TERM-' . rand(1000, 9999),
        'invoice_date' => $invDate,
        'due_date' => '', // omitted to test fallback
        'material_subtotal' => 50000,
        'gst_rate' => 5,
    ]);

    $response->assertRedirect(route('invoices.index'));

    $invoice = Invoice::where('customer_id', $this->customer15Days->id)->latest('id')->first();
    expect($invoice)->not->toBeNull();
    expect($invoice->due_date->format('Y-m-d'))->toBe($expectedDueDate);
});

test('storing invoice honors explicit auto-calculated due_date from reactive modal', function () {
    $invDate = '2026-10-15';
    $customDueDate = '2026-11-15';

    $response = $this->actingAs($this->user)->post(route('invoices.store'), [
        'customer_id' => $this->customer15Days->id,
        'invoice_number' => 'INV-TEST-CUSTOM-' . rand(1000, 9999),
        'invoice_date' => $invDate,
        'due_date' => $customDueDate,
        'material_subtotal' => 60000,
        'gst_rate' => 5,
    ]);

    $response->assertRedirect(route('invoices.index'));

    $invoice = Invoice::where('customer_id', $this->customer15Days->id)->latest('id')->first();
    expect($invoice)->not->toBeNull();
    expect($invoice->due_date->format('Y-m-d'))->toBe('2026-11-15');
});

test('storing invoice 100% settled by advance aligns due date with invoice date when empty', function () {
    // Create sales order with advance
    $order = SalesOrder::create([
        'tenant_id' => $this->tenant->id,
        'order_number' => 'PO-ADV-' . rand(1000, 9999),
        'customer_id' => $this->customer15Days->id,
        'order_date' => Carbon::today(),
        'subtotal' => 10000,
        'tax_amount' => 500,
        'total_amount' => 10500,
        'advance_required' => 10500,
        'advance_received' => 10500,
        'balance_amount' => 0,
        'payment_terms' => '100% Advance',
        'status' => 'CONFIRMED',
    ]);

    $invDate = Carbon::today()->format('Y-m-d');

    $response = $this->actingAs($this->user)->post(route('invoices.store'), [
        'customer_id' => $this->customer15Days->id,
        'sales_order_id' => $order->id,
        'invoice_number' => 'INV-TEST-FULLADV-' . rand(1000, 9999),
        'invoice_date' => $invDate,
        'due_date' => '', // omitted to test 100% advance alignment
        'material_subtotal' => 10000,
        'gst_rate' => 5,
        'advance_deduction_amount' => 10500,
    ]);

    $response->assertRedirect(route('invoices.index'));

    $invoice = Invoice::where('customer_id', $this->customer15Days->id)->latest('id')->first();
    expect($invoice)->not->toBeNull();
    expect($invoice->balance_due)->toEqual(0);
    expect($invoice->status)->toBe('PAID');
    expect($invoice->due_date->format('Y-m-d'))->toBe($invDate);
});

test('customer credit days can be updated via profile update and reflected in invoices customersMap', function () {
    $response = $this->actingAs($this->user)->post(route('customers.update', $this->customer15Days->id), [
        'company_name' => $this->customer15Days->company_name,
        'payment_terms_days' => 45,
    ]);

    $response->assertSessionHasNoErrors();
    $this->customer15Days->refresh();
    expect($this->customer15Days->payment_terms_days)->toBe(45);

    $indexResponse = $this->actingAs($this->user)->get(route('invoices.index'));
    $map = $indexResponse->viewData('customersMap');
    expect($map[$this->customer15Days->id]['payment_terms_days'])->toBe(45);
});

test('updating invoice recalculates due date based on updated invoice date and credit terms', function () {
    $invDate = '2026-10-10';
    $dueDate = '2026-10-25'; // 15 days

    $invoice = Invoice::create([
        'invoice_number' => 'INV-EDIT-TEST-' . rand(1000, 9999),
        'customer_id' => $this->customer15Days->id,
        'invoice_date' => Carbon::parse($invDate),
        'due_date' => Carbon::parse($dueDate),
        'material_subtotal' => 20000,
        'subtotal' => 20000,
        'gst_amount' => 1000,
        'total_amount' => 21000,
        'balance_due' => 21000,
        'amount_received' => 0,
        'status' => 'ISSUED',
    ]);

    // Update with new invoice date and re-calculated due date (+15 days)
    $newInvDate = '2026-10-20';
    $newDueDate = '2026-11-04';

    $response = $this->actingAs($this->user)->post("/invoices/{$invoice->id}/update", [
        'invoice_number' => $invoice->invoice_number,
        'invoice_date' => $newInvDate,
        'due_date' => $newDueDate,
        'material_subtotal' => 20000,
        'gst_rate' => 5,
    ]);

    $response->assertRedirect(route('invoices.index'));
    $invoice->refresh();
    expect($invoice->invoice_date->format('Y-m-d'))->toBe($newInvDate);
    expect($invoice->due_date->format('Y-m-d'))->toBe($newDueDate);
});

