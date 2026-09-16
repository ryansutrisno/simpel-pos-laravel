<?php

namespace App\Services\PaymentGateway;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mayar payment gateway implementation.
 */
class MayarGateway implements PaymentGatewayInterface
{
    protected string $baseUrl;

    protected string $apiKey;

    protected bool $sandbox;

    protected array $config;

    /**
     * @param  array{api_key: string, sandbox?: bool, config?: array}  $config
     */
    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'];
        $this->sandbox = $config['sandbox'] ?? true;
        $this->config = $config['config'] ?? [];
        $this->baseUrl = $this->sandbox
            ? config('services.mayar.sandbox_url', 'https://api.mayar.io')
            : config('services.mayar.production_url', 'https://api.mayar.id');
    }

    public function getProviderName(): string
    {
        return 'mayar';
    }

    /**
     * @return array{success: bool, data?: array, error?: string, http_status?: int, transient?: bool}
     */
    protected function request(string $method, string $endpoint, array $data = []): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->$method($this->baseUrl.$endpoint, $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            $error = $response->json('messages') ?? $response->json('message') ?? 'Unknown error';
            Log::error('Mayar API error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'error' => is_array($error) ? implode(', ', $error) : $error,
            ]);

            return [
                'success' => false,
                'error' => is_array($error) ? implode(', ', $error) : $error,
                'http_status' => $response->status(),
                'transient' => $response->serverError(),
            ];
        } catch (\Throwable $exception) {
            Log::error('Mayar API exception', [
                'endpoint' => $endpoint,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to connect to payment gateway',
                'transient' => true,
            ];
        }
    }

    public function createQRIS(int $amount, array $extraData = []): array
    {
        if ($amount < 1000) {
            return [
                'success' => false,
                'error' => 'Minimum amount is 1000 IDR',
            ];
        }

        $payload = [
            'amount' => $amount,
            'extraData' => $extraData,
        ];

        $result = $this->request('post', '/hl/v2/qr-codes/create', $payload);

        if (! $result['success']) {
            return $result;
        }

        $data = $result['data']['data'] ?? $result['data'] ?? [];

        return [
            'success' => true,
            'qr_image_url' => $data['url'] ?? null,
            'qr_string' => $data['qrString'] ?? null,
            'amount' => (int) ($data['amount'] ?? $amount),
            'reference' => $data['id'] ?? null,
            'transaction_id' => $data['transactionId'] ?? null,
        ];
    }

    public function createInvoice(array $data): array
    {
        $items = array_map(static fn (array $item): array => [
            'description' => $item['description'],
            'quantity' => $item['quantity'],
            'rate' => $item['rate'],
        ], $data['items'] ?? []);

        $payload = [
            'name' => $data['customer_name'],
            'email' => $data['customer_email'] ?? 'customer@pos.local',
            'mobile' => $data['customer_phone'] ?? '081000000000',
            'redirectUrl' => $data['callback_url'],
            'description' => $data['description'],
            'expiredAt' => $data['expired_at'] instanceof Carbon
                ? $data['expired_at']->toIso8601String()
                : Carbon::parse($data['expired_at'])->toIso8601String(),
            'items' => $items,
            'extraData' => [
                'app_transaction_id' => (string) $data['transaction_id'],
                'store_id' => (string) ($data['store_id'] ?? ''),
                'human_reference' => $data['human_reference'] ?? $data['pos_reference'],
            ],
        ];

        $result = $this->request('post', '/hl/v2/invoices/create', $payload);

        if (! $result['success']) {
            return $result;
        }

        $responseData = $result['data']['data'] ?? $result['data'] ?? [];

        return [
            'success' => true,
            'payment_url' => $responseData['link'] ?? null,
            'reference' => $responseData['id'] ?? null,
            'transaction_id' => $responseData['transactionId'] ?? null,
            'expires_at' => $responseData['expiredAt'] ?? null,
        ];
    }

    public function checkStatus(string $reference): array
    {
        $result = $this->fetchTransaction($reference);

        if (! $result['success']) {
            return $result;
        }

        return [
            'success' => true,
            'status' => match ($result['status']) {
                'paid' => 'paid',
                'unpaid', 'created' => 'pending',
                'expired' => 'expired',
                default => 'unknown',
            },
            'paid_at' => $result['paid_at'],
            'data' => $result,
        ];
    }

    /**
     * @return array{event: string, delivery_id: string, status_hint: bool, amount: int}
     */
    public function parseWebhook(array $payload): array
    {
        return [
            'event' => (string) $payload['event'],
            'delivery_id' => (string) $payload['data']['id'],
            'status_hint' => (bool) $payload['data']['status'],
            'amount' => (int) $payload['data']['amount'],
        ];
    }

    /**
     * @return array{success: bool, id?: string, status?: string, amount?: int, extraData?: array, paid_at?: ?string, error?: string, http_status?: int, transient?: bool}
     */
    public function fetchTransaction(string $id): array
    {
        $result = $this->request('get', '/hl/v2/transactions/'.rawurlencode($id));

        if (! $result['success']) {
            return $result;
        }

        $data = $result['data']['data'] ?? $result['data'] ?? [];

        return [
            'success' => true,
            'id' => (string) ($data['id'] ?? $id),
            'status' => strtolower((string) ($data['status'] ?? 'unknown')),
            'amount' => (int) ($data['amount'] ?? 0),
            'extraData' => is_array($data['extraData'] ?? null) ? $data['extraData'] : [],
            'paid_at' => $data['paidAt'] ?? $data['paid_at'] ?? null,
        ];
    }

    public function handleWebhook(array $payload): array
    {
        $parsed = $this->parseWebhook($payload);

        return [
            'reference' => $parsed['delivery_id'],
            'status' => $parsed['status_hint'] ? 'paid' : 'pending',
            'amount' => $parsed['amount'],
        ];
    }

    public function cancelPayment(string $reference): bool
    {
        return false;
    }
}
