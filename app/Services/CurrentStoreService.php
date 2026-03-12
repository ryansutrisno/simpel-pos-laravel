<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Session;

class CurrentStoreService
{
    protected const SESSION_KEY = 'current_store_id';

    public function get(): ?Store
    {
        $storeId = $this->getId();

        if (! $storeId) {
            return null;
        }

        return Store::find($storeId);
    }

    public function getId(): ?int
    {
        if (auth()->check() && auth()->user()->current_store_id) {
            return auth()->user()->current_store_id;
        }

        return Session::get(self::SESSION_KEY);
    }

    public function set(Store|int $store): void
    {
        $storeId = $store instanceof Store ? $store->id : $store;

        Session::put(self::SESSION_KEY, $storeId);

        if (auth()->check()) {
            auth()->user()->update(['current_store_id' => $storeId]);
        }
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function hasStore(): bool
    {
        return $this->getId() !== null;
    }

    public function isSuperAdmin(): bool
    {
        return auth()->check() && auth()->user()->isSuperAdmin();
    }

    public function canAccessAllStores(): bool
    {
        return $this->isSuperAdmin();
    }
}
