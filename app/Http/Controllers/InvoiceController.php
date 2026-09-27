<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentReceipt;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\CommercialShipment;
use App\Models\CommercialShipmentItem;
use App\Models\FollowupTask;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Services\DocumentTransactionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    protected DocumentTransactionService $documentService;

    public function __construct(DocumentTransactionService $documentService)
    {
        $this->documentService = $documentService;
    }

    public function index()
    {
        $invoices = Invoice::with([
            'customer',
            'salesOrder',
            'commercialShipment',
            'receipts',
            'items.product',
            'items.salesOrder',
            'invoiceDocument.activeVersion',
            'documents.activeVersion',
        ])->orderByDesc('invoice_date')->get();
        $receipts = PaymentReceipt::with(['customer', 'invoice'])->orderByDesc('receipt_date')->get();

        $totalBilled = $invoices->sum('total_amount');
        $totalReceived = $invoices->sum('amount_received');
        $totalOutstanding = $invoices->sum('balance_due');
        $pendingCount = $invoices->where('balance_due', '>', 0)->count();

        $customers = Customer::orderBy('company_name')->get();
        $products = Product::orderBy('product_name')->get();
        
        // Open orders that have un-invoiced value remaining
        $orders = SalesOrder::with(['customer', 'invoices'])
            ->orderByDesc('order_date')
            ->get()
            ->filter(function ($o) {
                return $o->remainingInvoicableAmount() > 0;
            });

        // Shipments that have un-invoiced balance remaining
        $shipments = CommercialShipment::with([
                'salesOrder.customer',
                'items.salesOrderItem.product',
                'items.salesOrderItem.salesOrder',
                'items.invoiceItems',
                'invoices'
            ])
            ->orderByDesc('shipment_date')
            ->get()
            ->filter(function ($s) {
                return $s->remaining_invoicable_material_amount > 0;
            });

        $shipmentOptions = [];
        foreach ($shipments as $s) {
            $distinctOrders = $s->getDistinctOrders();
            $firstCust = $s->salesOrder?->customer ?? $distinctOrders->first()?->customer;
            $poStr = $distinctOrders->isNotEmpty() ? $distinctOrders->pluck('order_number')->join(', ') : ($s->salesOrder?->order_number ?? 'PO');
            
            $itemsData = [];
            foreach ($s->items as $it) {
                $order = $it->salesOrderItem?->salesOrder;
                $product = $it->salesOrderItem?->product;
                $remQty = $it->remaining_invoicable_quantity;
                $remAmt = $it->remaining_invoicable_amount;

                $itemsData[] = [
                    'id' => $it->id,
                    'sales_order_id' => $order?->id,
                    'sales_order_number' => $order?->order_number ?? 'PO',
                    'sales_order_item_id' => $it->sales_order_item_id,
                    'product_id' => $product?->id,
                    'product_name' => $product?->product_name ?? 'Product',
                    'quantity' => (float)$it->quantity,
                    'rate' => (float)$it->unit_value,
                    'total_value' => (float)$it->total_value,
                    'invoiced_quantity' => (float)$it->invoiced_quantity,
                    'invoiced_amount' => (float)$it->invoiced_amount,
                    'remaining_quantity' => (float)$remQty,
                    'remaining_amount' => (float)$remAmt,
                    'is_fully_invoiced' => $remAmt <= 0,
                    'selected' => $remAmt > 0,
                ];
            }

            $shipmentAdvance = (float)$distinctOrders->sum(fn($o) => (float)$o->unadjustedAdvanceAmount());
            if ($shipmentAdvance == 0 && $s->salesOrder) {
                $shipmentAdvance = (float)$s->salesOrder->unadjustedAdvanceAmount();
            }

            $totalOrderValue = (float)$distinctOrders->sum('total_amount');
            if ($totalOrderValue == 0 && $s->salesOrder) {
                $totalOrderValue = (float)$s->salesOrder->total_amount;
            }

            $shipmentOptions[] = [
                'id' => $s->id,
                'lr_number' => $s->lr_number,
                'transporter' => $s->transporter,
                'shipment_date' => $s->shipment_date?->format('d M Y') ?? '',
                'customer_id' => $firstCust?->id,
                'customer_name' => $firstCust?->company_name ?? 'Client',
                'sales_order_id' => $s->sales_order_id ?? ($distinctOrders->first()?->id ?? null),
                'freight_payment_type' => $s->freight_payment_type,
                'freight_amount' => (float)$s->freight_amount,
                'material_value' => (float)$s->material_value,
                'remaining_material_amount' => (float)$s->remaining_invoicable_material_amount,
                'unadjusted_advance' => $shipmentAdvance,
                'total_order_value' => $totalOrderValue,
                'label' => "LR-{$s->lr_number} (" . ($firstCust?->company_name ?? 'Client') . " · {$poStr}) — Un-Invoiced: " . formatINR($s->remaining_invoicable_material_amount) . " [Freight: {$s->freight_payment_type}]",
                'items' => $itemsData,
            ];
        }

        // Open orders formatted for customer selection
        $customerOrdersData = [];
        foreach ($customers as $c) {
            $cOrders = SalesOrder::where('customer_id', $c->id)->orderByDesc('order_date')->get();
            $customerOrdersData[$c->id] = $cOrders->map(fn($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'po_number' => $o->po_number,
                'total_amount' => (float)$o->total_amount,
                'remaining_amount' => (float)$o->remainingInvoicableAmount(),
                'advance_received' => (float)$o->advance_received,
                'unadjusted_advance' => (float)$o->unadjustedAdvanceAmount(),
            ])->toArray();
        }

        return view('invoices.index', compact('invoices', 'receipts', 'totalBilled', 'totalReceived', 'totalOutstanding', 'pendingCount', 'customers', 'products', 'orders', 'shipments', 'shipmentOptions', 'customerOrdersData'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sales_order_id' => 'nullable|exists:sales_orders,id',
            'commercial_shipment_id' => 'nullable|exists:commercial_shipments,id',
            'invoice_number' => 'required|unique:invoices,invoice_number|max:100',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'material_subtotal' => 'nullable|numeric|min:0',
            'freight_amount' => 'nullable|numeric|min:0',
            'gst_rate' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'invoice_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:25600',
            'advance_deduction_amount' => 'nullable|numeric|min:0',
            'selected_items' => 'nullable|array',
            'selected_items.*.commercial_shipment_item_id' => 'required_with:selected_items|exists:commercial_shipment_items,id',
            'selected_items.*.quantity' => 'required_with:selected_items|numeric|min:0.01',
            'selected_items.*.rate' => 'required_with:selected_items|numeric|min:0',
            'selected_items.*.amount' => 'required_with:selected_items|numeric|min:0.01',
        ]);

        DB::beginTransaction();
        try {
            $shipment = null;
            $order = null;
            $materialSubtotal = 0;
            $linkedPoList = [];
            $firstOrderId = null;

            // SCENARIO 1: Specific line items from Shipment LR are selected
            if (!empty($validated['selected_items']) && count($validated['selected_items']) > 0) {
                if (!empty($validated['commercial_shipment_id'])) {
                    $shipment = CommercialShipment::findOrFail($validated['commercial_shipment_id']);
                }

                foreach ($validated['selected_items'] as $itemInput) {
                    $sItem = CommercialShipmentItem::with('salesOrderItem.salesOrder', 'salesOrderItem.product')->findOrFail($itemInput['commercial_shipment_item_id']);
                    $itemQty = (float)$itemInput['quantity'];
                    $itemRate = (float)$itemInput['rate'];
                    $itemAmt = (float)$itemInput['amount'];

                    // Strict balance check per line item
                    $remAmt = $sItem->remaining_invoicable_amount;
                    if ($itemAmt > ($remAmt + 0.01)) {
                        DB::rollBack();
                        return back()->with('error', "🚫 Disallowed: Invoiced amount (" . formatINR($itemAmt) . ") exceeds remaining un-invoiced amount (" . formatINR($remAmt) . ") for {$sItem->salesOrderItem?->product?->product_name} on PO {$sItem->salesOrderItem?->salesOrder?->order_number}.");
                    }

                    $materialSubtotal += $itemAmt;

                    $poNum = $sItem->salesOrderItem?->salesOrder?->order_number;
                    if ($poNum && !in_array($poNum, $linkedPoList)) {
                        $linkedPoList[] = $poNum;
                    }
                    if (!$firstOrderId && $sItem->salesOrderItem?->sales_order_id) {
                        $firstOrderId = $sItem->salesOrderItem->sales_order_id;
                    }
                }
            } else {
                // SCENARIO 2: Fallback manual subtotal / single PO invoice
                $materialSubtotal = (float)($validated['material_subtotal'] ?? 0);

                if ($materialSubtotal <= 0) {
                    DB::rollBack();
                    return back()->with('error', "🚫 Please enter a valid material subtotal or select shipment items.");
                }

                if (!empty($validated['commercial_shipment_id'])) {
                    $shipment = CommercialShipment::findOrFail($validated['commercial_shipment_id']);
                    if ($materialSubtotal > $shipment->remaining_invoicable_material_amount) {
                        DB::rollBack();
                        return back()->with('error', "🚫 Disallowed: Material subtotal (" . formatINR($materialSubtotal) . ") exceeds the remaining un-invoiced material value (" . formatINR($shipment->remaining_invoicable_material_amount) . ") on Shipment LR {$shipment->lr_number}.");
                    }
                    $linkedPoList = $shipment->getDistinctOrders()->pluck('order_number')->toArray();
                }

                if (!empty($validated['sales_order_id'])) {
                    $order = SalesOrder::findOrFail($validated['sales_order_id']);
                    if (!$shipment && $order) {
                        // Auto-resolve shipment from sales order if dispatched
                        $shipment = CommercialShipment::whereHas('items.salesOrderItem', function($q) use ($order) {
                            $q->where('sales_order_id', $order->id);
                        })->latest('shipment_date')->first();
                    }
                    if ($materialSubtotal > $order->remainingInvoicableAmount()) {
                        DB::rollBack();
                        return back()->with('error', "🚫 Disallowed: Material subtotal (" . formatINR($materialSubtotal) . ") exceeds un-invoiced balance on PO {$order->order_number}.");
                    }
                    if (!in_array($order->order_number, $linkedPoList)) {
                        $linkedPoList[] = $order->order_number;
                    }
                    $firstOrderId = $order->id;
                }
            }

            $freightAmount = (float) ($validated['freight_amount'] ?? 0);
            $subtotal = $materialSubtotal + $freightAmount;
            $gstRate = isset($validated['gst_rate']) && $validated['gst_rate'] !== null && $validated['gst_rate'] !== '' ? (float) $validated['gst_rate'] : 5.0;
            $gstAmount = $subtotal * ($gstRate / 100);
            $totalAmount = $subtotal + $gstAmount;

            $cust = Customer::findOrFail($validated['customer_id']);
            $invDate = Carbon::parse($validated['invoice_date']);
            $dueDate = !empty($validated['due_date']) ? Carbon::parse($validated['due_date']) : $invDate->copy()->addDays($cust->payment_terms_days ?: 30);

            $linkedPosString = !empty($linkedPoList) ? implode(', ', $linkedPoList) : null;

            // Check linked sales orders for unadjusted advance payments
            $linkedOrders = collect();
            if ($order) {
                $linkedOrders->push($order);
            }
            if ($request->has('items') && is_array($request->input('items'))) {
                $orderIds = collect($request->input('items'))->pluck('order_id')->filter()->unique();
                foreach ($orderIds as $oid) {
                    $so = SalesOrder::find($oid);
                    if ($so) {
                        if (!$firstOrderId) {
                            $firstOrderId = $so->id;
                        }
                        if (!in_array($so->order_number, $linkedPoList)) {
                            $linkedPoList[] = $so->order_number;
                        }
                        if (!$linkedOrders->contains('id', $so->id)) {
                            $linkedOrders->push($so);
                        }
                    }
                }
            }
            if ($shipment) {
                foreach ($shipment->getDistinctOrders() as $so) {
                    if (!$linkedOrders->contains('id', $so->id)) {
                        $linkedOrders->push($so);
                    }
                }
            }

            $totalAvailableAdvance = $linkedOrders->sum(fn($so) => $so->unadjustedAdvanceAmount());

            if ($request->has('advance_deduction_amount') && $request->input('advance_deduction_amount') !== null && $request->input('advance_deduction_amount') !== '') {
                $desiredAdvance = (float) $request->input('advance_deduction_amount');
                $advanceApplied = max(0, min($desiredAdvance, $totalAmount, (float)$totalAvailableAdvance));
            } else {
                $advanceApplied = min($totalAmount, (float)$totalAvailableAdvance);
            }

            $balanceDue = max(0, $totalAmount - $advanceApplied);
            $invStatus = $balanceDue <= 0 ? 'PAID' : ($advanceApplied > 0 ? 'PART_PAID' : 'ISSUED');

            $invoice = Invoice::create([
                'invoice_number' => $validated['invoice_number'],
                'customer_id' => $cust->id,
                'sales_order_id' => $firstOrderId,
                'commercial_shipment_id' => $shipment?->id,
                'linked_po_numbers' => $linkedPosString,
                'invoice_date' => $invDate,
                'due_date' => $dueDate,
                'material_subtotal' => $materialSubtotal,
                'freight_amount' => $freightAmount,
                'subtotal' => $subtotal,
                'gst_amount' => $gstAmount,
                'total_amount' => $totalAmount,
                'amount_received' => $advanceApplied,
                'balance_due' => $balanceDue,
                'status' => $invStatus,
                'notes' => $validated['notes'] ?? null,
            ]);

            // If advance was applied, record payment receipt linking advance to invoice
            if ($advanceApplied > 0) {
                $remToApply = $advanceApplied;
                foreach ($linkedOrders as $so) {
                    $unadj = $so->unadjustedAdvanceAmount();
                    if ($unadj > 0 && $remToApply > 0) {
                        $portion = min($unadj, $remToApply);
                        PaymentReceipt::create([
                            'tenant_id' => $invoice->tenant_id,
                            'receipt_number' => 'ADJ-' . date('Ymd') . '-' . rand(100, 999),
                            'invoice_id' => $invoice->id,
                            'sales_order_id' => $so->id,
                            'receipt_type' => 'PO_ADVANCE',
                            'customer_id' => $cust->id,
                            'receipt_date' => $invDate,
                            'amount_received' => $portion,
                            'payment_mode' => 'ADVANCE_OFFSET',
                            'reference_number' => "PO-ADV-{$so->order_number}",
                            'remarks' => "Advance received on PO {$so->order_number} adjusted against Invoice {$invoice->invoice_number}",
                        ]);
                        $remToApply -= $portion;
                    }
                }
            }

            // Save individual invoice line items if selected from shipment
            if (!empty($validated['selected_items'])) {
                foreach ($validated['selected_items'] as $itemInput) {
                    $sItem = CommercialShipmentItem::with('salesOrderItem')->findOrFail($itemInput['commercial_shipment_item_id']);
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'commercial_shipment_item_id' => $sItem->id,
                        'sales_order_item_id' => $sItem->sales_order_item_id,
                        'sales_order_id' => $sItem->salesOrderItem?->sales_order_id,
                        'product_id' => $sItem->salesOrderItem?->product_id ?? 1,
                        'quantity' => (float)$itemInput['quantity'],
                        'rate' => (float)$itemInput['rate'],
                        'amount' => (float)$itemInput['amount'],
                    ]);
                }
            } elseif ($request->has('items') && is_array($request->input('items'))) {
                foreach ($request->input('items') as $dItem) {
                    if (!empty($dItem['product_id']) && !empty($dItem['quantity'])) {
                        $qty = (float)$dItem['quantity'];
                        $rate = (float)($dItem['rate'] ?? 0);
                        InvoiceItem::create([
                            'invoice_id' => $invoice->id,
                            'product_id' => $dItem['product_id'],
                            'sales_order_id' => !empty($dItem['order_id']) ? $dItem['order_id'] : null,
                            'quantity' => $qty,
                            'rate' => $rate,
                            'amount' => $qty * $rate,
                        ]);
                    }
                }
            }

            // Update customer totals
            $cust->increment('total_revenue', $totalAmount);
            if ($balanceDue > 0) {
                $cust->increment('outstanding_amount', $balanceDue);
            }

            // Follow-up task for payment tracking (only if balance remains)
            if ($balanceDue > 0) {
                FollowupTask::create([
                    'customer_id' => $cust->id,
                    'type' => 'PAYMENT_FOLLOWUP',
                    'reason' => "Payment follow-up for Tax Invoice {$invoice->invoice_number} (Balance: " . formatINR($balanceDue) . ")",
                    'priority' => 'HIGH',
                    'due_date' => $dueDate->copy()->subDays(5),
                    'status' => 'PENDING',
                    'pending_item' => "Payment settlement for {$invoice->invoice_number}",
                    'next_action' => "Send WhatsApp invoice reminder and check expected RTGS payment date",
                ]);
            }

            $linkInfo = [];
            if ($linkedPosString) $linkInfo[] = "PO(s): {$linkedPosString}";
            if ($shipment) $linkInfo[] = "LR: {$shipment->lr_number}";
            $linkStr = !empty($linkInfo) ? " against " . implode(' & ', $linkInfo) : "";

            $advNote = $advanceApplied > 0 ? " (Less " . formatINR($advanceApplied) . " advance adjusted, Balance Due: " . formatINR($balanceDue) . ")" : "";
            ActivityLog::create([
                'customer_id' => $cust->id,
                'activity_type' => 'NOTE',
                'title' => "Invoice Generated ({$invoice->invoice_number})",
                'description' => "Generated Tax Invoice for " . formatINR($totalAmount) . "{$advNote} (Due: " . $dueDate->format('d M Y') . "){$linkStr}." . ($freightAmount > 0 ? " (Incl. " . formatINR($freightAmount) . " freight)" : ""),
            ]);

            // 1. Attach supporting document if uploaded inside transaction form
            if ($request->hasFile('invoice_document')) {
                $this->documentService->attachInvoiceSupportingDoc($invoice, $request->file('invoice_document'), $validated['notes'] ?? null);
            }

            // 2. Automatically generate and store ERP Tax Invoice PDF
            $this->documentService->generateAndStoreInvoicePdf($invoice);

            DB::commit();

            $successMsg = "✓ Tax Invoice {$invoice->invoice_number} created for " . formatINR($totalAmount);
            if ($advanceApplied > 0) {
                $successMsg .= " (" . formatINR($advanceApplied) . " advance adjusted, Balance due: " . formatINR($balanceDue) . ")";
            }
            $successMsg .= " linked to " . ($linkedPosString ?: 'PO') . "!";

            return redirect()->route('invoices.index')->with('success', $successMsg);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Invoice generation failed: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all(),
            ]);
            return back()->withInput()->with('error', 'Invoice generation failed: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $invoice = Invoice::with(['receipts', 'customer'])->findOrFail($id);

        // RULE: Cannot edit if any payment has been received
        if ($invoice->amount_received > 0 || $invoice->receipts()->count() > 0 || in_array($invoice->status, ['PAID', 'PART_PAID'])) {
            return back()->with('error', "🚫 Disallowed: Invoice {$invoice->invoice_number} has already received payment settlements (" . formatINR($invoice->amount_received) . "). It cannot be edited.");
        }

        $validated = $request->validate([
            'invoice_number' => 'required|max:100|unique:invoices,invoice_number,' . $id,
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'material_subtotal' => 'required|numeric|min:0.01',
            'freight_amount' => 'nullable|numeric|min:0',
            'gst_rate' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $materialSubtotal = (float) $validated['material_subtotal'];
            $freightAmount = (float) ($validated['freight_amount'] ?? 0);
            $subtotal = $materialSubtotal + $freightAmount;

            $gstRate = isset($validated['gst_rate']) && $validated['gst_rate'] !== null && $validated['gst_rate'] !== '' ? (float) $validated['gst_rate'] : 5.0;
            $gstAmount = $subtotal * ($gstRate / 100);
            $newTotalAmount = $subtotal + $gstAmount;

            $oldTotalAmount = (float) $invoice->total_amount;
            $difference = $newTotalAmount - $oldTotalAmount;

            $cust = $invoice->customer;

            $invoice->update([
                'invoice_number' => $validated['invoice_number'],
                'invoice_date' => Carbon::parse($validated['invoice_date']),
                'due_date' => !empty($validated['due_date']) ? Carbon::parse($validated['due_date']) : $invoice->due_date,
                'material_subtotal' => $materialSubtotal,
                'freight_amount' => $freightAmount,
                'subtotal' => $subtotal,
                'gst_amount' => $gstAmount,
                'total_amount' => $newTotalAmount,
                'balance_due' => $newTotalAmount,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Adjust customer revenue and outstanding balances
            if ($difference > 0) {
                $cust->increment('total_revenue', $difference);
                $cust->increment('outstanding_amount', $difference);
            } elseif ($difference < 0) {
                $cust->decrement('total_revenue', abs($difference));
                $cust->decrement('outstanding_amount', min(abs($difference), $cust->outstanding_amount));
            }

            ActivityLog::create([
                'customer_id' => $cust->id,
                'activity_type' => 'NOTE',
                'title' => "Invoice Updated ({$invoice->invoice_number})",
                'description' => "Updated invoice value to " . formatINR($newTotalAmount) . ".",
            ]);

            DB::commit();
            return redirect()->route('invoices.index')->with('success', "✓ Tax Invoice {$invoice->invoice_number} updated successfully!");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Invoice update failed for #{$id}: " . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all(),
            ]);
            return back()->withInput()->with('error', 'Invoice update failed: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $invoice = Invoice::with(['receipts', 'customer'])->findOrFail($id);

        // RULE: Cannot delete if any payment has been received
        if ($invoice->amount_received > 0 || $invoice->receipts()->count() > 0 || in_array($invoice->status, ['PAID', 'PART_PAID'])) {
            return back()->with('error', "🚫 Disallowed: Invoice {$invoice->invoice_number} has already received payment settlements (" . formatINR($invoice->amount_received) . "). It cannot be deleted.");
        }

        DB::beginTransaction();
        try {
            $invNo = $invoice->invoice_number;
            $amt = (float) $invoice->total_amount;
            $cust = $invoice->customer;

            // Deduct customer revenue and outstanding AR balance
            if ($cust) {
                $cust->decrement('total_revenue', min($amt, $cust->total_revenue));
                $cust->decrement('outstanding_amount', min($amt, $cust->outstanding_amount));
            }

            // Remove any pending follow-up tasks linked to this invoice
            FollowupTask::where('customer_id', $invoice->customer_id)
                ->where('pending_item', 'like', "%{$invNo}%")
                ->delete();

            $invoice->delete();

            if ($cust) {
                ActivityLog::create([
                    'customer_id' => $cust->id,
                    'activity_type' => 'NOTE',
                    'title' => "Invoice Deleted ({$invNo})",
                    'description' => "Cancelled and deleted Tax Invoice {$invNo} for " . formatINR($amt) . ".",
                ]);
            }

            DB::commit();
            return redirect()->route('invoices.index')->with('success', "✓ Invoice {$invNo} has been deleted and customer ledger adjusted.");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Invoice deletion failed for #{$id}: " . $e->getMessage(), [
                'exception' => $e,
            ]);
            return back()->with('error', 'Invoice deletion failed: ' . $e->getMessage());
        }
    }

    public function storeReceipt(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'receipt_date' => 'required|date',
            'amount_received' => 'required|numeric|min:0.01',
            'payment_mode' => 'required|string',
            'reference_number' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $inv = Invoice::with('customer')->findOrFail($validated['invoice_id']);
            $amt = (float) $validated['amount_received'];
            $cust = $inv->customer;

            // RULE: Amount received cannot exceed invoice balance due
            if ($amt > (float) $inv->balance_due) {
                DB::rollBack();
                return back()->with('error', "🚫 Disallowed: Receipt amount (" . formatINR($amt) . ") exceeds the invoice balance due (" . formatINR($inv->balance_due) . ") on {$inv->invoice_number}.");
            }

            $receiptNumber = 'RCP-' . date('Ymd') . '-' . rand(100, 999);

            $receipt = PaymentReceipt::create([
                'receipt_number' => $receiptNumber,
                'invoice_id' => $inv->id,
                'customer_id' => $cust->id,
                'receipt_date' => Carbon::parse($validated['receipt_date']),
                'amount_received' => $amt,
                'payment_mode' => $validated['payment_mode'],
                'reference_number' => $validated['reference_number'] ?? null,
                'remarks' => $validated['remarks'] ?? 'Payment settled against invoice',
            ]);

            // Recalculate invoice balance
            $newReceived = $inv->amount_received + $amt;
            $newBalance = max(0, $inv->total_amount - $newReceived);
            $invStatus = $newBalance <= 0 ? 'PAID' : 'PART_PAID';

            $inv->update([
                'amount_received' => $newReceived,
                'balance_due' => $newBalance,
                'status' => $invStatus,
            ]);

            // Deduct customer outstanding balance
            $cust->decrement('outstanding_amount', min($amt, $cust->outstanding_amount));

            // If invoice is fully settled, mark payment follow-up tasks as COMPLETED
            if ($newBalance <= 0) {
                FollowupTask::where('customer_id', $cust->id)
                    ->where('status', 'PENDING')
                    ->where(function ($q) use ($inv) {
                        $q->where('type', 'PAYMENT_FOLLOWUP')
                          ->where(function ($sq) use ($inv) {
                              $sq->where('pending_item', 'like', "%{$inv->invoice_number}%")
                                 ->orWhere('reason', 'like', "%{$inv->invoice_number}%")
                                 ->orWhere('related_entity_id', $inv->id);
                          });
                    })
                    ->update([
                        'status' => 'COMPLETED',
                        'completed_at' => Carbon::now(),
                        'outcome_notes' => "Invoice {$inv->invoice_number} 100% paid and settled via {$receipt->payment_mode}.",
                    ]);
            }

            ActivityLog::create([
                'customer_id' => $cust->id,
                'activity_type' => 'PAYMENT',
                'title' => "Payment Received (" . formatINR($amt) . ")",
                'description' => "Received via {$receipt->payment_mode} (Ref: {$receipt->reference_number}) against {$inv->invoice_number}.",
            ]);

            DB::commit();
            return redirect()->route('invoices.index')->with('success', "✓ Payment of " . formatINR($amt) . " recorded against {$inv->invoice_number}!");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Receipt recording failed: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all(),
            ]);
            return back()->withInput()->with('error', 'Receipt recording failed: ' . $e->getMessage());
        }
    }

    /**
     * Download or stream the official ERP-generated Tax Invoice PDF.
     */
    public function downloadPdf($id)
    {
        $invoice = Invoice::with(['customer', 'salesOrder', 'commercialShipment', 'items.product', 'invoiceDocument.activeVersion'])->findOrFail($id);

        if ($invoice->invoiceDocument?->activeVersion && \Illuminate\Support\Facades\Storage::disk('public')->exists($invoice->invoiceDocument->activeVersion->file_path)) {
            $path = \Illuminate\Support\Facades\Storage::disk('public')->path($invoice->invoiceDocument->activeVersion->file_path);
            return response()->file($path, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="Tax_Invoice_' . $invoice->invoice_number . '.pdf"',
            ]);
        }

        // Generate and store if not exists yet
        $doc = $this->documentService->generateAndStoreInvoicePdf($invoice);
        if ($doc && $doc->activeVersion && \Illuminate\Support\Facades\Storage::disk('public')->exists($doc->activeVersion->file_path)) {
            $path = \Illuminate\Support\Facades\Storage::disk('public')->path($doc->activeVersion->file_path);
            return response()->file($path, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="Tax_Invoice_' . $invoice->invoice_number . '.pdf"',
            ]);
        }

        // Direct fallback stream
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.pdf', compact('invoice'))->setPaper('a4', 'portrait');
        return $pdf->stream('Tax_Invoice_' . $invoice->invoice_number . '.pdf');
    }
}
