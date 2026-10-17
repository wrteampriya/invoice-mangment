<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory;

    public const CUSTOMER_TYPES = ['individual', 'business'];

    public const TAX_ID_TYPES = ['GSTIN', 'PAN', 'VAT', 'EIN', 'Other'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_type',
        'name',
        'company_name',
        'email',
        'phone',
        'tax_id_type',
        'tax_id',
        'address',
        'city',
        'state',
        'postal_code',
        'country_code',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // TODO: add hasMany invoices() once the invoices table exists.

    /**
     * Company name for businesses, contact name otherwise.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->customer_type === 'business' && $this->company_name
            ? $this->company_name
            : $this->name;
    }

    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $query) use ($like) {
            $query->where('name', 'like', $like)
                ->orWhere('company_name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhere('city', 'like', $like)
                ->orWhere('tax_id', 'like', $like);
        });
    }
}
