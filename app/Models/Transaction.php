<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_id',
        'discount_id',
        'shift_id',
        'total',
        'subtotal_before_discount',
        'discount_amount',
        'voucher_code',
        'payment_method',
        'payment_gateway_provider',
        'payment_gateway_reference',
        'payment_gateway_transaction_id',
        'payment_gateway_status',
        'payment_gateway_expires_at',
        'paid_at',
        'cash_amount',
        'change_amount',
        'points_earned',
        'points_redeemed',
        'discount_from_points',
        'is_split',
        'total_splits',
        'subtotal_before_tax',
        'tax_amount',
        'tax_rate',
        'tax_enabled',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'subtotal_before_discount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'cash_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'points_earned' => 'integer',
            'points_redeemed' => 'integer',
            'discount_from_points' => 'decimal:2',
            'is_split' => 'boolean',
            'total_splits' => 'integer',
            'subtotal_before_tax' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_enabled' => 'boolean',
            'payment_gateway_expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function pointsHistory(): HasMany
    {
        return $this->hasMany(CustomerPoint::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TransactionPayment::class);
    }

    public function splitBills(): HasMany
    {
        return $this->hasMany(SplitBill::class)->orderBy('split_number');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ProductReturn::class);
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function hasReturns(): bool
    {
        return $this->returns()->exists();
    }

    public function getTotalReturned(): float
    {
        return (float) $this->returns()->sum('total_refund');
    }

    public function isFullyReturned(): bool
    {
        return $this->items()->where('quantity_returned', '<', \DB::raw('quantity'))->doesntExist();
    }

    public function isTaxEnabled(): bool
    {
        return $this->tax_enabled ?? false;
    }

    public function getSubtotalBeforeTaxAttribute(): float
    {
        return (float) ($this->subtotal_before_tax ?? 0);
    }

    public function getTaxAmountAttribute(): float
    {
        return (float) ($this->tax_amount ?? 0);
    }

    public function getTaxRateAttribute(): float
    {
        return (float) ($this->tax_rate ?? 0);
    }

    public function getSubtotalWithoutTaxAttribute(): float
    {
        if ($this->tax_enabled && $this->tax_amount > 0) {
            return (float) ($this->total - $this->tax_amount);
        }

        return (float) $this->total;
    }
}
