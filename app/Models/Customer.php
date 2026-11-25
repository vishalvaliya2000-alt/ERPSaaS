<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Services\TenantManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Customer extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'customer_code',
        'company_name',
        'trade_name',
        'gst_number',
        'pan_number',
        'payment_terms_days',
        'stage',
        'health_score',
        'status',
        'primary_contact_person',
        'primary_phone',
        'primary_email',
        'address_line',
        'city',
        'state',
        'pincode',
        'country',
        'website',
        'notes',
        'total_revenue',
        'outstanding_amount',
        'last_order_date',
    ];

    protected $casts = [
        'payment_terms_days' => 'integer',
        'total_revenue' => 'decimal:2',
        'outstanding_amount' => 'decimal:2',
        'last_order_date' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($customer) {
            if (! $customer->tenant_id) {
                $customer->tenant_id = TenantManager::getTenantId();
            }
            if (empty($customer->customer_code)) {
                $customer->customer_code = static::generateNextCode($customer->tenant_id);
            }
        });
    }

    /**
     * Generate sequential, zero-padded customer code scoped to tenant (e.g. CUST-0001, CUST-0002).
     * Every new tenant starts at CUST-0001.
     */
    public static function generateNextCode(?int $tenantId = null): string
    {
        $tenantId = $tenantId ?? TenantManager::getTenantId();

        $query = static::withoutGlobalScopes();
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $codes = $query->whereNotNull('customer_code')->pluck('customer_code');

        $maxNum = 0;
        foreach ($codes as $code) {
            if (preg_match('/CUST[-_]?(\d+)/i', $code, $matches)) {
                $num = (int) $matches[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        $nextNum = $maxNum + 1;

        return 'CUST-'.str_pad((string) $nextNum, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Alias accessor for legal name mapping to company_name
     */
    public function getLegalNameAttribute(): string
    {
        return $this->company_name ?? '';
    }

    /**
     * Alias mutator for legal name mapping to company_name
     */
    public function setLegalNameAttribute($value): void
    {
        $this->attributes['company_name'] = $value;
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'customer_id');
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class, 'customer_id');
    }

    public function salesOrderItems(): HasManyThrough
    {
        return $this->hasManyThrough(SalesOrderItem::class, SalesOrder::class, 'customer_id', 'sales_order_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }

    public function paymentReceipts(): HasMany
    {
        return $this->hasMany(PaymentReceipt::class, 'customer_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(BusinessDocument::class, 'customer_id');
    }

    public function samples(): HasMany
    {
        return $this->hasMany(Sample::class, 'customer_id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class, 'customer_id');
    }

    public function followups(): HasMany
    {
        return $this->hasMany(FollowupTask::class, 'customer_id');
    }

    public function followupTasks(): HasMany
    {
        return $this->hasMany(FollowupTask::class, 'customer_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'customer_id');
    }

    public function aiInsights(): HasMany
    {
        return $this->hasMany(AiInsight::class, 'customer_id');
    }

    public function getTotalOrderedQtyAttribute(): float
    {
        return (float) $this->salesOrderItems()->sum('order_qty');
    }

    public function getTotalSuppliedQtyAttribute(): float
    {
        return (float) $this->salesOrderItems()->sum('shipped_qty');
    }

    public function getTotalPendingQtyAttribute(): float
    {
        return (float) $this->salesOrderItems()->sum('balance_qty');
    }

    public function creditDebitNotes(): HasMany
    {
        return $this->hasMany(CreditDebitNote::class, 'customer_id');
    }

    /**
     * Compute total unadjusted advances currently held across all open sales orders.
     */
    public function totalUnadjustedAdvanceAmount(): float
    {
        $orders = $this->salesOrders()->where('advance_received', '>', 0)->with('paymentReceipts')->get();
        $total = 0.00;
        foreach ($orders as $so) {
            $total += $so->unadjustedAdvanceAmount();
        }

        return round($total, 2);
    }

    /**
     * Authoritatively recalculate and synchronize customer outstanding_amount.
     * Outstanding Amount = max(0, sum(invoice adjusted receivables) + standalone debits - unadjusted PO advances - standalone credits)
     */
    public function recalculateOutstandingAmount(): float
    {
        $invoices = $this->invoices()->with(['creditNotes', 'debitNotes'])->get();
        $totalInvoiceDue = 0.00;
        foreach ($invoices as $inv) {
            $totalInvoiceDue += $inv->adjusted_balance_due;
        }

        $unadjustedAdvances = $this->totalUnadjustedAdvanceAmount();

        $standaloneCredits = (float) $this->creditDebitNotes()
            ->whereNull('invoice_id')
            ->whereIn('note_type', ['CREDIT_NOTE', 'CREDIT'])
            ->where('status', '!=', 'CANCELLED')
            ->sum('total_amount');

        $standaloneDebits = (float) $this->creditDebitNotes()
            ->whereNull('invoice_id')
            ->whereIn('note_type', ['DEBIT_NOTE', 'DEBIT'])
            ->where('status', '!=', 'CANCELLED')
            ->sum('total_amount');

        $grossDue = $totalInvoiceDue + $standaloneDebits;
        $totalDeductions = $unadjustedAdvances + $standaloneCredits;

        $netOutstanding = max(0.00, round($grossDue - $totalDeductions, 2));

        $this->update(['outstanding_amount' => $netOutstanding]);

        return $netOutstanding;
    }

    /**
     * Dynamic accessor for adjusted outstanding amount
     */
    public function getAdjustedOutstandingAmountAttribute(): float
    {
        return (float) $this->outstanding_amount;
    }

    public function getFulfillmentPercentageAttribute(): float
    {
        $ordered = $this->total_ordered_qty;
        if ($ordered <= 0) {
            return 100.0;
        }

        return round(($this->total_supplied_qty / $ordered) * 100, 1);
    }

    /**
     * Get consolidated product-wise ordered, shipped, and pending quantities for this customer.
     *
     * @return Collection<int, object>
     */
    public function getProductFulfillmentSummaryAttribute(): Collection
    {
        // If salesOrders is already loaded in memory with items.product, aggregate from collection
        if ($this->relationLoaded('salesOrders')) {
            $summary = [];

            foreach ($this->salesOrders as $order) {
                // Ignore cancelled or rejected orders
                if (in_array(strtoupper($order->status ?? ''), ['CANCELLED', 'REJECTED'])) {
                    continue;
                }

                foreach ($order->items as $item) {
                    $productId = $item->product_id;
                    if (! $productId) {
                        continue;
                    }

                    if (! isset($summary[$productId])) {
                        $product = $item->product;
                        $summary[$productId] = (object) [
                            'product_id' => $productId,
                            'product_name' => $product->product_name ?? ('Product #'.$productId),
                            'product_code' => $product->product_code ?? '',
                            'uom' => $product->uom ?? 'KG',
                            'total_ordered' => 0.0,
                            'total_shipped' => 0.0,
                            'total_pending' => 0.0,
                            'orders_count' => 0,
                            'order_ids' => [],
                            'last_rate' => (float) ($item->rate ?? 0),
                        ];
                    }

                    $summary[$productId]->total_ordered += (float) $item->order_qty;
                    $summary[$productId]->total_shipped += (float) $item->shipped_qty;
                    $summary[$productId]->total_pending += (float) $item->balance_qty;

                    if (! in_array($order->id, $summary[$productId]->order_ids)) {
                        $summary[$productId]->order_ids[] = $order->id;
                        $summary[$productId]->orders_count++;
                    }
                }
            }

            return collect(array_values($summary))->map(function ($item) {
                $ordered = $item->total_ordered;
                $shipped = $item->total_shipped;
                $pending = max(0, $item->total_pending);

                $fulfillmentPct = $ordered > 0 ? min(100.0, round(($shipped / $ordered) * 100, 1)) : 100.0;

                $item->fulfillment_percentage = $fulfillmentPct;
                $item->status = $pending <= 0 ? 'COMPLETED' : ($shipped > 0 ? 'PARTIAL' : 'PENDING');

                return $item;
            })->sortByDesc('total_pending')->values();
        }

        // Database query fallback if salesOrders relation is not eager-loaded
        return DB::table('sales_order_items')
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->join('products', 'sales_order_items.product_id', '=', 'products.id')
            ->where('sales_orders.customer_id', $this->id)
            ->whereNotIn('sales_orders.status', ['CANCELLED', 'REJECTED'])
            ->select(
                'products.id as product_id',
                'products.product_name',
                'products.product_code',
                'products.uom',
                DB::raw('SUM(sales_order_items.order_qty) as total_ordered'),
                DB::raw('SUM(sales_order_items.shipped_qty) as total_shipped'),
                DB::raw('SUM(sales_order_items.balance_qty) as total_pending'),
                DB::raw('COUNT(DISTINCT sales_orders.id) as orders_count')
            )
            ->groupBy('products.id', 'products.product_name', 'products.product_code', 'products.uom')
            ->orderByDesc('total_pending')
            ->get()
            ->map(function ($item) {
                $ordered = (float) $item->total_ordered;
                $shipped = (float) $item->total_shipped;
                $pending = max(0, (float) $item->total_pending);

                $item->total_ordered = $ordered;
                $item->total_shipped = $shipped;
                $item->total_pending = $pending;
                $item->fulfillment_percentage = $ordered > 0 ? min(100.0, round(($shipped / $ordered) * 100, 1)) : 100.0;
                $item->status = $pending <= 0 ? 'COMPLETED' : ($shipped > 0 ? 'PARTIAL' : 'PENDING');

                return $item;
            });
    }
}
