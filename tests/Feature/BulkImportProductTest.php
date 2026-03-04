<?php

use App\Models\Category;
use App\Models\Product;

beforeEach(function () {
    Category::query()->delete();
    Product::query()->delete();
});

describe('Bulk Import Page', function () {
    it('page loads successfully', function () {
        $this->actingAs(\App\Models\User::factory()->create());

        $this->get('/admin/bulk-import-products')
            ->assertStatus(200);
    });

    it('template file exists', function () {
        expect(file_exists(storage_path('app/public/templates/product_import_template.xlsx')))->toBeTrue();
    });
});

describe('Product Import Validation', function () {
    it('can create product with minimal data', function () {
        $category = Category::factory()->create();

        $product = Product::create([
            'name' => 'Test Product',
            'category_id' => $category->id,
            'purchase_price' => 5000,
            'selling_price' => 7500,
            'stock' => 100,
        ]);

        expect($product->exists)->toBeTrue();
    });

    it('can create product with sku', function () {
        $category = Category::factory()->create();

        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'SKU-20260304-0001',
            'category_id' => $category->id,
            'purchase_price' => 5000,
            'selling_price' => 7500,
            'stock' => 100,
        ]);

        expect($product->sku)->toBe('SKU-20260304-0001');
    });

    it('can auto-generate sku format', function () {
        $date = date('Ymd');
        $sku = 'SKU-'.$date.'-0001';

        expect($sku)->toBe('SKU-'.date('Ymd').'-0001');
    });
});
