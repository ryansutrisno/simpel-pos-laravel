<?php

use App\Models\Store;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Services\StaffPerformanceService;

uses()->group('staff-performance');

beforeEach(function () {
    Store::factory()->create();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can get sales by user', function () {
    Transaction::factory()->count(3)->create([
        'cashier_id' => $this->user->id,
        'total_amount' => 100000,
    ]);

    $sales = StaffPerformanceService::getSalesByUser(
        $this->user->id,
        today()->subMonth(),
        today()
    );

    expect($sales)->toBe(300000.00);
});

it('can get transaction count by user', function () {
    Transaction::factory()->count(5)->create([
        'cashier_id' => $this->user->id,
    ]);

    $count = StaffPerformanceService::getTransactionCountByUser(
        $this->user->id,
        today()->subMonth(),
        today()
    );

    expect($count)->toBe(5);
});

it('can get average transaction value by user', function () {
    Transaction::factory()->create([
        'cashier_id' => $this->user->id,
        'total_amount' => 50000,
    ]);
    Transaction::factory()->create([
        'cashier_id' => $this->user->id,
        'total_amount' => 150000,
    ]);

    $average = StaffPerformanceService::getAverageTransactionValue(
        $this->user->id,
        today()->subMonth(),
        today()
    );

    expect($average)->toBe(100000.00);
});

it('can get items sold by user', function () {
    $transaction1 = Transaction::factory()->create([
        'cashier_id' => $this->user->id,
    ]);
    TransactionItem::factory()->count(3)->create([
        'transaction_id' => $transaction1->id,
        'quantity' => 2,
    ]);

    $transaction2 = Transaction::factory()->create([
        'cashier_id' => $this->user->id,
    ]);
    TransactionItem::factory()->count(2)->create([
        'transaction_id' => $transaction2->id,
        'quantity' => 1,
    ]);

    $itemsSold = StaffPerformanceService::getItemsSoldByUser(
        $this->user->id,
        today()->subMonth(),
        today()
    );

    expect($itemsSold)->toBe(8);
});

it('can get top staff performers', function () {
    $user1 = User::factory()->create(['name' => 'Kasir A']);
    $user2 = User::factory()->create(['name' => 'Kasir B']);
    $user3 = User::factory()->create(['name' => 'Kasir C']);

    Transaction::factory()->count(5)->create([
        'cashier_id' => $user1->id,
        'total_amount' => 100000,
    ]);
    Transaction::factory()->count(3)->create([
        'cashier_id' => $user2->id,
        'total_amount' => 100000,
    ]);
    Transaction::factory()->count(2)->create([
        'cashier_id' => $user3->id,
        'total_amount' => 100000,
    ]);

    $topStaff = StaffPerformanceService::getTopStaff(2, today());

    expect($topStaff)->toHaveCount(2)
        ->and($topStaff->first()->id)->toBe($user1->id)
        ->and($topStaff->first()->total_sales)->toBe(500000.00)
        ->and($topStaff->last()->id)->toBe($user2->id);
});

it('can get top staff with detailed metrics', function () {
    $user = User::factory()->create(['name' => 'Top Kasir']);

    $transaction1 = Transaction::factory()->create([
        'cashier_id' => $user->id,
        'total_amount' => 200000,
    ]);
    TransactionItem::factory()->create([
        'transaction_id' => $transaction1->id,
        'quantity' => 5,
    ]);

    $transaction2 = Transaction::factory()->create([
        'cashier_id' => $user->id,
        'total_amount' => 300000,
    ]);
    TransactionItem::factory()->create([
        'transaction_id' => $transaction2->id,
        'quantity' => 3,
    ]);

    $topStaff = StaffPerformanceService::getTopStaff(1, today());

    expect($topStaff)->toHaveCount(1)
        ->and($topStaff->first()->total_sales)->toBe(500000.00)
        ->and($topStaff->first()->transaction_count)->toBe(2)
        ->and($topStaff->first()->items_sold)->toBe(8);
});

it('filters transactions by date range correctly', function () {
    Transaction::factory()->create([
        'cashier_id' => $this->user->id,
        'total_amount' => 100000,
        'created_at' => today()->subDays(10),
    ]);
    Transaction::factory()->create([
        'cashier_id' => $this->user->id,
        'total_amount' => 200000,
        'created_at' => today()->subDays(5),
    ]);
    Transaction::factory()->create([
        'cashier_id' => $this->user->id,
        'total_amount' => 300000,
        'created_at' => today(),
    ]);

    $sales = StaffPerformanceService::getSalesByUser(
        $this->user->id,
        today()->subDays(7),
        today()
    );

    expect($sales)->toBe(500000.00);
});

it('returns zero for user with no transactions', function () {
    $user = User::factory()->create();

    $sales = StaffPerformanceService::getSalesByUser(
        $user->id,
        today()->subMonth(),
        today()
    );

    expect($sales)->toBe(0.00);
});

it('returns zero average for user with no transactions', function () {
    $user = User::factory()->create();

    $average = StaffPerformanceService::getAverageTransactionValue(
        $user->id,
        today()->subMonth(),
        today()
    );

    expect($average)->toBe(0.00);
});

it('can get performance comparison between users', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Transaction::factory()->count(10)->create([
        'cashier_id' => $user1->id,
        'total_amount' => 50000,
    ]);
    Transaction::factory()->count(5)->create([
        'cashier_id' => $user2->id,
        'total_amount' => 50000,
    ]);

    $user1Sales = StaffPerformanceService::getSalesByUser($user1->id, today()->subMonth(), today());
    $user2Sales = StaffPerformanceService::getSalesByUser($user2->id, today()->subMonth(), today());

    expect($user1Sales)->toBe(500000.00)
        ->and($user2Sales)->toBe(250000.00)
        ->and($user1Sales)->toBeGreaterThan($user2Sales);
});

it('can calculate staff efficiency by items per transaction', function () {
    $transaction1 = Transaction::factory()->create([
        'cashier_id' => $this->user->id,
    ]);
    TransactionItem::factory()->count(4)->create([
        'transaction_id' => $transaction1->id,
        'quantity' => 1,
    ]);

    $transaction2 = Transaction::factory()->create([
        'cashier_id' => $this->user->id,
    ]);
    TransactionItem::factory()->count(2)->create([
        'transaction_id' => $transaction2->id,
        'quantity' => 1,
    ]);

    $transactionCount = StaffPerformanceService::getTransactionCountByUser(
        $this->user->id,
        today()->subMonth(),
        today()
    );
    $itemsSold = StaffPerformanceService::getItemsSoldByUser(
        $this->user->id,
        today()->subMonth(),
        today()
    );

    $itemsPerTransaction = $transactionCount > 0 ? $itemsSold / $transactionCount : 0;

    expect($itemsPerTransaction)->toBe(3.0);
});
