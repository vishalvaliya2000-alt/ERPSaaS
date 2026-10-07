<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditDebitNote extends Model
{
    use BelongsToTenant;

    public const TYPE_CREDIT = 'CREDIT_NOTE';
    public const TYPE_DEBIT = 'DEBIT_NOTE';

    public const STATUS_ISSUED = 'ISSUED';
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_CANCELLED = 'CANCELLED';

    public const REASONS = [
        'Sales Return / Rejection',
        'Post-Sale Discount / Rate Difference',
        'Transit Shortage / Weight Loss',
        'Correction in Invoice',
        'Supplementary / Extra Charges',
        'Other Commercial Adjustment',
    ];

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'invoice_id',
        'note_type',
        'note_number',
        'note_date',
        'original_invoice_number',
        'reason',
        'subtotal',
        'tax_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'is_interstate',
        'total_amount',
        'status',
        'created_by_user_id',
        'notes',
    ];

    protected $casts = [
        'note_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',
        'is_interstate' => 'boolean',
        'total_amount' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CreditDebitNoteItem::class, 'credit_debit_note_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function getIsCreditNoteAttribute(): bool
    {
        return in_array($this->note_type, ['CREDIT_NOTE', 'CREDIT']);
    }

    public function getIsDebitNoteAttribute(): bool
    {
        return in_array($this->note_type, ['DEBIT_NOTE', 'DEBIT']);
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->is_credit_note ? 'Credit Note' : 'Debit Note';
    }

    public function getTypeBadgeClassAttribute(): string
    {
        return $this->is_credit_note
            ? 'bg-rose-50 text-rose-700 border-rose-200'
            : 'bg-indigo-50 text-indigo-700 border-indigo-200';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'CANCELLED' => 'bg-neutral-100 text-neutral-500 border-neutral-200',
            default => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        };
    }
}