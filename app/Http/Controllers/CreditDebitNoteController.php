<?php

namespace App\Http\Controllers;

use App\Models\CreditDebitNote;
use App\Models\CreditDebitNoteItem;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Services\TenantManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditDebitNoteController extends Controller
{
    /**
     * Display a listing of Credit and Debit Notes with filters and KPIs
     */
    public function index(Request $request)
    {
        $tenantId = TenantManager::getTenantId() ?? 1;
        $search = $request->query('q', '');
        $type = $request->query('type', 'ALL');
        $status = $request->query('status', 'ALL');
        $customerId = $request->query('customer_id');

        $query = CreditDebitNote::with(['customer', 'invoice', 'items'])
            ->where('tenant_id', $tenantId)
            ->orderByDesc('note_date')
            ->orderByDesc('id');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('note_number', 'like', "%{$search}%")
                  ->orWhere('original_invoice_number', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('company_name', 'like', "%{$search}%")
                         ->orWhere('customer_code', 'like', "%{$search}%");
                  });
            });
        }

        if ($type !== 'ALL') {
            $query->where('note_type', $type);
        }

        if ($status !== 'ALL') {
            $query->where('status', $status);
        }

        $notes = $query->paginate(25)->withQueryString();

        // Calculate KPI summaries
        $allActiveNotes = CreditDebitNote::where('tenant_id', $tenantId)
            ->where('status', '!=', CreditDebitNote::STATUS_CANCELLED)
            ->get();

        $totalCreditAmount = $allActiveNotes
            ->whereIn('note_type', [CreditDebitNote::TYPE_CREDIT, 'CREDIT'])
            ->sum('total_amount');

        $totalDebitAmount = $allActiveNotes
            ->whereIn('note_type', [CreditDebitNote::TYPE_DEBIT, 'DEBIT'])
            ->sum('total_amount');

        $netAdjustment = $totalDebitAmount - $totalCreditAmount;

        $customers = Customer::where('tenant_id', $tenantId)
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'customer_code', 'gst_number', 'state']);

        $invoices = Invoice::where('tenant_id', $tenantId)
            ->with(['customer', 'items'])
            ->orderByDesc('invoice_date')
            ->limit(100)
            ->get();

        // Auto-suggest next sequential numbers
        $year = date('Y');
        $lastCredit = CreditDebitNote::where('tenant_id', $tenantId)
            ->whereIn('note_type', [CreditDebitNote::TYPE_CREDIT, 'CREDIT'])
            ->whereYear('created_at', $year)
            ->count();
        $nextCreditNumber = sprintf('CN-%s-%04d', $year, $lastCredit + 1);

        $lastDebit = CreditDebitNote::where('tenant_id', $tenantId)
            ->whereIn('note_type', [CreditDebitNote::TYPE_DEBIT, 'DEBIT'])
            ->whereYear('created_at', $year)
            ->count();
        $nextDebitNumber = sprintf('DN-%s-%04d', $year, $lastDebit + 1);

        return view('credit_debit_notes.index', compact(
            'notes',
            'search',
            'type',
            'status',
            'customerId',
            'totalCreditAmount',
            'totalDebitAmount',
            'netAdjustment',
            'customers',
            'invoices',
            'nextCreditNumber',
            'nextDebitNumber'
        ));
    }

    /**
     * API endpoint to retrieve invoice details and line items for prefilling
     */
    public function apiGetInvoice($invoiceId)
    {
        $tenantId = TenantManager::getTenantId() ?? 1;
        $invoice = Invoice::with(['customer', 'items.product'])
            ->where('tenant_id', $tenantId)
            ->findOrFail($invoiceId);

        $items = $invoice->items->map(function ($it) {
            return [
                'id' => $it->id,
                'product_id' => $it->product_id,
                'description' => $it->product?->product_name ?? 'Line Item',
                'hsn_code' => $it->product?->hsn_code ?? '07129020',
                'quantity' => (float)$it->quantity,
                'uom' => $it->product?->uom ?? 'KGS',
                'rate' => (float)$it->rate,
                'amount' => (float)$it->amount,
                'tax_rate_percent' => (float)($it->product?->tax_rate_percent ?? 5.00),
            ];
        });

        return response()->json([
            'success' => true,
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'invoice_date' => $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : '',
                'total_amount' => (float)$invoice->total_amount,
                'balance_due' => (float)$invoice->balance_due,
                'customer_id' => $invoice->customer_id,
                'customer_name' => $invoice->customer?->company_name ?? 'Unknown',
                'customer_gst' => $invoice->customer?->gst_number ?? '',
                'customer_state' => $invoice->customer?->state ?? '',
            ],
            'items' => $items,
        ]);
    }

    /**
     * Store a newly created Credit or Debit Note with items
     */
    public function store(Request $request)
    {
        $tenantId = TenantManager::getTenantId() ?? 1;
        $tenant = Tenant::find($tenantId) ?? Tenant::first();

        $validated = $request->validate([
            'note_type' => 'required|in:CREDIT_NOTE,DEBIT_NOTE,CREDIT,DEBIT',
            'note_number' => 'required|string|max:50',
            'note_date' => 'required|date',
            'customer_id' => 'required|exists:customers,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'original_invoice_number' => 'nullable|string|max:50',
            'reason' => 'required|string|max:150',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.hsn_code' => 'nullable|string|max:30',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.uom' => 'nullable|string|max:20',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.tax_rate_percent' => 'nullable|numeric|min:0',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.invoice_item_id' => 'nullable|exists:invoice_items,id',
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);
        $invoiceId = $validated['invoice_id'] ?? null;
        $invoice = !empty($invoiceId) ? Invoice::find($invoiceId) : null;
        $originalInvNumber = ($validated['original_invoice_number'] ?? null) ?: ($invoice?->invoice_number);

        // Check if interstate trade
        $isInterstate = false;
        if (!empty($customer->gst_number) && !empty($tenant?->tax_id_number)) {
            $custCode = substr(trim($customer->gst_number), 0, 2);
            $tenantCode = substr(trim($tenant->tax_id_number), 0, 2);
            $isInterstate = ($custCode !== $tenantCode);
        } elseif (!empty($customer->state) && !empty($tenant?->state)) {
            $isInterstate = (strcasecmp(trim($customer->state), trim($tenant->state)) !== 0);
        }

        DB::beginTransaction();
        try {
            // Compute Subtotal and Taxes
            $subtotal = 0.00;
            $taxAmount = 0.00;
            $preparedItems = [];

            foreach ($validated['items'] as $item) {
                $qty = (float)($item['quantity'] ?? 0);
                $rate = (float)($item['rate'] ?? 0);
                $itemSubtotal = round($qty * $rate, 2);

                $taxRate = isset($item['tax_rate_percent']) ? (float)$item['tax_rate_percent'] : 5.00;
                $itemTax = round($itemSubtotal * ($taxRate / 100), 2);
                $itemTotal = round($itemSubtotal + $itemTax, 2);

                $subtotal += $itemSubtotal;
                $taxAmount += $itemTax;

                $preparedItems[] = [
                    'tenant_id' => $tenantId,
                    'invoice_item_id' => $item['invoice_item_id'] ?? null,
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'hsn_code' => $item['hsn_code'] ?? '07129020',
                    'quantity' => $qty,
                    'uom' => $item['uom'] ?? 'KGS',
                    'rate' => $rate,
                    'subtotal' => $itemSubtotal,
                    'tax_rate_percent' => $taxRate,
                    'tax_amount' => $itemTax,
                    'total_amount' => $itemTotal,
                ];
            }

            $totalAmount = round($subtotal + $taxAmount, 2);

            // Interstate tax breakdown
            if ($isInterstate) {
                $igstAmount = $taxAmount;
                $cgstAmount = 0.00;
                $sgstAmount = 0.00;
            } else {
                $cgstAmount = round($taxAmount / 2, 2);
                $sgstAmount = round($taxAmount - $cgstAmount, 2);
                $igstAmount = 0.00;
            }

            $note = CreditDebitNote::create([
                'tenant_id' => $tenantId,
                'customer_id' => $customer->id,
                'invoice_id' => $invoice?->id,
                'note_type' => $validated['note_type'],
                'note_number' => strtoupper(trim($validated['note_number'])),
                'note_date' => $validated['note_date'],
                'original_invoice_number' => $originalInvNumber,
                'reason' => $validated['reason'],
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'cgst_amount' => $cgstAmount,
                'sgst_amount' => $sgstAmount,
                'igst_amount' => $igstAmount,
                'is_interstate' => $isInterstate,
                'total_amount' => $totalAmount,
                'status' => CreditDebitNote::STATUS_ISSUED,
                'created_by_user_id' => auth()->id(),
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($preparedItems as $pItem) {
                $pItem['credit_debit_note_id'] = $note->id;
                CreditDebitNoteItem::create($pItem);
            }

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "{$note->type_label} {$note->note_number} generated successfully.",
                    'note' => $note,
                ]);
            }

            return redirect()->route('credit-debit-notes.show', $note->id)
                ->with('success', "✓ {$note->type_label} '{$note->note_number}' created successfully!");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to create Credit/Debit Note: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create note: ' . $e->getMessage(),
                ], 500);
            }

            return back()->withInput()->with('error', 'Failed to create note: ' . $e->getMessage());
        }
    }

    /**
     * Display note details
     */
    public function show($id)
    {
        $tenantId = TenantManager::getTenantId() ?? 1;
        $note = CreditDebitNote::with(['customer', 'invoice', 'items.product', 'createdByUser'])
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);

        $tenant = Tenant::find($tenantId) ?? Tenant::first();

        return view('credit_debit_notes.show', compact('note', 'tenant'));
    }

    /**
     * Printable Voucher View
     */
    public function print($id)
    {
        $tenantId = TenantManager::getTenantId() ?? 1;
        $note = CreditDebitNote::with(['customer', 'invoice', 'items.product', 'createdByUser'])
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);

        $tenant = Tenant::find($tenantId) ?? Tenant::first();

        return view('credit_debit_notes.print', compact('note', 'tenant'));
    }

    /**
     * Cancel an issued Credit or Debit Note safely
     */
    public function cancel(Request $request, $id)
    {
        $tenantId = TenantManager::getTenantId() ?? 1;
        $note = CreditDebitNote::where('tenant_id', $tenantId)->findOrFail($id);

        if ($note->status === CreditDebitNote::STATUS_CANCELLED) {
            return back()->with('info', "Note '{$note->note_number}' is already cancelled.");
        }

        $note->update([
            'status' => CreditDebitNote::STATUS_CANCELLED,
            'notes' => ($note->notes ? $note->notes . "\n" : '') . "Cancelled on " . date('Y-m-d H:i') . " by user #" . auth()->id(),
        ]);

        return back()->with('success', "✓ {$note->type_label} '{$note->note_number}' has been cancelled.");
    }
}
