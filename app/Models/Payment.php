<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    public const METHODS = ['bank', 'cash', 'upi', 'card', 'other'];

    public const METHOD_LABELS = [
        'bank' => 'Bank Transfer',
        'cash' => 'Cash',
        'upi' => 'UPI',
        'card' => 'Card',
        'other' => 'Other',
    ];

    protected $fillable = [
        'invoice_id',
        'user_id',
        'amount',
        'payment_date',
        'method',
        'reference',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function methodLabel(): string
    {
        return self::METHOD_LABELS[$this->method] ?? ucfirst($this->method);
    }
}
