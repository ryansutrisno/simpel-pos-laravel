<?php

namespace Database\Seeders;

use App\Models\BundleItem;
use App\Models\Product;
use App\Models\ProductBundle;
use App\Models\ProductVariant;
use App\Models\ReorderAlert;
use Illuminate\Database\Seeder;

class VariantBundleDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating demo products with variants...');

        // 1. Create products with variants
        $productsWithVariants = [
            [
                'name' => 'Minyak Goreng Bimoli',
                'category' => 'Sembako',
                'variants' => [
                    ['name' => '500ml', 'sku' => 'BIM-500', 'purchase' => 8500, 'selling' => 10500, 'stock' => 50],
                    ['name' => '1 Liter', 'sku' => 'BIM-1L', 'purchase' => 15000, 'selling' => 18500, 'stock' => 40],
                    ['name' => '2 Liter', 'sku' => 'BIM-2L', 'purchase' => 28000, 'selling' => 34000, 'stock' => 25],
                    ['name' => '5 Liter', 'sku' => 'BIM-5L', 'purchase' => 65000, 'selling' => 78000, 'stock' => 15],
                ],
            ],
            [
                'name' => 'Beras Jasmine',
                'category' => 'Sembako',
                'variants' => [
                    ['name' => '2kg', 'sku' => 'JAS-2K', 'purchase' => 28000, 'selling' => 32000, 'stock' => 30],
                    ['name' => '5kg', 'sku' => 'JAS-5K', 'purchase' => 65000, 'selling' => 72000, 'stock' => 20],
                    ['name' => '10kg', 'sku' => 'JAS-10K', 'purchase' => 125000, 'selling' => 138000, 'stock' => 12],
                    ['name' => '25kg', 'sku' => 'JAS-25K', 'purchase' => 300000, 'selling' => 325000, 'stock' => 8],
                ],
            ],
            [
                'name' => 'Susu UHT Indomilk',
                'category' => 'Minuman',
                'variants' => [
                    ['name' => '125ml', 'sku' => 'IND-125', 'purchase' => 2500, 'selling' => 3500, 'stock' => 100],
                    ['name' => '200ml', 'sku' => 'IND-200', 'purchase' => 3500, 'selling' => 5000, 'stock' => 80],
                    ['name' => '1 Liter', 'sku' => 'IND-1L', 'purchase' => 12000, 'selling' => 15500, 'stock' => 45],
                ],
            ],
            [
                'name' => 'Shampoo Clear',
                'category' => 'Perawatan',
                'variants' => [
                    ['name' => 'Sachet', 'sku' => 'CLR-SAC', 'purchase' => 800, 'selling' => 1200, 'stock' => 200],
                    ['name' => '170ml', 'sku' => 'CLR-170', 'purchase' => 15000, 'selling' => 19500, 'stock' => 35],
                    ['name' => '340ml', 'sku' => 'CLR-340', 'purchase' => 28000, 'selling' => 35000, 'stock' => 20],
                ],
            ],
            [
                'name' => 'Kopi Kapal Api',
                'category' => 'Minuman',
                'variants' => [
                    ['name' => 'Sachet', 'sku' => 'KPL-SAC', 'purchase' => 1000, 'selling' => 1500, 'stock' => 150],
                    ['name' => '165g', 'sku' => 'KPL-165', 'purchase' => 12000, 'selling' => 15000, 'stock' => 40],
                    ['name' => '380g', 'sku' => 'KPL-380', 'purchase' => 25000, 'selling' => 31000, 'stock' => 25],
                ],
            ],
            [
                'name' => 'Gula Pasir Gulaku',
                'category' => 'Sembako',
                'variants' => [
                    ['name' => '250g', 'sku' => 'GLK-250', 'purchase' => 4500, 'selling' => 5500, 'stock' => 60],
                    ['name' => '1kg', 'sku' => 'GLK-1K', 'purchase' => 14500, 'selling' => 16500, 'stock' => 35],
                    ['name' => '2kg', 'sku' => 'GLK-2K', 'purchase' => 28000, 'selling' => 31000, 'stock' => 20],
                    ['name' => '5kg', 'sku' => 'GLK-5K', 'purchase' => 68000, 'selling' => 73000, 'stock' => 10],
                ],
            ],
        ];

        foreach ($productsWithVariants as $productData) {
            $category = \App\Models\Category::firstOrCreate(
                ['name' => $productData['category']],
                ['description' => 'Kategori '.$productData['category']]
            );

            $product = Product::create([
                'name' => $productData['name'],
                'category_id' => $category->id,
                'description' => fake()->sentence(),
                'purchase_price' => 0,
                'selling_price' => 0,
                'stock' => 0,
                'barcode' => fake()->ean13(),
                'is_active' => true,
                'reorder_point' => 10,
                'reorder_quantity' => 50,
            ]);

            foreach ($productData['variants'] as $variantData) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'variant_name' => $variantData['name'],
                    'sku' => $variantData['sku'],
                    'purchase_price' => $variantData['purchase'],
                    'selling_price' => $variantData['selling'],
                    'stock' => $variantData['stock'],
                    'low_stock_threshold' => max(5, (int) ($variantData['stock'] * 0.2)),
                    'is_active' => true,
                ]);
            }

            $this->command->info("Created: {$product->name} with ".count($productData['variants']).' variants');
        }

        // 2. Create some low stock variants for reorder alert demo
        $this->command->info('Creating low stock items for reorder alerts...');

        $lowStockVariants = ProductVariant::inRandomOrder()->take(5)->get();
        foreach ($lowStockVariants as $variant) {
            $variant->update(['stock' => 3, 'low_stock_threshold' => 10]);
            ReorderAlert::create([
                'product_id' => $variant->product_id,
                'variant_id' => $variant->id,
                'current_stock' => 3,
                'reorder_point' => 10,
                'status' => 'pending',
                'notified_at' => now(),
            ]);
        }

        // 3. Create bundles
        $this->command->info('Creating product bundles...');

        // Bundle 1: Beli 2 Gratis 1
        $bundle1 = ProductBundle::create([
            'name' => 'Beli 2 Susu 1L, Gratis 1',
            'description' => 'Pembelian 2 Susu UHT Indomilk 1 Liter, gratis 1 pcs',
            'discount_type' => 'free_item',
            'discount_value' => 0,
            'start_date' => now()->subWeek(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
            'min_quantity' => 2,
            'priority' => 10,
        ]);

        $susuVariant = ProductVariant::where('sku', 'IND-1L')->first();
        if ($susuVariant) {
            BundleItem::create([
                'bundle_id' => $bundle1->id,
                'product_id' => $susuVariant->product_id,
                'variant_id' => $susuVariant->id,
                'quantity' => 2,
                'is_free' => false,
            ]);
            BundleItem::create([
                'bundle_id' => $bundle1->id,
                'product_id' => $susuVariant->product_id,
                'variant_id' => $susuVariant->id,
                'quantity' => 1,
                'is_free' => true,
            ]);
        }

        // Bundle 2: Diskon 15% Minyak
        $bundle2 = ProductBundle::create([
            'name' => 'Hemat Minyak 2L',
            'description' => 'Diskon 15% untuk pembelian Minyak Goreng Bimoli 2 Liter',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'start_date' => now()->subWeek(),
            'end_date' => now()->addWeeks(2),
            'is_active' => true,
            'min_quantity' => 1,
            'priority' => 5,
        ]);

        $minyakVariant = ProductVariant::where('sku', 'BIM-2L')->first();
        if ($minyakVariant) {
            BundleItem::create([
                'bundle_id' => $bundle2->id,
                'product_id' => $minyakVariant->product_id,
                'variant_id' => $minyakVariant->id,
                'quantity' => 1,
                'is_free' => false,
            ]);
        }

        // Bundle 3: Paket Sembako
        $bundle3 = ProductBundle::create([
            'name' => 'Paket Sembako Hemat',
            'description' => 'Beli Beras 5kg + Minyak 1L, diskon Rp 5.000',
            'discount_type' => 'fixed_amount',
            'discount_value' => 5000,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
            'min_quantity' => 2,
            'priority' => 8,
        ]);

        $berasVariant = ProductVariant::where('sku', 'JAS-5K')->first();
        $minyak1L = ProductVariant::where('sku', 'BIM-1L')->first();

        if ($berasVariant && $minyak1L) {
            BundleItem::create([
                'bundle_id' => $bundle3->id,
                'product_id' => $berasVariant->product_id,
                'variant_id' => $berasVariant->id,
                'quantity' => 1,
                'is_free' => false,
            ]);
            BundleItem::create([
                'bundle_id' => $bundle3->id,
                'product_id' => $minyak1L->product_id,
                'variant_id' => $minyak1L->id,
                'quantity' => 1,
                'is_free' => false,
            ]);
        }

        $this->command->info('Created 3 product bundles');

        $this->command->info('Demo data created successfully!');
        $this->command->info('Summary:');
        $this->command->info('- Products with variants: '.count($productsWithVariants));
        $this->command->info('- Total variants: '.ProductVariant::count());
        $this->command->info('- Low stock alerts: '.ReorderAlert::count());
        $this->command->info('- Active bundles: '.ProductBundle::where('is_active', true)->count());
    }
}
