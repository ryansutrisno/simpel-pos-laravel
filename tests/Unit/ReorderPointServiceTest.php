<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReorderAlert;
use App\Models\Store;
use App\Models\User;
use App\Services\ReorderPointService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses()->group('reorder');

uses(RefreshDatabase::class);

beforeEach(function () {
    Store::factory()->create();
});

it('can check stock levels and create alerts', function () {
    // Create product below reorder point
    $product = Product::factory()->create([
        'stock' => 5,
        'reorder_point' => 10,
    ]);

    $alerts = ReorderPointService::checkStockLevels();

    expect($alerts)->toHaveCount(1)
        ->and($alerts[0]->product_id)->toBe($product->id)
        ->and($alerts[0]->current_stock)->toBe(5);
});

it('does not duplicate pending alerts', function () {
    $product = Product::factory()->create([
        'stock' => 5,
        'reorder_point' => 10,
    ]);

    // First check creates alert
    ReorderPointService::checkStockLevels();

    // Second check should not create duplicate
    $alerts = ReorderPointService::checkStockLevels();

    expect($alerts)->toHaveCount(0)
        ->and(ReorderAlert::count())->toBe(1);
});

it('can get pending alerts', function () {
    ReorderAlert::factory()->count(3)->pending()->create();
    ReorderAlert::factory()->count(2)->acknowledged()->create();

    $pending = ReorderPointService::getPendingAlerts();

    expect($pending)->toHaveCount(3);
});

it('can acknowledge alert', function () {
    $user = User::factory()->create();
    $alert = ReorderAlert::factory()->pending()->create();

    $acknowledged = ReorderPointService::acknowledgeAlert($alert->id, $user->id, 'Will order soon');

    expect($acknowledged)->not->toBeNull()
        ->and($acknowledged->status)->toBe('acknowledged')
        ->and($acknowledged->acknowledged_by)->toBe($user->id);
});

it('cannot acknowledge non-pending alert', function () {
    $user = User::factory()->create();
    $alert = ReorderAlert::factory()->ordered()->create();

    $result = ReorderPointService::acknowledgeAlert($alert->id, $user->id);

    expect($result)->toBeNull();
});

it('can mark alert as ordered', function () {
    $alert = ReorderAlert::factory()->pending()->create();

    $ordered = ReorderPointService::markAsOrdered($alert->id, 'Ordered from supplier');

    expect($ordered->status)->toBe('ordered');
});

it('can get alerts count by status', function () {
    ReorderAlert::factory()->count(3)->pending()->create();
    ReorderAlert::factory()->count(2)->acknowledged()->create();
    ReorderAlert::factory()->count(1)->ordered()->create();

    $counts = ReorderPointService::getAlertsCount();

    expect($counts['pending'])->toBe(3)
        ->and($counts['acknowledged'])->toBe(2)
        ->and($counts['ordered'])->toBe(1)
        ->and($counts['total'])->toBe(6);
});

it('can check variant stock levels', function () {
    $variant = ProductVariant::factory()->create([
        'stock' => 3,
        'low_stock_threshold' => 10,
    ]);

    $alerts = ReorderPointService::checkStockLevels();

    expect($alerts)->toHaveCount(1)
        ->and($alerts[0]->variant_id)->toBe($variant->id);
});

it('can update product reorder point', function () {
    $product = Product::factory()->create(['reorder_point' => 10]);

    $updated = ReorderPointService::updateReorderPoint($product->id, 15, 100);

    expect($updated->reorder_point)->toBe(15)
        ->and($updated->reorder_quantity)->toBe(100);
});

it('can get recommended purchase quantity', function () {
    $product = Product::factory()->create([
        'reorder_point' => 10,
        'reorder_quantity' => 50,
    ]);

    $recommended = ReorderPointService::getRecommendedPurchaseQuantity($product->id);

    expect($recommended)->toBe(50);
});

it('returns default quantity when product has no settings', function () {
    $product = Product::factory()->create([
        'reorder_quantity' => 50,
        'reorder_point' => 5,
    ]);

    $recommended = ReorderPointService::getRecommendedPurchaseQuantity($product->id);

    expect($recommended)->toBe(50); // max(5*2, 50)
});

it('can dismiss alert', function () {
    $alert = ReorderAlert::factory()->create();

    $result = ReorderPointService::dismissAlert($alert->id);

    expect($result)->toBeTrue()
        ->and(ReorderAlert::find($alert->id))->toBeNull();
});

it('returns false when dismissing non-existent alert', function () {
    $result = ReorderPointService::dismissAlert(99999);

    expect($result)->toBeFalse();
});

it('should notify when pending alerts exist', function () {
    ReorderAlert::factory()->pending()->create();

    expect(ReorderPointService::shouldNotify())->toBeTrue();
});

it('should not notify when no pending alerts', function () {
    ReorderAlert::factory()->acknowledged()->create();

    expect(ReorderPointService::shouldNotify())->toBeFalse();
});
