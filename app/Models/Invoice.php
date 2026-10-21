<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    public const TAX_TYPE_NONE = 'none';

    public const TAX_TYPES = ['none', 'gst', 'vat', 'sales_tax'];

    public const TAX_TYPE_LABELS = [
        'none' => 'None',
        'gst' => 'GST',
        'vat' => 'VAT',
        'sales_tax' => 'Sales Tax',
    ];

    public const CURRENCIES = ['USD', 'INR', 'EUR', 'GBP', 'AUD', 'CAD', 'AED'];

    protected $fillable = [
        'user_id',
        'customer_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'currency',
        'customer_country',
        'tax_type',
        'tax_rate',
        'subtotal',
        'discount_total',
        'tax_total',
        'grand_total',
        'terms_and_conditions',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'tax_rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * "INR 12,500.00" style amount for display.
     */
    public function formatMoney(float|int|string|null $amount): string
    {
        return $this->currency . ' ' . number_format((float) $amount, 2);
    }
}
