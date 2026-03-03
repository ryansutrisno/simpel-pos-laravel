<?php

namespace Database\Factories;

use App\Models\ProductBundle;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductBundleFactory extends Factory
{
    protected $model = ProductBundle::class;

    public function definition(): array
    {
        $bundleTypes = [
            ['name' => 'Beli 2 Gratis 1', 'type' => 'free_item', 'value' => 0],
            ['name' => 'Paket Hemat Lebaran', 'type' => 'percentage', 'value' => 15],
            ['name' => 'Promo Akhir Tahun', 'type' => 'percentage', 'value' => 20],
            ['name' => 'Diskon Spesial', 'type' => 'fixed_amount', 'value' => 5000],
            ['name' => 'Buy 1 Get 1 Free', 'type' => 'free_item', 'value' => 0],
        ];

        $bundle = fake()->randomElement($bundleTypes);

        return [
            'name' => $bundle['name'],
            'description' => fake()->sentence(),
            'discount_type' => $bundle['type'],
            'discount_value' => $bundle['value'],
            'start_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'end_date' => fake()->optional()->dateTimeBetween('+1 week', '+3 months'),
            'is_active' => true,
            'min_quantity' => fake()->randomElement([2, 3]),
            'priority' => fake()->numberBetween(0, 10),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => fake()->dateTimeBetween('-3 months', '-2 months'),
            'end_date' => fake()->dateTimeBetween('-1 month', '-1 week'),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
