<?php

use App\Models\Product;
use App\Models\ProductBundle;
use App\Models\Store;
use App\Services\BundleService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses()->group('bundle');

uses(RefreshDatabase::class);

beforeEach(function () {
    Store::factory()->create();
});

it('can get active bundles', function () {
    ProductBundle::factory()->count(3)->create([
        'is_active' => true,
        'start_date' => now()->subWeek(),
        'end_date' => now()->addMonth(),
    ]);
    ProductBundle::factory()->create([
        'is_active' => false,
    ]);
    ProductBundle::factory()->create([
        'is_active' => true,
        'start_date' => now()->addWeek(),
        'end_date' => now()->addMonth(),
    ]);

    $activeBundles = BundleService::getActiveBundles();

    expect($activeBundles)->toHaveCount(3);
});

it('can check applicable bundles for cart items', function () {
    $product1 = Product::factory()->create(['selling_price' => 10000]);
    $product2 = Product::factory()->create(['selling_price' => 20000]);

    $bundle = ProductBundle::factory()->create([
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'min_quantity' => 2,
        'is_active' => true,
        'start_date' => now()->subWeek(),
    ]);

    \App\Models\BundleItem::create([
        'bundle_id' => $bundle->id,
        'product_id' => $product1->id,
        'quantity' => 1,
        'is_free' => false,
    ]);

    $cartItems = [
        ['product_id' => $product1->id, 'variant_id' => null, 'quantity' => 2, 'selling_price' => 10000],
    ];

    $applicableBundles = BundleService::checkApplicableBundles($cartItems);

    expect($applicableBundles)->toHaveCount(1)
        ->and($applicableBundles[0]['bundle']->id)->toBe($bundle->id);
});

it('can apply best bundle with highest discount', function () {
    $product = Product::factory()->create(['selling_price' => 10000]);

    // Bundle 1: 10% discount
    $bundle1 = ProductBundle::factory()->create([
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'priority' => 1,
        'is_active' => true,
        'start_date' => now()->subWeek(),
    ]);
    \App\Models\BundleItem::create([
        'bundle_id' => $bundle1->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'is_free' => false,
    ]);

    // Bundle 2: 20% discount (better)
    $bundle2 = ProductBundle::factory()->create([
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'priority' => 5,
        'is_active' => true,
        'start_date' => now()->subWeek(),
    ]);
    \App\Models\BundleItem::create([
        'bundle_id' => $bundle2->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'is_free' => false,
    ]);

    $cartItems = [
        ['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1, 'selling_price' => 10000],
    ];

    $bestBundle = BundleService::getBestBundle($cartItems);

    expect($bestBundle['bundle']->id)->toBe($bundle2->id)
        ->and($bestBundle['total_discount'])->toBeGreaterThan(0);
});

it('can calculate free item bundle', function () {
    $product = Product::factory()->create(['selling_price' => 10000]);

    $bundle = ProductBundle::factory()->create([
        'discount_type' => 'free_item',
        'discount_value' => 0,
        'min_quantity' => 2,
        'is_active' => true,
        'start_date' => now()->subWeek(),
    ]);
    \App\Models\BundleItem::create([
        'bundle_id' => $bundle->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'is_free' => false,
    ]);
    \App\Models\BundleItem::create([
        'bundle_id' => $bundle->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'is_free' => true,
    ]);

    $cartItems = [
        ['product_id' => $product->id, 'quantity' => 2, 'selling_price' => 10000],
    ];

    $result = BundleService::calculateBundleDiscount($bundle->id, $cartItems);

    expect($result['applicable'])->toBeTrue()
        ->and($result['free_items'])->toHaveCount(1);
});

it('can create and update bundle', function () {
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();

    $bundle = BundleService::createBundle(
        [
            'name' => 'Test Bundle',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'start_date' => now(),
            'is_active' => true,
            'min_quantity' => 2,
        ],
        [
            ['product_id' => $product1->id, 'quantity' => 1, 'is_free' => false],
            ['product_id' => $product2->id, 'quantity' => 1, 'is_free' => false],
        ]
    );

    expect($bundle)->toBeInstanceOf(ProductBundle::class)
        ->and($bundle->items)->toHaveCount(2);

    $updated = BundleService::updateBundle(
        $bundle->id,
        ['name' => 'Updated Bundle'],
        [
            ['product_id' => $product1->id, 'quantity' => 2, 'is_free' => false],
        ]
    );

    expect($updated->name)->toBe('Updated Bundle')
        ->and($updated->items)->toHaveCount(1);
});

it('returns not applicable for invalid bundle', function () {
    $product = Product::factory()->create();

    $bundle = ProductBundle::factory()->create([
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'min_quantity' => 5, // Requires 5 items
        'is_active' => true,
        'start_date' => now()->subWeek(),
    ]);
    \App\Models\BundleItem::create([
        'bundle_id' => $bundle->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'is_free' => false,
    ]);

    $cartItems = [
        ['product_id' => $product->id, 'quantity' => 2, 'selling_price' => 10000], // Only 2 items
    ];

    $result = BundleService::calculateBundleDiscount($bundle->id, $cartItems);

    expect($result['applicable'])->toBeFalse()
        ->and($result['total_discount'])->toBe(0);
});
