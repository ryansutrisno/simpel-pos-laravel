<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class StoreScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $storeId = $this->getCurrentStoreId();

        if ($storeId) {
            $builder->where($model->getTable().'.store_id', $storeId);
        }
    }

    protected function getCurrentStoreId(): ?int
    {
        if (auth()->check() && auth()->user()->current_store_id) {
            return auth()->user()->current_store_id;
        }

        return session('current_store_id');
    }
}
