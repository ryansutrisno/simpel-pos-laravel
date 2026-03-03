<?php

use App\Models\BundleItem;
use App\Models\Product;
use App\Models\ProductBundle;
use App\Models\Store;

uses()->group('bundle');

beforeEach(function () {
    Store::factory()->create();
});

it('can create product bundle', function () {
    $bundle = ProductBundle::create([
        'name' => 'Beli 2 Gratis 1',
        'description' => 'Promo spesial',
        'discount_type' => 'free_item',
        'discount_value' => 0,
        'start_date' => today(),
        'end_date' => today()->addMonth(),
        'is_active' => true,
        'min_quantity' => 2,
        'priority' => 5,
    ]);

    expect($bundle)->toBeInstanceOf(ProductBundle::class)
        ->and($bundle->name)->toBe('Beli 2 Gratis 1')
        ->and($bundle->isFreeItem())->toBeTrue();
});

it('bundle has many items', function () {
    $bundle = ProductBundle::factory()->create();
    $product = Product::factory()->create();

    BundleItem::create([
        'bundle_id' => $bundle->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'is_free' => false,
    ]);

    expect($bundle->items)->toHaveCount(1)
        ->and($bundle->items->first()->quantity)->toBe(2);
});

it('can check if bundle is valid', function () {
    $validBundle = ProductBundle::factory()->create([
        'is_active' => true,
        'start_date' => today()->subWeek(),
        'end_date' => today()->addMonth(),
    ]);

    $expiredBundle = ProductBundle::factory()->create([
        'is_active' => true,
        'start_date' => today()->subMonth(),
        'end_date' => today()->subWeek(),
    ]);

    $inactiveBundle = ProductBundle::factory()->create([
        'is_active' => false,
        'start_date' => today()->subWeek(),
    ]);

    expect($validBundle->isValid())->toBeTrue()
        ->and($expiredBundle->isValid())->toBeFalse()
        ->and($inactiveBundle->isValid())->toBeFalse();
});

it('can identify discount type', function () {
    $percentage = ProductBundle::factory()->create(['discount_type' => 'percentage']);
    $fixed = ProductBundle::factory()->create(['discount_type' => 'fixed_amount']);
    $free = ProductBundle::factory()->create(['discount_type' => 'free_item']);

    expect($percentage->isPercentage())->toBeTrue()
        ->and($fixed->isFixedAmount())->toBeTrue()
        ->and($free->isFreeItem())->toBeTrue();
});

it('bundle can have valid scope', function () {
    ProductBundle::factory()->create([
        'is_active' => true,
        'start_date' => today()->subWeek(),
        'end_date' => today()->addMonth(),
    ]);
    ProductBundle::factory()->create([
        'is_active' => true,
        'start_date' => today()->addWeek(),
        'end_date' => today()->addMonth(),
    ]);

    $validBundles = ProductBundle::valid()->get();

    expect($validBundles)->toHaveCount(1);
});

it('bundle items belong to product', function () {
    $product = Product::factory()->create();
    $bundle = ProductBundle::factory()->create();

    $item = BundleItem::create([
        'bundle_id' => $bundle->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'is_free' => false,
    ]);

    expect($item->product)->toBeInstanceOf(Product::class)
        ->and($item->product->id)->toBe($product->id);
});

it('bundle items can have variant', function () {
    $variant = \App\Models\ProductVariant::factory()->create();
    $bundle = ProductBundle::factory()->create();

    $item = BundleItem::create([
        'bundle_id' => $bundle->id,
        'product_id' => $variant->product_id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'is_free' => false,
    ]);

    expect($item->variant)->toBeInstanceOf(\App\Models\ProductVariant::class)
        ->and($item->variant->id)->toBe($variant->id);
});
