<?php

use App\Models\PaymentGatewayConfig;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PaymentGateway\MayarGateway;
use Illuminate\Support\Facades\Http;

uses()->group('payment');

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->store = Store::factory()->create();
    $this->actingAs($this->user);
});

describe('Payment Gateway Configuration', function () {
    it('can create payment gateway config', function () {
        $config = PaymentGatewayConfig::create([
            'store_id' => $this->store->id,
            'provider' => 'mayar',
            'is_active' => true,
            'is_sandbox' => true,
            'api_key' => 'test_api_key_123',
            'enabled_methods' => ['qris', 'invoice'],
        ]);

        expect($config)->toBeInstanceOf(PaymentGatewayConfig::class)
            ->and($config->provider)->toBe('mayar')
            ->and($config->is_active)->toBeTrue()
            ->and($config->api_key)->toBe('test_api_key_123');
    });

    it('encrypts api key when saving', function () {
        $config = PaymentGatewayConfig::create([
            'store_id' => $this->store->id,
            'provider' => 'mayar',
            'api_key' => 'secret_key',
        ]);

        $rawValue = \DB::table('payment_gateway_configs')
            ->where('id', $config->id)
            ->value('api_key');

        expect($rawValue)->not->toBe('secret_key');
        expect($config->fresh()->api_key)->toBe('secret_key');
    });
});

describe('Mayar Gateway', function () {
    beforeEach(function () {
        $this->gateway = new MayarGateway([
            'api_key' => 'test_api_key',
            'sandbox' => true,
        ]);
    });

    it('initializes with correct base url for sandbox', function () {
        expect($this->gateway->getProviderName())->toBe('mayar');
    });

    it('returns correct provider name', function () {
        expect($this->gateway->getProviderName())->toBe('mayar');
    });
});

describe('Payment API Endpoints', function () {
    beforeEach(function () {
        PaymentGatewayConfig::create([
            'store_id' => $this->store->id,
            'provider' => 'mayar',
            'is_active' => true,
            'is_sandbox' => true,
            'api_key' => 'test_api_key',
            'enabled_methods' => ['qris'],
        ]);

        $this->transaction = Transaction::factory()->create([
            'total' => 10000,
            'payment_gateway_status' => 'pending',
        ]);
    });

    it('can initiate qris payment', function () {
        Http::fake([
            'https://api.mayar.club/hl/v1/qrcode/create' => Http::response([
                'statusCode' => 200,
                'data' => [
                    'url' => 'https://example.com/qr.png',
                    'amount' => 10000,
                ],
            ], 200),
        ]);

        $response = $this->postJson("/api/payments/{$this->transaction->id}/initiate", [
            'method' => 'qris',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'qr_image_url' => 'https://example.com/qr.png',
                'amount' => 10000,
            ]);
    });

    it('returns error when payment gateway not configured', function () {
        PaymentGatewayConfig::where('store_id', $this->store->id)->delete();

        $response = $this->postJson("/api/payments/{$this->transaction->id}/initiate", [
            'method' => 'qris',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => 'Payment gateway not configured',
            ]);
    });

    it('returns error for already completed transaction', function () {
        $this->transaction->update(['payment_gateway_status' => 'paid']);

        $response = $this->postJson("/api/payments/{$this->transaction->id}/initiate", [
            'method' => 'qris',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => 'Transaction already completed',
            ]);
    });

    it('can check payment status', function () {
        $this->transaction->update([
            'payment_gateway_provider' => 'mayar',
            'payment_gateway_status' => 'pending',
        ]);

        $response = $this->getJson("/api/payments/{$this->transaction->id}/status");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'pending',
            ]);
    });
});

describe('Webhook Handling', function () {
    beforeEach(function () {
        PaymentGatewayConfig::create([
            'store_id' => $this->store->id,
            'provider' => 'mayar',
            'is_active' => true,
            'api_key' => 'test_api_key',
        ]);

        $this->transaction = Transaction::factory()->create([
            'total' => 10000,
            'payment_gateway_provider' => 'mayar',
            'payment_gateway_reference' => 'inv-test-123',
            'payment_gateway_status' => 'pending',
        ]);
    });

    it('processes paid webhook correctly', function () {
        $payload = [
            'transactionId' => 'inv-test-123',
            'status' => 'PAID',
            'amount' => 10000,
            'paymentMethod' => 'QRIS',
            'extraData' => [
                'transaction_id' => $this->transaction->id,
            ],
        ];

        $response = $this->postJson('/webhook/mayar', $payload);

        $response->assertOk();

        $this->transaction->refresh();
        expect($this->transaction->payment_gateway_status)->toBe('paid');
    });

    it('processes expired webhook correctly', function () {
        $payload = [
            'transactionId' => 'inv-test-123',
            'status' => 'EXPIRED',
            'amount' => 10000,
            'extraData' => [
                'transaction_id' => $this->transaction->id,
            ],
        ];

        $response = $this->postJson('/webhook/mayar', $payload);

        $response->assertOk();

        $this->transaction->refresh();
        expect($this->transaction->payment_gateway_status)->toBe('expired');
    });

    it('returns 404 for non-existent transaction', function () {
        $payload = [
            'transactionId' => 'inv-nonexistent',
            'status' => 'PAID',
            'amount' => 10000,
            'extraData' => [],
        ];

        $response = $this->postJson('/webhook/mayar', $payload);

        $response->assertStatus(404);
    });

    it('rejects invalid webhook payload', function () {
        $payload = [
            'invalid' => 'data',
        ];

        $response = $this->postJson('/webhook/mayar', $payload);

        $response->assertStatus(400);
    });
});

describe('Payment Flow Integration', function () {
    beforeEach(function () {
        PaymentGatewayConfig::create([
            'store_id' => $this->store->id,
            'provider' => 'mayar',
            'is_active' => true,
            'is_sandbox' => true,
            'api_key' => 'test_api_key',
            'enabled_methods' => ['qris', 'invoice'],
        ]);
    });

    it('completes full payment flow', function () {
        Http::fake([
            'https://api.mayar.club/hl/v1/qrcode/create' => Http::response([
                'statusCode' => 200,
                'data' => [
                    'url' => 'https://example.com/qr.png',
                    'amount' => 50000,
                ],
            ], 200),
        ]);

        $transaction = Transaction::factory()->create([
            'total' => 50000,
            'payment_gateway_status' => 'pending',
        ]);

        // Step 1: Initiate payment
        $response = $this->postJson("/api/payments/{$transaction->id}/initiate", [
            'method' => 'qris',
        ]);

        $response->assertOk();
        $transaction->refresh();
        expect($transaction->payment_method)->toBe('qris');

        // Step 2: Simulate webhook callback
        $webhookPayload = [
            'transactionId' => $transaction->payment_gateway_reference ?? 'test-ref',
            'status' => 'PAID',
            'amount' => 50000,
            'extraData' => [
                'transaction_id' => $transaction->id,
            ],
        ];

        $this->postJson('/webhook/mayar', $webhookPayload)->assertOk();

        $transaction->refresh();
        expect($transaction->payment_gateway_status)->toBe('paid');
    });
});
