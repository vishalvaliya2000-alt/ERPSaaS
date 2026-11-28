<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Services\TenantManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Customer::class);

        $query = Customer::with(['contacts', 'salesOrders.items.product', 'invoices.creditDebitNotes', 'followups', 'aiInsights']);

        // Search by company, trade name, customer code, GSTIN, contact person, phone, city
        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('trade_name', 'like', "%{$search}%")
                    ->orWhere('customer_code', 'like', "%{$search}%")
                    ->orWhere('gst_number', 'like', "%{$search}%")
                    ->orWhere('primary_contact_person', 'like', "%{$search}%")
                    ->orWhere('primary_phone', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        // Filter by stage
        if ($stage = $request->input('stage')) {
            if ($stage !== 'ALL') {
                $query->where('stage', $stage);
            }
        }

        // Filter by health score
        if ($health = $request->input('health_score')) {
            if ($health !== 'ALL') {
                $query->where('health_score', $health);
            }
        }

        // Sort
        $sort = $request->input('sort', 'latest');
        match ($sort) {
            'name_asc' => $query->orderBy('company_name', 'asc'),
            'name_desc' => $query->orderBy('company_name', 'desc'),
            'revenue_desc' => $query->orderByDesc('total_revenue'),
            'outstanding_desc' => $query->orderByDesc('outstanding_amount'),
            'oldest' => $query->orderBy('created_at', 'asc'),
            default => $query->orderByDesc('created_at'),
        };

        $customers = $query->paginate(12)->withQueryString();

        $tenantId = TenantManager::getTenantId();
        $nextCustomerCode = Customer::generateNextCode($tenantId);

        $totalRevenue = (float) Customer::sum('total_revenue');
        if ($totalRevenue <= 0) {
            $totalRevenue = (float) Invoice::sum('total_amount');
        }

        $metrics = [
            'total_customers' => Customer::count(),
            'total_revenue' => $totalRevenue,
            'total_outstanding' => (float) Customer::sum('outstanding_amount'),
        ];

        return view('customers.index', compact('customers', 'nextCustomerCode', 'metrics', 'search', 'stage', 'health', 'sort'));
    }

    public function show($id)
    {
        $customer = Customer::with([
            'contacts',
            'salesOrders' => function ($q) {
                $q->with([
                    'items.product',
                    'shipments.items.salesOrderItem.product',
                    'shipments.lrDocument.activeVersion',
                    'poDocument.activeVersion',
                    'documents.activeVersion',
                ])->orderByDesc('order_date');
            },
            'invoices' => function ($q) {
                $q->with([
                    'receipts',
                    'creditDebitNotes',
                    'invoiceDocument.activeVersion',
                    'documents.activeVersion',
                ])->orderByDesc('invoice_date');
            },
            'paymentReceipts' => function ($q) {
                $q->with(['salesOrder', 'invoice'])->orderByDesc('receipt_date');
            },
            'creditDebitNotes' => function ($q) {
                $q->with('invoice')->orderByDesc('note_date');
            },
            'quotations.items.product',
            'samples.product',
            'documents' => function ($q) {
                $q->with(['activeVersion', 'salesOrder', 'commercialShipment', 'invoice'])->orderByDesc('document_date');
            },
            'followups' => fn ($q) => $q->orderBy('due_date'),
            'activities' => fn ($q) => $q->orderByDesc('occurred_at'),
            'aiInsights',
        ])->findOrFail($id);

        Gate::authorize('view', $customer);

        // Authoritatively synchronize & fetch net outstanding and unadjusted PO advances
        $netOutstanding = $customer->recalculateOutstandingAmount();
        $unadjustedAdvances = $customer->totalUnadjustedAdvanceAmount();
        $products = Product::where('is_active', true)->get();

        return view('customers.show', compact('customer', 'products', 'netOutstanding', 'unadjustedAdvances'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Customer::class);

        $validated = $request->validate([
            'customer_code' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'trade_name' => 'nullable|string|max:255',
            'gst_number' => 'nullable|string|max:20',
            'primary_contact_person' => 'nullable|string|max:255',
            'primary_phone' => 'nullable|string|max:30',
            'primary_email' => 'nullable|email|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'payment_terms_days' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);

        $legalName = trim($validated['legal_name'] ?? ($validated['company_name'] ?? ''));
        if (empty($legalName)) {
            return back()->withInput()->withErrors(['company_name' => 'Legal Name of Business is required.']);
        }

        $tradeName = ! empty($validated['trade_name']) ? trim($validated['trade_name']) : null;

        if (empty($request->confirm_duplicate)) {
            $duplicateQuery = Customer::where('company_name', $legalName);

            if (! empty($tradeName)) {
                $duplicateQuery->orWhere('trade_name', $tradeName);
            }

            if (! empty($validated['gst_number'])) {
                $duplicateQuery->orWhere('gst_number', $validated['gst_number']);
            }

            if ($duplicateQuery->exists()) {
                return back()
                    ->withInput()
                    ->with('error', "A customer with this Legal Name, Trade Name, or GSTIN already exists. To proceed anyway, please check 'Confirm Duplicate' and submit again.")
                    ->with('requires_duplicate_confirmation', true);
            }
        }

        $tenantId = TenantManager::getTenantId();

        if (! empty($validated['customer_code'])) {
            $code = trim($validated['customer_code']);
            if (Customer::where('customer_code', $code)->exists()) {
                return back()
                    ->withInput()
                    ->withErrors(['customer_code' => "Customer Code '{$code}' is already in use in your account. Please use a unique code or leave blank to auto-generate."]);
            }
        } else {
            $code = Customer::generateNextCode($tenantId);
        }

        DB::beginTransaction();
        try {
            $customer = Customer::create([
                'tenant_id' => $tenantId,
                'customer_code' => $code,
                'company_name' => $legalName,
                'trade_name' => $tradeName,
                'gst_number' => $validated['gst_number'] ?? null,
                'primary_contact_person' => $validated['primary_contact_person'] ?? null,
                'primary_phone' => $validated['primary_phone'] ?? null,
                'primary_email' => $validated['primary_email'] ?? null,
                'city' => $validated['city'] ?? null,
                'state' => $validated['state'] ?? 'Gujarat',
                'payment_terms_days' => $validated['payment_terms_days'] ?? 30,
                'notes' => $validated['notes'] ?? null,
                'stage' => 'ACTIVE',
            ]);

            if (! empty($validated['primary_contact_person'])) {
                Contact::create([
                    'customer_id' => $customer->id,
                    'name' => $validated['primary_contact_person'],
                    'phone' => $validated['primary_phone'] ?? null,
                    'email' => $validated['primary_email'] ?? null,
                    'is_primary' => true,
                ]);
            }

            DB::commit();

            return redirect()->route('customers.show', $customer->id)->with('success', "✓ Customer '{$customer->company_name}' created successfully!");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to create customer: '.$e->getMessage(), [
                'exception' => $e,
                'request' => $request->except(['password']),
            ]);

            return back()->withInput()->with('error', 'Failed to create customer: '.$e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        Gate::authorize('update', $customer);

        $validated = $request->validate([
            'company_name' => 'nullable|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'trade_name' => 'nullable|string|max:255',
            'gst_number' => 'nullable|string|max:20',
            'primary_contact_person' => 'nullable|string|max:255',
            'primary_phone' => 'nullable|string|max:30',
            'primary_email' => 'nullable|email|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'payment_terms_days' => 'nullable|integer',
            'stage' => 'nullable|string|max:50',
        ]);

        if (! empty($validated['legal_name'])) {
            $validated['company_name'] = $validated['legal_name'];
            unset($validated['legal_name']);
        }

        DB::beginTransaction();
        try {
            $customer->update($validated);
            DB::commit();

            return back()->with('success', "✓ Customer '{$customer->company_name}' updated successfully!");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Failed to update customer #{$id}: ".$e->getMessage(), [
                'exception' => $e,
                'request' => $request->all(),
            ]);

            return back()->withInput()->with('error', 'Failed to update customer: '.$e->getMessage());
        }
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);

        Gate::authorize('delete', $customer);

        DB::beginTransaction();
        try {
            // Pessimistic lock on customer row to prevent concurrent modifications
            $customer = Customer::where('id', $id)->lockForUpdate()->firstOrFail();
            $name = $customer->company_name;

            // Safe deletion guardrails: block deletion if any financial, commercial or operational dependency exists
            $reasons = [];

            if ($customer->salesOrders()->count() > 0) {
                $reasons[] = 'associated sales orders';
            }

            if ($customer->invoices()->count() > 0) {
                $reasons[] = 'associated invoices';
            }

            if ($customer->paymentReceipts()->count() > 0) {
                $reasons[] = 'associated payment receipts';
            }

            if ($customer->creditDebitNotes()->count() > 0) {
                $reasons[] = 'associated credit or debit notes';
            }

            if ($customer->quotations()->count() > 0) {
                $reasons[] = 'associated quotations';
            }

            if ($customer->samples()->count() > 0) {
                $reasons[] = 'associated samples';
            }

            if ($customer->documents()->count() > 0) {
                $reasons[] = 'associated documents or files';
            }

            if ((float) $customer->outstanding_amount > 0.00) {
                $reasons[] = 'an outstanding balance of ₹'.number_format((float) $customer->outstanding_amount, 2);
            }

            if (! empty($reasons)) {
                DB::rollBack();
                $reasonText = implode(', ', $reasons);

                return back()->with('error', "Cannot delete customer '{$name}' because they have {$reasonText}.");
            }

            $customer->contacts()->delete();
            $customer->followups()->delete();
            $customer->activities()->delete();
            $customer->aiInsights()->delete();
            $customer->delete();
            DB::commit();

            return redirect()->route('customers.index')->with('success', "✓ Customer '{$name}' deleted successfully.");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Failed to delete customer #{$id}: ".$e->getMessage(), [
                'exception' => $e,
            ]);

            return back()->with('error', 'Failed to delete customer: '.$e->getMessage());
        }
    }
}
