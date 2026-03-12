<?php

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CurrentStoreService;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->store1 = Store::create([
        'name' => 'Store 1',
        'address' => 'Address 1',
        'phone' => '0811111111',
    ]);

    $this->store2 = Store::create([
        'name' => 'Store 2',
        'address' => 'Address 2',
        'phone' => '0822222222',
    ]);

    $this->user = User::factory()->create([
        'current_store_id' => $this->store1->id,
    ]);

    $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $this->superAdmin = User::factory()->create([
        'current_store_id' => $this->store1->id,
    ]);
    $this->superAdmin->assignRole($superAdminRole);
});

describe('User Store Assignment', function () {
    it('user has current store', function () {
        expect($this->user->current_store_id)->toBe($this->store1->id);
        expect($this->user->currentStore)->toBeInstanceOf(Store::class);
        expect($this->user->currentStore->id)->toBe($this->store1->id);
    });

    it('user can be assigned to different store', function () {
        $this->user->update(['current_store_id' => $this->store2->id]);

        expect($this->user->fresh()->current_store_id)->toBe($this->store2->id);
    });
});

describe('CurrentStoreService', function () {
    it('gets current store from user', function () {
        $this->actingAs($this->user);

        $service = app(CurrentStoreService::class);

        expect($service->getId())->toBe($this->store1->id);
        expect($service->get())->toBeInstanceOf(Store::class);
        expect($service->get()->id)->toBe($this->store1->id);
    });

    it('can set current store', function () {
        $this->actingAs($this->user);

        $service = app(CurrentStoreService::class);
        $service->set($this->store2);

        expect($service->getId())->toBe($this->store2->id);
        expect($this->user->fresh()->current_store_id)->toBe($this->store2->id);
    });

    it('super admin can access all stores', function () {
        $this->actingAs($this->superAdmin);

        $service = app(CurrentStoreService::class);

        expect($service->canAccessAllStores())->toBeTrue();
        expect($service->isSuperAdmin())->toBeTrue();
    });

    it('regular user cannot access all stores', function () {
        $this->actingAs($this->user);

        $service = app(CurrentStoreService::class);

        expect($service->canAccessAllStores())->toBeFalse();
        expect($service->isSuperAdmin())->toBeFalse();
    });
});

describe('ProductStock Multi-Store', function () {
    beforeEach(function () {
        $this->product = Product::factory()->create([
            'name' => 'Test Product',
            'stock' => 0, // Stock sekarang di product_stocks
        ]);
    });

    it('product can have different stock per store', function () {
        $stock1 = ProductStock::create([
            'product_id' => $this->product->id,
            'store_id' => $this->store1->id,
            'quantity' => 100,
            'min_stock' => 10,
        ]);

        $stock2 = ProductStock::create([
            'product_id' => $this->product->id,
            'store_id' => $this->store2->id,
            'quantity' => 50,
            'min_stock' => 10,
        ]);

        expect($this->product->getStockForStore($this->store1->id))->toBe(100.0);
        expect($this->product->getStockForStore($this->store2->id))->toBe(50.0);
    });

    it('product stock belongs to store', function () {
        $stock = ProductStock::create([
            'product_id' => $this->product->id,
            'store_id' => $this->store1->id,
            'quantity' => 100,
            'min_stock' => 10,
        ]);

        expect($stock->store)->toBeInstanceOf(Store::class);
        expect($stock->store->id)->toBe($this->store1->id);
    });

    it('can detect low stock per store', function () {
        $stock1 = ProductStock::create([
            'product_id' => $this->product->id,
            'store_id' => $this->store1->id,
            'quantity' => 5,
            'min_stock' => 10,
        ]);

        $stock2 = ProductStock::create([
            'product_id' => $this->product->id,
            'store_id' => $this->store2->id,
            'quantity' => 20,
            'min_stock' => 10,
        ]);

        expect($stock1->isLowStock())->toBeTrue();
        expect($stock2->isLowStock())->toBeFalse();
    });

    it('product can check low stock for specific store', function () {
        ProductStock::create([
            'product_id' => $this->product->id,
            'store_id' => $this->store1->id,
            'quantity' => 5,
            'min_stock' => 10,
        ]);

        ProductStock::create([
            'product_id' => $this->product->id,
            'store_id' => $this->store2->id,
            'quantity' => 20,
            'min_stock' => 10,
        ]);

        expect($this->product->isLowStockForStore($this->store1->id))->toBeTrue();
        expect($this->product->isLowStockForStore($this->store2->id))->toBeFalse();
    });
});

describe('BelongsToStore Trait', function () {
    it('auto-sets store_id on creating model', function () {
        $this->actingAs($this->user);

        $transaction = Transaction::create([
            'transaction_number' => 'TRX-001',
            'user_id' => $this->user->id,
            'total_amount' => 100000,
            'total' => 100000,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
        ]);

        expect($transaction->store_id)->toBe($this->store1->id);
    });

    it('scopeForStore filters by store', function () {
        $this->actingAs($this->user);

        $trx1 = Transaction::create([
            'transaction_number' => 'TRX-001',
            'user_id' => $this->user->id,
            'store_id' => $this->store1->id,
            'total_amount' => 100000,
            'total' => 100000,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
        ]);

        $trx2 = Transaction::create([
            'transaction_number' => 'TRX-002',
            'user_id' => $this->user->id,
            'store_id' => $this->store2->id,
            'total_amount' => 200000,
            'total' => 200000,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
        ]);

        $store1Transactions = Transaction::forStore($this->store1->id)->get();
        $store2Transactions = Transaction::forStore($this->store2->id)->get();

        expect($store1Transactions)->toHaveCount(1);
        expect($store1Transactions->first()->id)->toBe($trx1->id);

        expect($store2Transactions)->toHaveCount(1);
        expect($store2Transactions->first()->id)->toBe($trx2->id);
    });
});

describe('Store User Relationship', function () {
    it('store has many users', function () {
        $user2 = User::factory()->create([
            'current_store_id' => $this->store1->id,
        ]);

        $store1Users = $this->store1->users;

        expect($store1Users)->toHaveCount(3);
        expect($store1Users->pluck('id'))->toContain($this->user->id, $user2->id, $this->superAdmin->id);
    });

    it('store has product stocks', function () {
        $product = Product::factory()->create();

        ProductStock::create([
            'product_id' => $product->id,
            'store_id' => $this->store1->id,
            'quantity' => 100,
            'min_stock' => 10,
        ]);

        expect($this->store1->productStocks)->toHaveCount(1);
    });
});
