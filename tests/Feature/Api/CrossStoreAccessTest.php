<?php

use App\Enums\RefundMethod;
use App\Enums\ReturnReason;
use App\Enums\ReturnType;
use App\Models\ProductReturn;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\ShieldSeeder;

beforeEach(function () {
    $this->seed(ShieldSeeder::class);

    $this->storeA = Store::factory()->create();
    $this->storeB = Store::factory()->create();

    $this->cashier = User::factory()->create();
    $this->cashier->assignRole('kasir');
    $this->cashier->stores()->attach($this->storeA->id);
});

it('forbids a cashier from reading a transaction in another store', function () {
    $transaction = Transaction::factory()->create([
        'store_id' => $this->storeB->id,
    ]);

    $response = $this->actingAs($this->cashier)
        ->getJson("/api/transactions/{$transaction->id}");

    $response->assertForbidden();
});

it('allows a cashier to read a transaction in their assigned store', function () {
    $transaction = Transaction::factory()->create([
        'store_id' => $this->storeA->id,
    ]);

    $response = $this->actingAs($this->cashier)
        ->getJson("/api/transactions/{$transaction->id}");

    $response->assertSuccessful();
});

it('forbids a cashier from reading a return in another store', function () {
    $productReturn = createReturnForStore($this->storeB);

    $response = $this->actingAs($this->cashier)
        ->getJson("/api/returns/{$productReturn->id}");

    $response->assertForbidden();
});

it('allows a cashier to read a return in their assigned store', function () {
    $productReturn = createReturnForStore($this->storeA);

    $response = $this->actingAs($this->cashier)
        ->getJson("/api/returns/{$productReturn->id}");

    $response->assertSuccessful();
});

it('allows a super admin to read a transaction from any store', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');
    $transaction = Transaction::factory()->create([
        'store_id' => $this->storeB->id,
    ]);

    $response = $this->actingAs($superAdmin)
        ->getJson("/api/transactions/{$transaction->id}");

    $response->assertSuccessful();
});

it('allows a super admin to read a return from any store', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');
    $productReturn = createReturnForStore($this->storeB);

    $response = $this->actingAs($superAdmin)
        ->getJson("/api/returns/{$productReturn->id}");

    $response->assertSuccessful();
});

function createReturnForStore(Store $store): ProductReturn
{
    $transaction = Transaction::factory()->create([
        'store_id' => $store->id,
    ]);

    return ProductReturn::create([
        'return_number' => 'RTN-'.now()->format('YmdHis').'-'.fake()->unique()->numerify('####'),
        'transaction_id' => $transaction->id,
        'user_id' => $transaction->user_id,
        'type' => ReturnType::Partial,
        'reason_category' => ReturnReason::Damaged,
        'refund_method' => RefundMethod::Cash,
        'total_refund' => 10000,
        'total_exchange_value' => 0,
        'selisih_amount' => -10000,
        'return_date' => now(),
    ]);
}
