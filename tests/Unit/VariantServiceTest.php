<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\VariantService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses()->group('variant');

uses(RefreshDatabase::class);

beforeEach(function () {
    Store::factory()->create();
});

it('can get available variants for a product', function () {
    $product = Product::factory()->create();
    ProductVariant::factory()->count(3)->create([
        'product_id' => $product->id,
        'is_active' => true,
        'stock' => 10,
    ]);
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'is_active' => true,
        'stock' => 0, // out of stock
    ]);

    $variants = VariantService::getAvailableVariants($product->id);

    expect($variants)->toHaveCount(3);
});

it('can get variant by SKU', function () {
    $variant = ProductVariant::factory()->create([
        'sku' => 'TEST-001',
        'is_active' => true,
    ]);

    $found = VariantService::getVariantBySku('TEST-001');
    $notFound = VariantService::getVariantBySku('NONEXISTENT');

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($variant->id)
        ->and($notFound)->toBeNull();
});

it('can check if product has variants', function () {
    $productWithVariants = Product::factory()->create();
    ProductVariant::factory()->create(['product_id' => $productWithVariants->id]);

    $productWithoutVariants = Product::factory()->create();

    expect(VariantService::hasVariants($productWithVariants->id))->toBeTrue()
        ->and(VariantService::hasVariants($productWithoutVariants->id))->toBeFalse();
});

it('can check stock availability', function () {
    $variant = ProductVariant::factory()->create(['stock' => 10]);

    expect(VariantService::checkStock($variant->id, 5))->toBeTrue()
        ->and(VariantService::checkStock($variant->id, 10))->toBeTrue()
        ->and(VariantService::checkStock($variant->id, 15))->toBeFalse()
        ->and(VariantService::checkStock(99999, 1))->toBeFalse();
});

it('can decrement variant stock', function () {
    $variant = ProductVariant::factory()->create(['stock' => 10]);

    $result = VariantService::decrementStock($variant->id, 3);
    $variant->refresh();

    expect($result)->toBeTrue()
        ->and($variant->stock)->toBe(7);
});

it('cannot decrement stock below zero', function () {
    $variant = ProductVariant::factory()->create(['stock' => 5]);

    $result = VariantService::decrementStock($variant->id, 10);
    $variant->refresh();

    expect($result)->toBeFalse()
        ->and($variant->stock)->toBe(5);
});

it('can increment variant stock', function () {
    $variant = ProductVariant::factory()->create(['stock' => 10]);

    $result = VariantService::incrementStock($variant->id, 5);
    $variant->refresh();

    expect($result)->toBeTrue()
        ->and($variant->stock)->toBe(15);
});

it('can get selling and purchase prices', function () {
    $variant = ProductVariant::factory()->create([
        'selling_price' => 15000,
        'purchase_price' => 10000,
    ]);

    expect(VariantService::getSellingPrice($variant->id))->toBe(15000.0)
        ->and(VariantService::getPurchasePrice($variant->id))->toBe(10000.0)
        ->and(VariantService::getSellingPrice(99999))->toBeNull();
});

it('can identify low stock variants', function () {
    ProductVariant::factory()->count(3)->create([
        'stock' => 5,
        'low_stock_threshold' => 10,
    ]);
    ProductVariant::factory()->count(2)->create([
        'stock' => 20,
        'low_stock_threshold' => 10,
    ]);

    $lowStock = VariantService::getLowStockVariants();

    expect($lowStock)->toHaveCount(3);
});

it('can calculate variant stock value', function () {
    $variant = ProductVariant::factory()->create([
        'stock' => 10,
        'purchase_price' => 5000,
    ]);

    $value = VariantService::getVariantStockValue($variant->id);

    expect($value)->toBe(50000.0);
});

it('can create and update variant', function () {
    $product = Product::factory()->create();

    $variant = VariantService::createVariant([
        'product_id' => $product->id,
        'variant_name' => 'Test Variant',
        'sku' => 'TEST-SKU-001',
        'purchase_price' => 10000,
        'selling_price' => 15000,
        'stock' => 50,
    ]);

    expect($variant)->toBeInstanceOf(ProductVariant::class)
        ->and($variant->variant_name)->toBe('Test Variant');

    $updated = VariantService::updateVariant($variant->id, [
        'stock' => 75,
        'selling_price' => 16000,
    ]);

    expect($updated->stock)->toBe(75)
        ->and((float) $updated->selling_price)->toBe(16000.0);
});
