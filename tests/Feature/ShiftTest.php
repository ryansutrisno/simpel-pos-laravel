<?php

use App\Models\Shift;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ShiftService;

uses()->group('shift');

beforeEach(function () {
    Store::factory()->create();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can create a shift', function () {
    $shift = Shift::create([
        'user_id' => $this->user->id,
        'shift_type' => 'morning',
        'shift_date' => today(),
        'opening_cash' => 500000,
        'opened_at' => now(),
        'status' => 'open',
    ]);

    expect($shift)->toBeInstanceOf(Shift::class)
        ->and($shift->shift_type)->toBe('morning')
        ->and((float) $shift->opening_cash)->toBe(500000.0)
        ->and($shift->status)->toBe('open');
});

it('shift belongs to user', function () {
    $shift = Shift::factory()->create([
        'user_id' => $this->user->id,
    ]);

    expect($shift->user)->toBeInstanceOf(User::class)
        ->and($shift->user->id)->toBe($this->user->id);
});

it('shift has many transactions', function () {
    $shift = Shift::factory()->create();
    Transaction::factory()->count(3)->create([
        'shift_id' => $shift->id,
        'user_id' => $this->user->id,
    ]);

    expect($shift->transactions)->toHaveCount(3)
        ->and($shift->transactions->first())->toBeInstanceOf(Transaction::class);
});

it('shift has many expenses', function () {
    $shift = Shift::factory()->create();
    \App\Models\Expense::factory()->count(2)->create([
        'shift_id' => $shift->id,
    ]);

    expect($shift->expenses)->toHaveCount(2)
        ->and($shift->expenses->first())->toBeInstanceOf(\App\Models\Expense::class);
});

it('can check if user has active shift via service', function () {
    Shift::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'open',
        'shift_date' => today(),
    ]);

    $hasActiveShift = ShiftService::hasActiveShift($this->user->id);

    expect($hasActiveShift)->toBeTrue();
});

it('returns false when user has no active shift', function () {
    $hasActiveShift = ShiftService::hasActiveShift($this->user->id);

    expect($hasActiveShift)->toBeFalse();
});

it('can get user active shift via service', function () {
    $shift = Shift::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'open',
        'shift_date' => today(),
    ]);

    $activeShift = ShiftService::getActiveShift($this->user->id);

    expect($activeShift)->not->toBeNull()
        ->and($activeShift->id)->toBe($shift->id);
});

it('returns null when no active shift exists', function () {
    $activeShift = ShiftService::getActiveShift($this->user->id);

    expect($activeShift)->toBeNull();
});

it('can open new shift via service', function () {
    $shift = ShiftService::openShift($this->user->id, 'evening', 1000000);

    expect($shift)->toBeInstanceOf(Shift::class)
        ->and($shift->user_id)->toBe($this->user->id)
        ->and($shift->shift_type)->toBe('evening')
        ->and((float) $shift->opening_cash)->toBe(1000000.0)
        ->and($shift->status)->toBe('open');
});

it('cannot open shift if already has active shift', function () {
    Shift::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'open',
        'shift_date' => today(),
    ]);

    expect(fn () => ShiftService::openShift($this->user->id, 'morning', 500000))
        ->toThrow(\Exception::class, 'User already has an active shift');
});

it('can close shift via service', function () {
    $shift = Shift::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'open',
        'shift_date' => today(),
        'opening_cash' => 500000,
    ]);

    $closedShift = ShiftService::closeShift($shift->id, 900000, 'Shift berjalan lancar');

    expect($closedShift)->toBeInstanceOf(Shift::class)
        ->and($closedShift->status)->toBe('closed')
        ->and((float) $closedShift->closing_cash)->toBe(900000.0)
        ->and($closedShift->notes)->toBe('Shift berjalan lancar')
        ->and($closedShift->closed_at)->not->toBeNull();
});

it('cannot close already closed shift', function () {
    $shift = Shift::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'closed',
        'shift_date' => today(),
        'closed_at' => now(),
    ]);

    expect(fn () => ShiftService::closeShift($shift->id, 500000))
        ->toThrow(\Exception::class, 'Shift is already closed');
});

it('can get shift summary via service', function () {
    $shift = Shift::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'open',
        'shift_date' => today(),
    ]);

    Transaction::factory()->count(2)->create([
        'shift_id' => $shift->id,
        'user_id' => $this->user->id,

        'total' => 150000,
        'payment_method' => 'cash',
        'cash_amount' => 150000,
    ]);

    \App\Models\Expense::factory()->create([
        'shift_id' => $shift->id,
        'amount' => 50000,
    ]);

    $summary = ShiftService::getShiftSummary($shift->id);

    expect($summary)->toBeArray()
        ->and((float) $summary['total_sales'])->toBe(300000.0)
        ->and($summary['total_transactions'])->toBe(2)
        ->and((float) $summary['total_expenses'])->toBe(50000.0);
});

it('scope open returns only open shifts', function () {
    $openShift = Shift::factory()->create(['status' => 'open']);
    Shift::factory()->create(['status' => 'closed', 'closed_at' => now()]);

    $shifts = Shift::open()->get();

    expect($shifts)->toHaveCount(1)
        ->and($shifts->first()->id)->toBe($openShift->id);
});

it('scope today returns only today shifts', function () {
    $todayShift = Shift::factory()->create([
        'shift_date' => today(),
        'opened_at' => now(),
    ]);
    Shift::factory()->create([
        'shift_date' => today()->subDay(),
    ]);

    $shifts = Shift::today()->get();

    expect($shifts)->toHaveCount(1)
        ->and($shifts->first()->id)->toBe($todayShift->id);
});

it('scope by user returns only user shifts', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Shift::factory()->count(2)->create(['user_id' => $user1->id]);
    Shift::factory()->count(3)->create(['user_id' => $user2->id]);

    $shifts = Shift::byUser($user1->id)->get();

    expect($shifts)->toHaveCount(2);
});

it('can get shifts by date range via service', function () {
    Shift::factory()->create([
        'user_id' => $this->user->id,
        'shift_date' => today()->subDays(2),
    ]);
    Shift::factory()->create([
        'user_id' => $this->user->id,
        'shift_date' => today(),
    ]);
    Shift::factory()->create([
        'user_id' => $this->user->id,
        'shift_date' => today()->addDays(5),
    ]);

    $shifts = ShiftService::getShiftsByDateRange(today()->subDays(3), today()->addDay());

    expect($shifts)->toHaveCount(2);
});

it('can check if user can open shift', function () {
    expect(ShiftService::canOpenShift($this->user->id))->toBeTrue();

    Shift::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'open',
        'shift_date' => today(),
    ]);

    expect(ShiftService::canOpenShift($this->user->id))->toBeFalse();
});
