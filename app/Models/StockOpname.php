<?php

namespace App\Models;

use App\Enums\OpnameStatus;
use App\Enums\StockMovementType;
use App\Models\Concerns\BelongsToStore;
use App\Services\StockService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class StockOpname extends Model
{
    use BelongsToStore;
    use HasFactory;

    protected $fillable = [
        'store_id',
        'opname_number',
        'status',
        'opname_date',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'status' => OpnameStatus::class,
        'opname_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->opname_number)) {
                $model->opname_number = 'OPN-'.str_pad(StockOpname::count() + 1, 5, '0', STR_PAD_LEFT);
            }
            $model->user_id = Auth::id();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    public function isDraft(): bool
    {
        return $this->status === OpnameStatus::Draft;
    }

    public function isCompleted(): bool
    {
        return $this->status === OpnameStatus::Completed;
    }

    public function complete(): void
    {
        if (! $this->isDraft()) {
            return;
        }

        $stockService = app(StockService::class);

        foreach ($this->items as $item) {
            if ($item->difference !== 0 && $item->actual_stock !== null) {
                $stockService->setStock(
                    $item->product,
                    $item->actual_stock,
                    StockMovementType::Opname,
                    $this,
                    "Stock Opname: {$this->opname_number}"
                );
            }
        }

        $this->update(['status' => OpnameStatus::Completed]);
    }

    public function getTotalDifference(): int
    {
        return $this->items()->sum('difference');
    }

    public function getItemsWithDifference()
    {
        return $this->items()->where('difference', '!=', 0)->get();
    }
}
