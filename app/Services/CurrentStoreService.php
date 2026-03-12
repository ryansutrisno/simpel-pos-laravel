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

        if (auth()->check() && ! $this->canAccessStore($storeId)) {
            return;
        }

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

    public function isAdmin(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function canAccessAllStores(): bool
    {
        return $this->isSuperAdmin() || $this->isAdmin();
    }

    public function canAccessStore(int $storeId): bool
    {
        if (! auth()->check()) {
            return false;
        }

        return auth()->user()->canAccessStore($storeId);
    }

    public function getAvailableStores()
    {
        if (! auth()->check()) {
            return collect();
        }

        return auth()->user()->assignedStores();
    }

    public function getFirstAvailableStore(): ?Store
    {
        $stores = $this->getAvailableStores();

        return $stores->first();
    }

    public function hasAnyStore(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        return auth()->user()->hasAnyStore();
    }

    public function initializeForUser(): void
    {
        if (! auth()->check() || $this->hasStore()) {
            return;
        }

        $store = $this->getFirstAvailableStore();

        if ($store) {
            $this->set($store);
        }
    }
}
