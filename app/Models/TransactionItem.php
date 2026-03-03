<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'product_id',
        'variant_id',
        'quantity',
        'quantity_returned',
        'purchase_price',
        'selling_price',
        'original_price',
        'discount_amount',
        'discount_id',
        'profit',
        'subtotal',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'profit' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'quantity_returned' => 'integer',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany(ProductReturnItem::class);
    }

    public function getReturnableQuantity(): int
    {
        return $this->quantity - ($this->quantity_returned ?? 0);
    }

    public function isFullyReturned(): bool
    {
        return $this->getReturnableQuantity() <= 0;
    }

    public function isPartiallyReturned(): bool
    {
        return $this->quantity_returned > 0 && $this->quantity_returned < $this->quantity;
    }

    public function hasVariant(): bool
    {
        return $this->variant_id !== null;
    }
}
