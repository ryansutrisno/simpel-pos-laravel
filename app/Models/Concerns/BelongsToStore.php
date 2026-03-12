<?php

namespace App\Models\Concerns;

use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToStore
{
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function scopeForStore(Builder $query, int|Store $store): Builder
    {
        $storeId = $store instanceof Store ? $store->id : $store;

        return $query->where('store_id', $storeId);
    }

    public function scopeForCurrentStore(Builder $query): Builder
    {
        $storeId = $this->getCurrentStoreId();

        if (! $storeId) {
            return $query;
        }

        return $query->where('store_id', $storeId);
    }

    protected function getCurrentStoreId(): ?int
    {
        if (auth()->check() && auth()->user()->current_store_id) {
            return auth()->user()->current_store_id;
        }

        return session('current_store_id');
    }

    protected static function bootBelongsToStore(): void
    {
        static::creating(function ($model) {
            if (empty($model->store_id)) {
                $storeId = $model->getCurrentStoreId();
                if ($storeId) {
                    $model->store_id = $storeId;
                }
            }
        });
    }
}
