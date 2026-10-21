<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    public const DISCOUNT_TYPE_NONE = 'none';

    public const DISCOUNT_TYPES = ['none', 'flat', 'percentage'];

    protected $fillable = [
        'invoice_id',
        'item_description',
        'quantity',
        'unit_price',
        'discount_type',
        'discount_value',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
