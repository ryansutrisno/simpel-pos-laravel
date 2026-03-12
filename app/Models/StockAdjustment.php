<?php

namespace App\Models;

use App\Enums\AdjustmentReason;
use App\Enums\AdjustmentType;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAdjustment extends Model
{
    use BelongsToStore;
    use HasFactory;

    protected $fillable = [
        'store_id',
        'adjustment_number',
        'adjustment_date',
        'type',
        'reason',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'type' => AdjustmentType::class,
        'reason' => AdjustmentReason::class,
        'adjustment_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->adjustment_number)) {
                $model->adjustment_number = 'ADJ-'.str_pad(StockAdjustment::count() + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isIncrease(): bool
    {
        return $this->type === AdjustmentType::Increase;
    }

    public function isDecrease(): bool
    {
        return $this->type === AdjustmentType::Decrease;
    }
}
