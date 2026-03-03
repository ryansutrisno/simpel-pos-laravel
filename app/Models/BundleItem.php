<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BundleItem extends Model
{
    use HasFactory;

    protected $table = 'bundle_items';

    protected $fillable = [
        'bundle_id',
        'product_id',
        'variant_id',
        'quantity',
        'is_free',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'is_free' => 'boolean',
        ];
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(ProductBundle::class, 'bundle_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
