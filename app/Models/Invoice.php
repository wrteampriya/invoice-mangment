<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    public const STATUSES = ['draft', 'sent', 'cancelled'];

    /**
     * Display statuses selectable from the create/edit form. The stored
     * column accepts these and they always win over the derived state.
     */
    public const FORM_STATUSES = ['sent', 'overdue', 'paid'];

    public const FORM_STATUS_LABELS = [
        'sent' => 'Sent',
        'overdue' => 'Overdue',
        'paid' => 'Paid',
    ];

    protected $fillable = [
        'user_id',
        'customer_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'currency',
        'customer_country',
        'status',
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
        'status' => 'string',
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

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('payment_date');
    }

    /**
     * Sum of payments received for this invoice. When the relation has been
     * eager-summed (withSum('payments as paid_total_sum', 'amount')) the
     * pre-computed value is reused instead of hitting the database again.
     */
    public function getPaidTotalAttribute(): float
    {
        if (array_key_exists('paid_total_sum', $this->attributes)) {
            return (float) ($this->paid_total_sum ?? 0);
        }

        return (float) $this->payments()->sum('amount');
    }

    /**
     * grand_total - paid_total, never below zero.
     */
    public function getBalanceAttribute(): float
    {
        return max(0, (float) $this->grand_total - $this->paid_total);
    }

    /**
     * Computed status for display: explicit "paid"/"overdue" values chosen in
     * the form always win; otherwise draft/cancelled as stored, otherwise
     * derived from payments and the due date.
     */
    public function getDisplayStatusAttribute(): string
    {
        if (in_array($this->status, ['paid', 'overdue'], true)) {
            return $this->status;
        }

        if (in_array($this->status, ['draft', 'cancelled'], true)) {
            return $this->status;
        }

        if ($this->balance <= 0) {
            return $this->paid_total > 0 ? 'paid' : 'sent';
        }

        if ($this->paid_total > 0) {
            return 'partial';
        }

        if ($this->due_date && $this->due_date->isPast()) {
            return 'overdue';
        }

        return 'sent';
    }

    /**
     * Sent/overdue-marked invoices that still have an outstanding balance.
     * Stored "paid" invoices are excluded.
     */
    public function scopeOutstanding(Builder $query): Builder
    {
        return $query
            ->whereIn('status', ['sent', 'overdue'])
            ->whereRaw('grand_total > COALESCE((SELECT SUM(amount) FROM payments WHERE payments.invoice_id = invoices.id), 0)');
    }

    /**
     * Overdue = the explicit "overdue" form value, or a sent invoice with an
     * outstanding balance whose due date has passed.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->where('status', 'overdue')
                ->orWhere(function (Builder $q) {
                    $q->where('status', 'sent')
                        ->whereRaw('grand_total > COALESCE((SELECT SUM(amount) FROM payments WHERE payments.invoice_id = invoices.id), 0)')
                        ->whereDate('due_date', '<', now()->toDateString());
                });
        });
    }

    /**
     * Outstanding invoices due within the next N days (from today).
     */
    public function scopeDueWithin(Builder $query, int $days): Builder
    {
        return $query->outstanding()->whereBetween('due_date', [
            now()->toDateString(),
            now()->addDays($days)->toDateString(),
        ]);
    }

    /**
     * Paid = the explicit "paid" form value, or a sent invoice whose
     * payments cover the grand total.
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->where('status', 'paid')
                ->orWhere(function (Builder $q) {
                    $q->where('status', 'sent')
                        ->whereRaw('grand_total <= COALESCE((SELECT SUM(amount) FROM payments WHERE payments.invoice_id = invoices.id), 0)');
                });
        });
    }

    /**
     * All invoice queries belong to the issuing user.
     */
    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * "INR 12,500.00" style amount for display.
     */
    public function formatMoney(float|int|string|null $amount): string
    {
        return $this->currency . ' ' . number_format((float) $amount, 2);
    }
}
