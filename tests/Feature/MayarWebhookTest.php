<?php

use App\Models\PaymentGatewayConfig;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

uses()->group('payment');

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->store = Store::factory()->create();
    $this->actingAs($this->user);
    $this->config = PaymentGatewayConfig::create([
        'store_id' => $this->store->id,
        'provider' => 'mayar',
        'is_active' => true,
        'is_sandbox' => true,
        'api_key' => 'test_api_key',
        'enabled_methods' => ['qris', 'invoice'],
    ]);
    $this->webhookUrl = '/webhook/mayar/'.$this->config->webhook_path_token;
});

function mayarWebhookPayload(string $event = 'payment.received', string $deliveryId = 'dlv_1', bool|string $status = true, int $amount = 10000): array
{
    return [
        'event' => $event,
        'data' => [
            'id' => $deliveryId,
            'status' => $status,
            'amount' => $amount,
            'merchantId' => 'merchant-test',
        ],
    ];
}

function mayarDetail(string $status, int $amount, ?int $transactionId = null): array
{
    return [
        'data' => [
            'id' => 'dlv_1',
            'status' => $status,
            'amount' => $amount,
            'extraData' => $transactionId === null ? [] : ['app_transaction_id' => (string) $transactionId],
        ],
    ];
}

it('returns 404 for an unknown webhook token without writing to the database', function (): void {
    $transactionCount = Transaction::count();

    $this->postJson('/webhook/mayar/unknown-token', mayarWebhookPayload())
        ->assertNotFound();

    expect(Transaction::count())->toBe($transactionCount);
});

it('rejects a declared payload larger than 16KB', function (): void {
    $payload = mayarWebhookPayload();
    $payload['data']['customerName'] = str_repeat('x', 17000);

    $this->postJson($this->webhookUrl, $payload)
        ->assertStatus(413);
});

it('throttles the 61st webhook request in one minute', function (): void {
    $responses = [];

    for ($index = 0; $index < 61; $index++) {
        $responses[] = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->postJson($this->webhookUrl, mayarWebhookPayload('payment.reminder', 'dlv-'.$index));
    }

    expect($responses[59]->status())->toBe(200)
        ->and($responses[60]->status())->toBe(429);
});

it('rejects malformed webhook data', function (array $payload): void {
    $this->postJson($this->webhookUrl, $payload)->assertStatus(422);
})->with([
    'non boolean status' => [mayarWebhookPayload(status: 'paid')],
    'missing delivery id' => [[
        'event' => 'payment.received',
        'data' => ['status' => true, 'amount' => 10000, 'merchantId' => 'merchant-test'],
    ]],
]);

it('acknowledges non payment events without changing the database', function (string $event): void {
    $transaction = Transaction::factory()->create([
        'store_id' => $this->store->id,
        'payment_gateway_provider' => 'mayar',
        'payment_gateway_status' => 'pending',
    ]);
    $before = [
        'payment_gateway_status' => $transaction->payment_gateway_status,
        'paid_at' => $transaction->paid_at?->timestamp,
        'status' => $transaction->status,
        'updated_at' => $transaction->updated_at?->timestamp,
    ];

    $this->postJson($this->webhookUrl, mayarWebhookPayload($event))
        ->assertOk()
        ->assertJson(['status' => 'ignored']);

    $freshTransaction = $transaction->fresh();
    expect([
        'payment_gateway_status' => $freshTransaction->payment_gateway_status,
        'paid_at' => $freshTransaction->paid_at?->timestamp,
        'status' => $freshTransaction->status,
        'updated_at' => $freshTransaction->updated_at?->timestamp,
    ])->toBe($before);
})->with([
    'reminder' => 'payment.reminder',
    'membership event' => 'membership.memberExpired',
]);

