<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Shift;
use App\Models\Store;
use App\Models\User;
use App\Services\ExpenseService;

uses()->group('expense');

beforeEach(function () {
    Store::factory()->create();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can create expense category', function () {
    $category = ExpenseCategory::create([
        'name' => 'Listrik',
        'description' => 'Biaya listrik toko',
    ]);

    expect($category)->toBeInstanceOf(ExpenseCategory::class)
        ->and($category->name)->toBe('Listrik')
        ->and($category->description)->toBe('Biaya listrik toko');
});

it('can create expense', function () {
    $category = ExpenseCategory::factory()->create();

    $expense = Expense::create([
        'expense_number' => 'EXP-001',
        'expense_category_id' => $category->id,
        'amount' => 500000,
        'description' => 'Pembayaran listrik bulan Januari',
        'expense_date' => today(),
        'user_id' => $this->user->id,
        'shift_id' => null,
    ]);

    expect($expense)->toBeInstanceOf(Expense::class)
        ->and((float) $expense->amount)->toBe(500000.00)
        ->and($expense->description)->toBe('Pembayaran listrik bulan Januari');
});

it('expense belongs to category', function () {
    $category = ExpenseCategory::factory()->create();
    $expense = Expense::factory()->create([
        'expense_category_id' => $category->id,
    ]);

    expect($expense->category)->toBeInstanceOf(ExpenseCategory::class)
        ->and($expense->category->id)->toBe($category->id);
});

it('expense belongs to user', function () {
    $expense = Expense::factory()->create([
        'user_id' => $this->user->id,
    ]);

    expect($expense->user)->toBeInstanceOf(User::class)
        ->and($expense->user->id)->toBe($this->user->id);
});

it('expense belongs to shift', function () {
    $shift = Shift::factory()->create();
    $expense = Expense::factory()->create([
        'shift_id' => $shift->id,
    ]);

    expect($expense->shift)->toBeInstanceOf(Shift::class)
        ->and($expense->shift->id)->toBe($shift->id);
});

it('can get total expense by category via service', function () {
    $category1 = ExpenseCategory::factory()->create(['name' => 'Listrik']);
    $category2 = ExpenseCategory::factory()->create(['name' => 'Air']);

    Expense::factory()->create([
        'expense_category_id' => $category1->id,
        'amount' => 500000,
        'expense_date' => today(),
    ]);
    Expense::factory()->create([
        'expense_category_id' => $category1->id,
        'amount' => 300000,
        'expense_date' => today(),
    ]);
    Expense::factory()->create([
        'expense_category_id' => $category2->id,
        'amount' => 200000,
        'expense_date' => today(),
    ]);

    $summary = ExpenseService::getTotalByCategory(today()->startOfMonth(), today()->endOfMonth());

    expect($summary)->toHaveCount(2)
        ->and($summary[$category1->id]['total'])->toBe(800000.00)
        ->and($summary[$category2->id]['total'])->toBe(200000.00);
});

it('can calculate daily expense via service', function () {
    Expense::factory()->count(3)->create([
        'amount' => 100000,
        'expense_date' => today(),
    ]);

    $total = ExpenseService::getDailyExpense(today());

    expect($total)->toBe(300000.00);
});

it('can calculate monthly expense via service', function () {
    Expense::factory()->count(2)->create([
        'amount' => 100000,
        'expense_date' => today(),
    ]);
    Expense::factory()->create([
        'amount' => 50000,
        'expense_date' => today()->subMonth(),
    ]);

    $total = ExpenseService::getMonthlyExpense(now());

    expect($total)->toBe(200000.00);
});

it('can get expense by shift via service', function () {
    $shift = Shift::factory()->create();

    Expense::factory()->create([
        'shift_id' => $shift->id,
        'amount' => 100000,
    ]);
    Expense::factory()->create([
        'shift_id' => $shift->id,
        'amount' => 50000,
    ]);
    Expense::factory()->create([
        'amount' => 25000,
    ]);

    $total = ExpenseService::getExpenseByShift($shift->id);

    expect($total)->toBe(150000.00);
});

it('can generate expense number via service', function () {
    $number1 = ExpenseService::generateExpenseNumber();

    // Create an expense to increment the sequence
    Expense::factory()->create(['expense_number' => $number1]);

    $number2 = ExpenseService::generateExpenseNumber();

    expect($number1)->toStartWith('EXP')
        ->and($number1)->not->toBe($number2);
});

it('scope date range works correctly', function () {
    Expense::factory()->create(['expense_date' => today()->subDays(10)]);
    Expense::factory()->create(['expense_date' => today()->subDays(5)]);
    Expense::factory()->create(['expense_date' => today()]);
    Expense::factory()->create(['expense_date' => today()->addDays(5)]);

    $expenses = Expense::whereBetween('expense_date', [today()->subDays(7), today()->addDay()])->get();

    expect($expenses)->toHaveCount(2);
});

it('can filter expenses by user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Expense::factory()->count(2)->create(['user_id' => $user1->id]);
    Expense::factory()->count(3)->create(['user_id' => $user2->id]);

    $expenses = Expense::where('user_id', $user1->id)->get();

    expect($expenses)->toHaveCount(2);
});

it('can filter expenses by shift', function () {
    $shift1 = Shift::factory()->create();
    $shift2 = Shift::factory()->create();

    Expense::factory()->count(2)->create(['shift_id' => $shift1->id]);
    Expense::factory()->count(3)->create(['shift_id' => $shift2->id]);

    $expenses = Expense::where('shift_id', $shift1->id)->get();

    expect($expenses)->toHaveCount(2);
});
