<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;

uses()->group('variant');

beforeEach(function () {
    Store::factory()->create();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can create product variant', function () {
    $product = Product::factory()->create();

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'variant_name' => '1 Liter',
        'sku' => 'TEST-001',
        'purchase_price' => 10000,
        'selling_price' => 15000,
        'stock' => 50,
        'low_stock_threshold' => 10,
    ]);

    expect($variant)->toBeInstanceOf(ProductVariant::class)
        ->and($variant->variant_name)->toBe('1 Liter')
        ->and($variant->sku)->toBe('TEST-001');
});

it('variant belongs to product', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    expect($variant->product)->toBeInstanceOf(Product::class)
        ->and($variant->product->id)->toBe($product->id);
});

it('variant has many transaction items', function () {
    $variant = ProductVariant::factory()->create();
    $transaction = Transaction::factory()->create();

    $transaction->items()->create([
        'product_id' => $variant->product_id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'purchase_price' => $variant->purchase_price,
        'selling_price' => $variant->selling_price,
        'profit' => 5000,
        'subtotal' => 30000,
    ]);

    expect($variant->transactionItems)->toHaveCount(1)
        ->and($variant->transactionItems->first()->quantity)->toBe(2);
});

it('can identify low stock variants', function () {
    ProductVariant::factory()->create([
        'stock' => 5,
        'low_stock_threshold' => 10,
    ]);
    ProductVariant::factory()->create([
        'stock' => 20,
        'low_stock_threshold' => 10,
    ]);

    $lowStock = ProductVariant::lowStock()->get();

    expect($lowStock)->toHaveCount(1)
        ->and($lowStock->first()->stock)->toBe(5);
});

it('can identify out of stock variants', function () {
    ProductVariant::factory()->create(['stock' => 0]);
    ProductVariant::factory()->create(['stock' => 10]);

    $outOfStock = ProductVariant::where('stock', '<=', 0)->get();

    expect($outOfStock)->toHaveCount(1)
        ->and($outOfStock->first()->stock)->toBe(0);
});

it('product can have multiple variants', function () {
    $product = Product::factory()->create();
    ProductVariant::factory()->count(3)->create(['product_id' => $product->id]);

    expect($product->variants)->toHaveCount(3)
        ->and($product->hasVariants())->toBeTrue();
});

it('product knows if it has no variants', function () {
    $product = Product::factory()->create();

    expect($product->hasVariants())->toBeFalse();
});

it('product has active variants scope', function () {
    $product = Product::factory()->create();
    ProductVariant::factory()->count(2)->create([
        'product_id' => $product->id,
        'is_active' => true,
    ]);
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'is_active' => false,
    ]);

    expect($product->activeVariants)->toHaveCount(2);
});

it('variant has isLowStock method', function () {
    $lowStock = ProductVariant::factory()->create([
        'stock' => 5,
        'low_stock_threshold' => 10,
    ]);
    $normalStock = ProductVariant::factory()->create([
        'stock' => 20,
        'low_stock_threshold' => 10,
    ]);

    expect($lowStock->isLowStock())->toBeTrue()
        ->and($normalStock->isLowStock())->toBeFalse();
});

it('variant has isOutOfStock method', function () {
    $outOfStock = ProductVariant::factory()->create(['stock' => 0]);
    $inStock = ProductVariant::factory()->create(['stock' => 10]);

    expect($outOfStock->isOutOfStock())->toBeTrue()
        ->and($inStock->isOutOfStock())->toBeFalse();
});