it('fulfils a payment only after fetching a matching paid detail', function (): void {
    $transaction = Transaction::factory()->create([
        'store_id' => $this->store->id,
        'total' => 10000,
        'payment_gateway_provider' => 'mayar',
        'payment_gateway_status' => 'pending',
    ]);
    Http::fake(['https://api.mayar.io/hl/v2/transactions/dlv_1' => Http::response(mayarDetail('paid', 10000, $transaction->id))]);

    $this->postJson($this->webhookUrl, mayarWebhookPayload())->assertOk();

    expect($transaction->fresh()->payment_gateway_status)->toBe('paid')
        ->and($transaction->fresh()->status)->toBe('completed')
        ->and($transaction->fresh()->paid_at)->not->toBeNull();
});

it('does not fulfil a webhook hint when the detail is not paid', function (string $status): void {
    $transaction = Transaction::factory()->create([
        'store_id' => $this->store->id,
        'total' => 10000,
        'payment_gateway_provider' => 'mayar',
        'payment_gateway_status' => 'pending',
    ]);
    Http::fake(['https://api.mayar.io/hl/v2/transactions/dlv_1' => Http::response(mayarDetail($status, 10000, $transaction->id))]);

    $this->postJson($this->webhookUrl, mayarWebhookPayload())->assertOk();

    expect($transaction->fresh()->payment_gateway_status)->toBe('pending');
})->with(['created', 'unpaid']);

it('rejects a detail amount mismatch and logs a warning', function (): void {
    $transaction = Transaction::factory()->create([
        'store_id' => $this->store->id,
        'total' => 10000,
        'payment_gateway_provider' => 'mayar',
        'payment_gateway_status' => 'pending',
    ]);
    Http::fake(['https://api.mayar.io/hl/v2/transactions/dlv_1' => Http::response(mayarDetail('paid', 9000, $transaction->id))]);
    Log::spy();

    $this->postJson($this->webhookUrl, mayarWebhookPayload())->assertOk();

    expect($transaction->fresh()->payment_gateway_status)->toBe('pending');
    Log::shouldHaveReceived('warning');
});

it('rejects a detail with a mismatched application transaction id', function (): void {
    $transaction = Transaction::factory()->create([
        'store_id' => $this->store->id,
        'total' => 10000,
        'payment_gateway_provider' => 'mayar',
        'payment_gateway_status' => 'pending',
    ]);
    Http::fake(['https://api.mayar.io/hl/v2/transactions/dlv_1' => Http::response(mayarDetail('paid', 10000, $transaction->id + 1))]);

    $this->postJson($this->webhookUrl, mayarWebhookPayload())->assertOk();

    expect($transaction->fresh()->payment_gateway_status)->toBe('pending');
});

it('is idempotent and preserves the original paid timestamp', function (): void {
    $paidAt = now()->subHour();
    $transaction = Transaction::factory()->create([
        'store_id' => $this->store->id,
        'total' => 10000,
        'payment_gateway_provider' => 'mayar',
        'payment_gateway_status' => 'paid',
        'status' => 'completed',
        'paid_at' => $paidAt,
    ]);
    Http::fake(['https://api.mayar.io/hl/v2/transactions/dlv_1' => Http::response(mayarDetail('paid', 10000, $transaction->id))]);

    $this->postJson($this->webhookUrl, mayarWebhookPayload())->assertOk();

    expect($transaction->fresh()->paid_at->timestamp)->toBe($paidAt->timestamp);
});

it('does not downgrade an already paid transaction', function (): void {
    $transaction = Transaction::factory()->create([
        'store_id' => $this->store->id,
        'total' => 10000,
        'payment_gateway_provider' => 'mayar',
        'payment_gateway_status' => 'paid',
        'status' => 'completed',
    ]);
    Http::fake(['https://api.mayar.io/hl/v2/transactions/dlv_1' => Http::response(mayarDetail('expired', 10000, $transaction->id))]);

    $this->postJson($this->webhookUrl, mayarWebhookPayload())->assertOk();

    expect($transaction->fresh()->payment_gateway_status)->toBe('paid');
});

