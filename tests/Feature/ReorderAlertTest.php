<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReorderAlert;
use App\Models\Store;
use App\Models\User;

uses()->group('reorder');

beforeEach(function () {
    Store::factory()->create();
});

it('can create reorder alert', function () {
    $product = Product::factory()->create();

    $alert = ReorderAlert::create([
        'product_id' => $product->id,
        'current_stock' => 5,
        'reorder_point' => 10,
        'status' => 'pending',
        'notified_at' => now(),
    ]);

    expect($alert)->toBeInstanceOf(ReorderAlert::class)
        ->and($alert->status)->toBe('pending')
        ->and($alert->isPending())->toBeTrue();
});

it('alert belongs to product', function () {
    $product = Product::factory()->create();
    $alert = ReorderAlert::factory()->create(['product_id' => $product->id]);

    expect($alert->product)->toBeInstanceOf(Product::class)
        ->and($alert->product->id)->toBe($product->id);
});

it('alert can belong to variant', function () {
    $variant = ProductVariant::factory()->create();
    $alert = ReorderAlert::factory()->create([
        'product_id' => $variant->product_id,
        'variant_id' => $variant->id,
    ]);

    expect($alert->variant)->toBeInstanceOf(ProductVariant::class)
        ->and($alert->variant->id)->toBe($variant->id);
});

it('alert belongs to acknowledged user', function () {
    $user = User::factory()->create();
    $alert = ReorderAlert::factory()->create([
        'acknowledged_by' => $user->id,
        'acknowledged_at' => now(),
        'status' => 'acknowledged',
    ]);

    expect($alert->acknowledgedBy)->toBeInstanceOf(User::class)
        ->and($alert->acknowledgedBy->id)->toBe($user->id);
});

it('has pending scope', function () {
    ReorderAlert::factory()->count(3)->pending()->create();
    ReorderAlert::factory()->count(2)->acknowledged()->create();

    $pending = ReorderAlert::pending()->get();

    expect($pending)->toHaveCount(3);
});

it('has acknowledged scope', function () {
    ReorderAlert::factory()->count(2)->pending()->create();
    ReorderAlert::factory()->count(4)->acknowledged()->create();

    $acknowledged = ReorderAlert::acknowledged()->get();

    expect($acknowledged)->toHaveCount(4);
});

it('has ordered scope', function () {
    ReorderAlert::factory()->count(2)->pending()->create();
    ReorderAlert::factory()->count(3)->ordered()->create();

    $ordered = ReorderAlert::ordered()->get();

    expect($ordered)->toHaveCount(3);
});

it('can identify status with methods', function () {
    $pending = ReorderAlert::factory()->pending()->create();
    $acknowledged = ReorderAlert::factory()->acknowledged()->create();
    $ordered = ReorderAlert::factory()->ordered()->create();

    expect($pending->isPending())->toBeTrue()
        ->and($acknowledged->isAcknowledged())->toBeTrue()
        ->and($ordered->isOrdered())->toBeTrue();
});

it('product has reorder alerts relationship', function () {
    $product = Product::factory()->create();
    ReorderAlert::factory()->count(3)->create(['product_id' => $product->id]);

    expect($product->reorderAlerts)->toHaveCount(3);
});

it('product has pending reorder alerts', function () {
    $product = Product::factory()->create();
    ReorderAlert::factory()->count(2)->pending()->create(['product_id' => $product->id]);
    ReorderAlert::factory()->acknowledged()->create(['product_id' => $product->id]);

    expect($product->pendingReorderAlerts)->toHaveCount(2);
});

it('product can check if it needs reorder', function () {
    $needsReorder = Product::factory()->create([
        'stock' => 5,
        'reorder_point' => 10,
    ]);
    $stockOk = Product::factory()->create([
        'stock' => 50,
        'reorder_point' => 10,
    ]);

    expect($needsReorder->needsReorder())->toBeTrue()
        ->and($stockOk->needsReorder())->toBeFalse();
});

it('variant can trigger reorder alert', function () {
    $variant = ProductVariant::factory()->create([
        'stock' => 3,
        'low_stock_threshold' => 10,
    ]);

    $alert = ReorderAlert::create([
        'product_id' => $variant->product_id,
        'variant_id' => $variant->id,
        'current_stock' => $variant->stock,
        'reorder_point' => $variant->low_stock_threshold,
        'status' => 'pending',
    ]);

    expect($alert->variant_id)->toBe($variant->id)
        ->and($alert->current_stock)->toBe(3);
});
