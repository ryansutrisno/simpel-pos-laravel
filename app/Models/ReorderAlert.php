<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReorderAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'variant_id',
        'current_stock',
        'reorder_point',
        'status',
        'notified_at',
        'acknowledged_by',
        'acknowledged_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'current_stock' => 'integer',
            'reorder_point' => 'integer',
            'notified_at' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAcknowledged($query)
    {
        return $query->where('status', 'acknowledged');
    }

    public function scopeOrdered($query)
    {
        return $query->where('status', 'ordered');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAcknowledged(): bool
    {
        return $this->status === 'acknowledged';
    }

    public function isOrdered(): bool
    {
        return $this->status === 'ordered';
    }
}
