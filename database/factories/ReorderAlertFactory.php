<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReorderAlert;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReorderAlertFactory extends Factory
{
    protected $model = ReorderAlert::class;

    public function definition(): array
    {
        $product = Product::factory()->create();
        $reorderPoint = fake()->numberBetween(5, 20);
        $currentStock = fake()->numberBetween(0, $reorderPoint);

        return [
            'product_id' => $product->id,
            'variant_id' => null,
            'current_stock' => $currentStock,
            'reorder_point' => $reorderPoint,
            'status' => fake()->randomElement(['pending', 'acknowledged', 'ordered']),
            'notified_at' => now(),
            'acknowledged_by' => null,
            'acknowledged_at' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'acknowledged_by' => null,
            'acknowledged_at' => null,
        ]);
    }

    public function acknowledged(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'acknowledged',
                'acknowledged_by' => User::factory(),
                'acknowledged_at' => now(),
            ];
        });
    }

    public function ordered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ordered',
        ]);
    }

    public function forVariant(): static
    {
        return $this->state(function (array $attributes) {
            $variant = ProductVariant::factory()->create();

            return [
                'product_id' => $variant->product_id,
                'variant_id' => $variant->id,
                'current_stock' => $variant->stock,
                'reorder_point' => $variant->low_stock_threshold,
            ];
        });
    }
}
