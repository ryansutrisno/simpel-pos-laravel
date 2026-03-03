<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        $variantTypes = [
            'Ukuran' => ['500ml', '1 Liter', '2 Liter', '5 Liter'],
            'Berat' => ['250g', '500g', '1kg', '5kg', '10kg'],
            'Warna' => ['Merah', 'Biru', 'Hijau', 'Kuning'],
            'Pack' => ['Single', 'Pack Isi 6', 'Pack Isi 12', 'Dus'],
        ];

        $type = fake()->randomElement(array_keys($variantTypes));
        $value = fake()->randomElement($variantTypes[$type]);

        $product = Product::factory()->create([
            'name' => fake()->randomElement(['Minyak Goreng', 'Sabun Cair', 'Shampoo', 'Deterjen', 'Susu', 'Kopi']),
        ]);

        $purchasePrice = fake()->numberBetween(5000, 50000);
        $margin = fake()->numberBetween(20, 50);
        $sellingPrice = (int) ($purchasePrice * (1 + $margin / 100));

        return [
            'product_id' => $product->id,
            'variant_name' => $value,
            'sku' => strtoupper(fake()->bothify('???-#####')),
            'purchase_price' => $purchasePrice,
            'selling_price' => $sellingPrice,
            'stock' => fake()->numberBetween(5, 100),
            'low_stock_threshold' => fake()->numberBetween(5, 15),
            'is_active' => true,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
        ]);
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 3,
            'low_stock_threshold' => 10,
        ]);
    }
}
