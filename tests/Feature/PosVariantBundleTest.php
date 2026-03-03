<?php

use App\Models\Product;
use App\Models\ProductBundle;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;

uses()->group('pos');

beforeEach(function () {
    Store::factory()->create();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can add product with variant to cart', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'variant_name' => '1 Liter',
        'selling_price' => 15000,
        'stock' => 50,
    ]);

    $component = Livewire::test(\App\Livewire\Pos::class)
        ->call('addToCart', $product->id, $variant->id);

    $cart = $component->get('cart');

    expect($cart)->toHaveCount(1)
        ->and($cart[0]['variant_id'])->toBe($variant->id)
        ->and($cart[0]['name'])->toContain('1 Liter')
        ->and((float) $cart[0]['selling_price'])->toBe(15000.0);
});

it('shows variant modal when adding product with variants', function () {
    $product = Product::factory()->create();
    ProductVariant::factory()->create(['product_id' => $product->id]);

    $component = Livewire::test(\App\Livewire\Pos::class)
        ->call('addToCart', $product->id);

    expect($component->get('showVariantModal'))->toBeTrue()
        ->and($component->get('selectedProductForVariant')->id)->toBe($product->id);
});

it('can select variant from modal', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'stock' => 50,
    ]);

    $component = Livewire::test(\App\Livewire\Pos::class)
        ->set('selectedProductForVariant', $product)
        ->call('selectVariant', $variant->id);

    $cart = $component->get('cart');

    expect($cart)->toHaveCount(1)
        ->and($cart[0]['variant_id'])->toBe($variant->id)
        ->and($component->get('showVariantModal'))->toBeFalse();
});

it('decrements variant stock on checkout', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'stock' => 50,
        'selling_price' => 15000,
    ]);

    Livewire::test(\App\Livewire\Pos::class)
        ->call('addToCart', $product->id, $variant->id)
        ->set('paymentMethod', 'cash')
        ->set('cashAmount', 50000)
        ->call('checkout');

    $variant->refresh();

    expect($variant->stock)->toBe(49);
});

it('applies bundle discount when conditions met', function () {
    $product = Product::factory()->create(['selling_price' => 10000]);
    $bundle = ProductBundle::factory()->create([
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'min_quantity' => 1,
        'is_active' => true,
        'start_date' => now()->subWeek(),
    ]);
    \App\Models\BundleItem::create([
        'bundle_id' => $bundle->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'is_free' => false,
    ]);

    $component = Livewire::test(\App\Livewire\Pos::class)
        ->call('addToCart', $product->id);

    // Check cart was updated
    $cart = $component->get('cart');
    expect($cart)->toHaveCount(1);
});

it('calculates correct total with bundle discount', function () {
    $product = Product::factory()->create(['selling_price' => 10000]);
    $bundle = ProductBundle::factory()->create([
        'discount_type' => 'fixed_amount',
        'discount_value' => 2000,
        'min_quantity' => 1,
        'is_active' => true,
        'start_date' => now()->subWeek(),
    ]);
    \App\Models\BundleItem::create([
        'bundle_id' => $bundle->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'is_free' => false,
    ]);

    $component = Livewire::test(\App\Livewire\Pos::class)
        ->call('addToCart', $product->id);

    // Cart should have item
    $cart = $component->get('cart');
    expect($cart)->toHaveCount(1)
        ->and((float) $cart[0]['selling_price'])->toBe(10000.0);
});

it('can process barcode with variant SKU', function () {
    $variant = ProductVariant::factory()->create([
        'sku' => 'TEST-SKU-123',
        'stock' => 50,
    ]);

    $component = Livewire::test(\App\Livewire\Pos::class)
        ->call('processBarcode', 'TEST-SKU-123');

    $cart = $component->get('cart');

    expect($cart)->toHaveCount(1)
        ->and($cart[0]['variant_id'])->toBe($variant->id);
});

it('saves variant_id in transaction item', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'stock' => 50,
        'selling_price' => 15000,
    ]);

    Livewire::test(\App\Livewire\Pos::class)
        ->call('addToCart', $product->id, $variant->id)
        ->set('paymentMethod', 'cash')
        ->set('cashAmount', 50000)
        ->call('checkout');

    $transaction = Transaction::latest()->first();

    expect($transaction->items->first()->variant_id)->toBe($variant->id);
});

it('cannot add variant with zero stock', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'stock' => 0,
    ]);

    $component = Livewire::test(\App\Livewire\Pos::class)
        ->call('addToCart', $product->id, $variant->id);

    $cart = $component->get('cart');

    expect($cart)->toHaveCount(0);
});