it('expires a pending transaction when the detail is expired', function (): void {
    $transaction = Transaction::factory()->create([
        'store_id' => $this->store->id,
        'total' => 10000,
        'payment_gateway_provider' => 'mayar',
        'payment_gateway_status' => 'pending',
    ]);
    Http::fake(['https://api.mayar.io/hl/v2/transactions/dlv_1' => Http::response(mayarDetail('expired', 10000, $transaction->id))]);

    $this->postJson($this->webhookUrl, mayarWebhookPayload())->assertOk();

    expect($transaction->fresh()->payment_gateway_status)->toBe('expired')
        ->and($transaction->fresh()->status)->toBe('cancelled');
});

it('handles transient detail failures and 404 details correctly', function (int $status, int $expected): void {
    Http::fake(['https://api.mayar.io/hl/v2/transactions/dlv_1' => Http::response([], $status)]);

    $this->postJson($this->webhookUrl, mayarWebhookPayload())->assertStatus($expected);
})->with([
    'server failure' => [500, 500],
    'not found' => [404, 200],
]);

it('ignores an unknown delivery detail and logs a warning', function (): void {
    Http::fake(['https://api.mayar.io/hl/v2/transactions/dlv_1' => Http::response(mayarDetail('paid', 10000))]);
    Log::spy();

    $this->postJson($this->webhookUrl, mayarWebhookPayload())->assertOk();

    Log::shouldHaveReceived('warning');
});

it('expires the previous pending QRIS when starting another one', function (): void {
    Http::fake(['https://api.mayar.io/hl/v2/qr-codes/create' => Http::response([
        'data' => ['url' => 'https://example.com/qr.png', 'qrString' => 'qr-string', 'amount' => 10000],
    ])]);
    $first = Transaction::factory()->create(['store_id' => $this->store->id, 'total' => 10000, 'payment_gateway_status' => 'pending']);

    $this->postJson("/api/payments/{$first->id}/initiate", ['method' => 'qris'])->assertOk();
    $second = Transaction::factory()->create(['store_id' => $this->store->id, 'total' => 10000, 'payment_gateway_status' => 'pending']);
    $this->postJson("/api/payments/{$second->id}/initiate", ['method' => 'qris'])->assertOk();

    expect($first->fresh()->payment_gateway_status)->toBe('expired')
        ->and($second->fresh()->payment_gateway_qr_string)->toBe('qr-string');
});

it('resolves a QRIS payment through the extraData round trip', function (): void {
    $transaction = Transaction::factory()->create(['store_id' => $this->store->id, 'total' => 10000, 'payment_gateway_status' => 'pending']);
    Http::fake([
        'https://api.mayar.io/hl/v2/qr-codes/create' => Http::response(['data' => ['url' => 'https://example.com/qr.png', 'qrString' => 'qr-string', 'amount' => 10000]]),
        'https://api.mayar.io/hl/v2/transactions/dlv_1' => Http::response(mayarDetail('paid', 10000, $transaction->id)),
    ]);

    $this->postJson("/api/payments/{$transaction->id}/initiate", ['method' => 'qris'])->assertOk();
    $this->postJson($this->webhookUrl, mayarWebhookPayload())->assertOk();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.mayar.io/hl/v2/qr-codes/create'
        && $request['extraData']['app_transaction_id'] === (string) $transaction->id);
    expect($transaction->fresh()->payment_gateway_status)->toBe('paid');
});

it('checks Mayar status using the transaction id before the invoice reference', function (): void {
    $transaction = Transaction::factory()->create([
        'store_id' => $this->store->id,
        'payment_gateway_provider' => 'mayar',
        'payment_gateway_status' => 'pending',
        'payment_gateway_reference' => 'invoice-id',
        'payment_gateway_transaction_id' => 'transaction-id',
    ]);
    Http::fake(['https://api.mayar.io/hl/v2/transactions/transaction-id' => Http::response(mayarDetail('unpaid', (int) $transaction->total))]);

    $this->getJson("/api/payments/{$transaction->id}/status")->assertOk();

    Http::assertSentCount(1);
    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.mayar.io/hl/v2/transactions/transaction-id');
});
