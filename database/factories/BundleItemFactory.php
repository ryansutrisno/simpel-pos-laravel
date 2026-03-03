<?php

namespace Database\Factories;

use App\Models\BundleItem;
use App\Models\Product;
use App\Models\ProductBundle;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class BundleItemFactory extends Factory
{
    protected $model = BundleItem::class;

    public function definition(): array
    {
        $product = Product::factory()->create();
        $isFree = fake()->boolean(30);

        return [
            'bundle_id' => ProductBundle::factory(),
            'product_id' => $product->id,
            'variant_id' => null,
            'quantity' => fake()->randomElement([1, 2]),
            'is_free' => $isFree,
        ];
    }

    public function withVariant(): static
    {
        return $this->state(function (array $attributes) {
            $variant = ProductVariant::factory()->create();

            return [
                'product_id' => $variant->product_id,
                'variant_id' => $variant->id,
            ];
        });
    }

    public function freeItem(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_free' => true,
            'quantity' => 1,
        ]);
    }
}
