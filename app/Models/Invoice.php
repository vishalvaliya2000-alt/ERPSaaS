<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'invoice_number',
        'customer_id',
        'sales_order_id',
        'commercial_shipment_id',
        'linked_po_numbers',
        'invoice_date',
        'due_date',
        'material_subtotal',
        'freight_amount',
        'subtotal',
        'gst_amount',
        'total_amount',
        'amount_received',
        'balance_due',
        'status',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'datetime',
        'due_date' => 'datetime',
        'material_subtotal' => 'decimal:2',
        'freight_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_received' => 'decimal:2',
        'balance_due' => 'decimal:2',
    ];

    protected $appends = [
        'invoice_date_formatted',
        'due_date_formatted',
    ];

    public function getInvoiceDateFormattedAttribute(): string
    {
        return $this->invoice_date ? $this->invoice_date->format('Y-m-d') : '';
    }

    public function getDueDateFormattedAttribute(): string
    {
        return $this->due_date ? $this->due_date->format('Y-m-d') : '';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function commercialShipment(): BelongsTo
    {
        return $this->belongsTo(CommercialShipment::class, 'commercial_shipment_id');
    }

    public function getEffectiveShipmentAttribute(): ?CommercialShipment
    {
        if ($this->commercial_shipment_id) {
            return $this->commercialShipment;
        }

        if ($this->sales_order_id) {
            return CommercialShipment::whereHas('items.salesOrderItem', function ($q) {
                $q->where('sales_order_id', $this->sales_order_id);
            })->latest('shipment_date')->first();
        }

        return null;
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(PaymentReceipt::class, 'invoice_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(BusinessDocument::class, 'invoice_id');
    }

    public function invoiceDocument(): HasOne
    {
        return $this->hasOne(BusinessDocument::class, 'invoice_id')
            ->ofMany(['id' => 'max'], function ($q) {
                $q->where('document_type', 'INVOICE');
            });
    }

    public function creditDebitNotes(): HasMany
    {
        return $this->hasMany(CreditDebitNote::class, 'invoice_id');
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditDebitNote::class, 'invoice_id')
            ->whereIn('note_type', ['CREDIT_NOTE', 'CREDIT'])
            ->where('status', '!=', 'CANCELLED');
    }

    public function debitNotes(): HasMany
    {
        return $this->hasMany(CreditDebitNote::class, 'invoice_id')
            ->whereIn('note_type', ['DEBIT_NOTE', 'DEBIT'])
            ->where('status', '!=', 'CANCELLED');
    }

    public function getTotalCreditedAmountAttribute(): float
    {
        if ($this->relationLoaded('creditNotes')) {
            return round((float) $this->creditNotes->sum('total_amount'), 2);
        }

        return round((float) $this->creditNotes()->sum('total_amount'), 2);
    }

    public function getTotalDebitedAmountAttribute(): float
    {
        if ($this->relationLoaded('debitNotes')) {
            return round((float) $this->debitNotes->sum('total_amount'), 2);
        }

        return round((float) $this->debitNotes()->sum('total_amount'), 2);
    }

    /**
     * Adjusted Invoice Total = Original Invoice Total + Valid Debit Notes - Valid Credit Notes
     */
    public function getAdjustedTotalAmountAttribute(): float
    {
        $adjTotal = (float) $this->total_amount + $this->total_debited_amount - $this->total_credited_amount;

        return max(0.00, round($adjTotal, 2));
    }

    /**
     * Adjusted Receivable = max(0, Adjusted Invoice Total - Payments Received)
     */
    public function getAdjustedBalanceDueAttribute(): float
    {
        $adjReceivable = (float) $this->adjusted_total_amount - (float) $this->amount_received;

        return max(0.00, round($adjReceivable, 2));
    }

    /**
     * Check if invoice is settled (adjusted balance due is zero)
     */
    public function getIsSettledAttribute(): bool
    {
        return $this->adjusted_balance_due <= 0.00;
    }

    /**
     * Determine proper lifecycle status based on payments, notes and due date
     */
    public function recalculateSettlementStatus(): string
    {
        $adjBalance = $this->adjusted_balance_due;
        if ($adjBalance <= 0.00) {
            return 'PAID';
        }
        if ((float) $this->amount_received > 0.00) {
            return 'PART_PAID';
        }
        if ($this->due_date && $this->due_date->isPast()) {
            return 'OVERDUE';
        }

        return $this->status === 'PENDING' ? 'PENDING' : 'ISSUED';
    }

    /**
     * Synchronize database balance_due and status to reflect adjusted receivables
     */
    public function syncAdjustedBalances(): void
    {
        $adjBalance = $this->adjusted_balance_due;
        $newStatus = $this->recalculateSettlementStatus();

        $this->update([
            'balance_due' => $adjBalance,
            'status' => $newStatus,
        ]);
    }

    public function getDisplayPoNumbersAttribute(): string
    {
        if (! empty($this->linked_po_numbers)) {
            return $this->linked_po_numbers;
        }
        if ($this->salesOrder) {
            return $this->salesOrder->order_number;
        }
        if ($this->commercialShipment) {
            return $this->commercialShipment->po_numbers;
        }

        return 'Direct Invoice';
    }
}
