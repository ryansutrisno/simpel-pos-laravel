<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'category_id',
        'description',
        'purchase_price',
        'selling_price',
        'stock',
        'low_stock_threshold',
        'reorder_point',
        'reorder_quantity',
        'barcode',
        'image',
        'is_active',
        'is_returnable',
        'last_reorder_alert_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_returnable' => 'boolean',
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'low_stock_threshold' => 'integer',
        'reorder_point' => 'integer',
        'reorder_quantity' => 'integer',
        'last_reorder_alert_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stockHistories(): HasMany
    {
        return $this->hasMany(StockHistory::class);
    }

    public function discounts(): BelongsToMany
    {
        return $this->belongsToMany(Discount::class, 'discount_product');
    }

    public function activeDiscounts()
    {
        return $this->discounts()->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            });
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
    }

    public function hasVariants(): bool
    {
        return $this->variants()->where('is_active', true)->exists();
    }

    public function bundleItems(): HasMany
    {
        return $this->hasMany(BundleItem::class);
    }

    public function reorderAlerts(): HasMany
    {
        return $this->hasMany(ReorderAlert::class);
    }

    public function pendingReorderAlerts(): HasMany
    {
        return $this->reorderAlerts()->where('status', 'pending');
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->low_stock_threshold;
    }

    public function needsReorder(): bool
    {
        return $this->stock <= $this->reorder_point;
    }

    public function isReturnable(): bool
    {
        return $this->is_returnable ?? true;
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock', '<=', 'low_stock_threshold');
    }

    public function scopeNeedsReorder(Builder $query): Builder
    {
        return $query->whereColumn('stock', '<=', 'reorder_point');
    }

    public function scopeReturnable(Builder $query): Builder
    {
        return $query->where('is_returnable', true);
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->stock <= 0) {
            return 'out_of_stock';
        }
        if ($this->stock <= $this->low_stock_threshold) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    public function getStockStatusLabelAttribute(): string
    {
        return match ($this->stock_status) {
            'out_of_stock' => 'Stok Habis',
            'low_stock' => 'Stok Menipis',
            default => 'Stok Tersedia',
        };
    }
}
