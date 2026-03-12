<?php

use App\Models\Store;
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

    $this->store3 = Store::create([
        'name' => 'Store 3',
        'address' => 'Address 3',
        'phone' => '0833333333',
    ]);

    $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
    $kasirRole = Role::firstOrCreate(['name' => 'kasir', 'guard_name' => 'web']);

    $this->superAdmin = User::factory()->create(['current_store_id' => $this->store1->id]);
    $this->superAdmin->assignRole($superAdminRole);

    $this->admin = User::factory()->create(['current_store_id' => $this->store1->id]);
    $this->admin->assignRole($adminRole);

    $this->manager = User::factory()->create(['current_store_id' => $this->store1->id]);
    $this->manager->assignRole($managerRole);

    $this->kasir = User::factory()->create(['current_store_id' => $this->store1->id]);
    $this->kasir->assignRole($kasirRole);
});

describe('User Store Assignment', function () {
    it('super admin can access all stores', function () {
        $stores = $this->superAdmin->assignedStores();

        expect($stores)->toHaveCount(3);
        expect($stores->pluck('id')->toArray())->toContain($this->store1->id, $this->store2->id, $this->store3->id);
    });

    it('admin can access all stores', function () {
        $stores = $this->admin->assignedStores();

        expect($stores)->toHaveCount(3);
        expect($stores->pluck('id')->toArray())->toContain($this->store1->id, $this->store2->id, $this->store3->id);
    });

    it('manager can only access assigned stores', function () {
        $this->manager->stores()->attach([$this->store1->id, $this->store2->id]);

        $stores = $this->manager->assignedStores();

        expect($stores)->toHaveCount(2);
        expect($stores->pluck('id')->toArray())->toContain($this->store1->id, $this->store2->id);
        expect($stores->pluck('id')->toArray())->not->toContain($this->store3->id);
    });

    it('kasir can only access assigned stores', function () {
        $this->kasir->stores()->attach([$this->store1->id]);

        $stores = $this->kasir->assignedStores();

        expect($stores)->toHaveCount(1);
        expect($stores->first()->id)->toBe($this->store1->id);
    });

    it('user with no store assignments has empty stores', function () {
        $stores = $this->manager->assignedStores();

        expect($stores)->toHaveCount(0);
    });
});

describe('User canAccessStore', function () {
    it('super admin can access any store', function () {
        expect($this->superAdmin->canAccessStore($this->store1->id))->toBeTrue();
        expect($this->superAdmin->canAccessStore($this->store2->id))->toBeTrue();
        expect($this->superAdmin->canAccessStore($this->store3->id))->toBeTrue();
    });

    it('admin can access any store', function () {
        expect($this->admin->canAccessStore($this->store1->id))->toBeTrue();
        expect($this->admin->canAccessStore($this->store2->id))->toBeTrue();
        expect($this->admin->canAccessStore($this->store3->id))->toBeTrue();
    });

    it('manager can only access assigned stores', function () {
        $this->manager->stores()->attach([$this->store1->id, $this->store2->id]);

        expect($this->manager->canAccessStore($this->store1->id))->toBeTrue();
        expect($this->manager->canAccessStore($this->store2->id))->toBeTrue();
        expect($this->manager->canAccessStore($this->store3->id))->toBeFalse();
    });

    it('kasir can only access assigned stores', function () {
        $this->kasir->stores()->attach([$this->store1->id]);

        expect($this->kasir->canAccessStore($this->store1->id))->toBeTrue();
        expect($this->kasir->canAccessStore($this->store2->id))->toBeFalse();
    });
});

describe('CurrentStoreService Multi-Store', function () {
    it('returns all stores for super admin', function () {
        $this->actingAs($this->superAdmin);

        $service = app(CurrentStoreService::class);
        $stores = $service->getAvailableStores();

        expect($stores)->toHaveCount(3);
    });

    it('returns all stores for admin', function () {
        $this->actingAs($this->admin);

        $service = app(CurrentStoreService::class);
        $stores = $service->getAvailableStores();

        expect($stores)->toHaveCount(3);
    });

    it('returns only assigned stores for manager', function () {
        $this->manager->stores()->attach([$this->store1->id, $this->store2->id]);
        $this->actingAs($this->manager);

        $service = app(CurrentStoreService::class);
        $stores = $service->getAvailableStores();

        expect($stores)->toHaveCount(2);
    });

    it('can set store only if user has access', function () {
        $this->manager->stores()->attach([$this->store1->id]);
        $this->actingAs($this->manager);

        $service = app(CurrentStoreService::class);

        $service->set($this->store1->id);
        expect($service->getId())->toBe($this->store1->id);

        $service->set($this->store2->id);
        expect($service->getId())->toBe($this->store1->id);
    });

    it('super admin can switch to any store', function () {
        $this->actingAs($this->superAdmin);

        $service = app(CurrentStoreService::class);

        $service->set($this->store2->id);
        expect($service->getId())->toBe($this->store2->id);

        $service->set($this->store3->id);
        expect($service->getId())->toBe($this->store3->id);
    });

    it('initializes first available store for user without current store', function () {
        $this->manager->update(['current_store_id' => null]);
        $this->manager->stores()->attach([$this->store1->id, $this->store2->id]);
        $this->actingAs($this->manager);

        $service = app(CurrentStoreService::class);
        $service->initializeForUser();

        expect($service->getId())->not->toBeNull();
        expect($this->manager->fresh()->current_store_id)->not->toBeNull();
    });
});

describe('Store User Relationship', function () {
    it('store can have multiple assigned users', function () {
        $this->manager->stores()->attach([$this->store1->id]);
        $this->kasir->stores()->attach([$this->store1->id]);

        $assignedUsers = $this->store1->assignedUsers;

        expect($assignedUsers)->toHaveCount(2);
        expect($assignedUsers->pluck('id')->toArray())->toContain($this->manager->id, $this->kasir->id);
    });

    it('store can track current store users separately', function () {
        $currentStoreUsers = $this->store1->currentStoreUsers;

        expect($currentStoreUsers)->toHaveCount(4);
        expect($currentStoreUsers->pluck('id')->toArray())->toContain(
            $this->superAdmin->id,
            $this->admin->id,
            $this->manager->id,
            $this->kasir->id
        );
    });
});

describe('User hasAnyStore', function () {
    it('returns true for super admin if stores exist', function () {
        $this->actingAs($this->superAdmin);

        expect($this->superAdmin->hasAnyStore())->toBeTrue();
    });

    it('returns true for manager with assigned stores', function () {
        $this->manager->stores()->attach([$this->store1->id]);

        expect($this->manager->hasAnyStore())->toBeTrue();
    });

    it('returns false for manager without assigned stores', function () {
        expect($this->manager->hasAnyStore())->toBeFalse();
    });
});
