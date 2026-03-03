<?php

use App\Models\Product;

it('product has correct fillable attributes', function () {
    $product = new Product;

    expect($product->getFillable())->toEqual([
        'name',
        'category_id',
        'description',
        'purchase_price',
        'selling_price',
        'stock',
        'low_stock_threshold',
        'reorder_point',
        'reorder_quantity',
        'barcode',
        'image',
        'is_active',
        'is_returnable',
    ]);
});

it('product has correct casts', function () {
    $product = new Product;
    $casts = $product->getCasts();

    expect($casts['is_active'])->toBe('boolean');
    expect($casts['is_returnable'])->toBe('boolean');
    expect($casts['purchase_price'])->toBe('decimal:2');
    expect($casts['selling_price'])->toBe('decimal:2');
    expect($casts['low_stock_threshold'])->toBe('integer');
});

it('product uses soft deletes trait', function () {
    $traits = class_uses(Product::class);

    expect($traits)->toHaveKey('Illuminate\\Database\\Eloquent\\SoftDeletes');
});
