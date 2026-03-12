<?php

use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $store = Store::first();

        if (! $store) {
            return;
        }

        $products = Product::where('stock', '>', 0)->get();

        foreach ($products as $product) {
            DB::table('product_stocks')->insert([
                'product_id' => $product->id,
                'store_id' => $store->id,
                'variant_id' => null,
                'quantity' => $product->stock,
                'min_stock' => $product->low_stock_threshold ?? 0,
                'max_stock' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('product_stocks')->truncate();
    }
};
