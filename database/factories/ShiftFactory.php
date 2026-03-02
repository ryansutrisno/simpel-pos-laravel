<?php

namespace Database\Factories;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition(): array
    {
        $shiftType = fake()->randomElement(['morning', 'evening']);
        $shiftDate = fake()->dateTimeBetween('-1 week', 'now');
        $openedAt = fake()->dateTimeBetween('-1 week', 'now');
        $isClosed = fake()->boolean(60);
        $openingCash = fake()->numberBetween(100000, 1000000);
        $expectedCash = $openingCash + fake()->numberBetween(50000, 500000);

        return [
            'user_id' => User::factory(),
            'shift_date' => $openedAt,
            'shift_type' => $shiftType,
            'opening_cash' => $openingCash,
            'closing_cash' => $isClosed ? fake()->numberBetween(100000, 2000000) : null,
            'expected_cash' => $isClosed ? $expectedCash : null,
            'difference' => $isClosed ? fake()->numberBetween(-100000, 100000) : null,
            'opened_at' => $openedAt,
            'closed_at' => $isClosed ? fake()->dateTimeBetween($openedAt, 'now') : null,
            'notes' => fake()->sentence(),
            'status' => $isClosed ? 'closed' : 'open',
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
            'closed_at' => null,
            'closing_cash' => null,
            'expected_cash' => null,
            'difference' => null,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
            'closed_at' => fake()->dateTimeBetween($attributes['opened_at'] ?? '-1 day', 'now'),
            'closing_cash' => fake()->numberBetween(100000, 2000000),
        ]);
    }
}
