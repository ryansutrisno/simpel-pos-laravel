<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'expense_number' => 'EXP-'.fake()->unique()->numberBetween(1000, 9999),
            'expense_category_id' => ExpenseCategory::factory(),
            'amount' => fake()->numberBetween(10000, 1000000),
            'description' => fake()->sentence(),
            'expense_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'user_id' => User::factory(),
            'shift_id' => null,
            'attachment' => null,
        ];
    }
}
