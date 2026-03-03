<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBundle extends Model
{
    use HasFactory;

    protected $table = 'product_bundles';

    protected $fillable = [
        'name',
        'description',
        'discount_type',
        'discount_value',
        'start_date',
        'end_date',
        'is_active',
        'min_quantity',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'discount_type' => 'string',
            'discount_value' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'min_quantity' => 'integer',
            'priority' => 'integer',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(BundleItem::class, 'bundle_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        $today = today();

        return $query->where('start_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $today);
            });
    }

    public function isValid(): bool
    {
        $today = today();

        return $this->is_active
            && $this->start_date <= $today
            && ($this->end_date === null || $this->end_date >= $today);
    }

    public function isPercentage(): bool
    {
        return $this->discount_type === 'percentage';
    }

    public function isFixedAmount(): bool
    {
        return $this->discount_type === 'fixed_amount';
    }

    public function isFreeItem(): bool
    {
        return $this->discount_type === 'free_item';
    }
}
