<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditDebitNoteItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'credit_debit_note_id',
        'invoice_item_id',
        'product_id',
        'description',
        'hsn_code',
        'quantity',
        'uom',
        'rate',
        'subtotal',
        'tax_rate_percent',
        'tax_amount',
        'total_amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_rate_percent' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function creditDebitNote(): BelongsTo
    {
        return $this->belongsTo(CreditDebitNote::class, 'credit_debit_note_id');
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class, 'invoice_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
