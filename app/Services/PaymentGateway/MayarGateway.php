<?php

namespace App\Services\PaymentGateway;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mayar payment gateway implementation.
 *
 * Supports QRIS generation and invoice creation via Mayar API.
 *
 * @see https://docs.mayar.id/
 */
class MayarGateway implements PaymentGatewayInterface
{
    /**
     * Base URL for Mayar API.
     */
    protected string $baseUrl;

    /**
     * API Key for authentication.
     */
    protected string $apiKey;

    /**
     * Whether to use sandbox environment.
     */
    protected bool $sandbox;

    /**
     * Additional configuration.
     */
    protected array $config;

    /**
     * Create a new Mayar gateway instance.
     *
     * @param array{
     *     api_key: string,
     *     sandbox: bool,
     *     config?: array
     * } $config
     */
    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'];
        $this->sandbox = $config['sandbox'] ?? true;
        $this->config = $config['config'] ?? [];
        $this->baseUrl = $this->sandbox
            ? 'https://api.mayar.club/hl/v1'
            : 'https://api.mayar.id/hl/v1';
    }

    /**
     * Get the provider name.
     */
    public function getProviderName(): string
    {
        return 'mayar';
    }

    /**
     * Make an authenticated HTTP request to Mayar API.
     *
     * @param  string  $method  HTTP method (get, post, etc.)
     * @param  string  $endpoint  API endpoint (without base URL)
     * @param  array  $data  Request data
     * @return array{
     *     success: bool,
     *     data?: array,
     *     error?: string
     * }
     */
    protected function request(string $method, string $endpoint, array $data = []): array
    {
        try {
            $url = $this->baseUrl.$endpoint;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->$method($url, $data);

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
                'error' => $error,
            ]);

            return [
                'success' => false,
                'error' => is_array($error) ? implode(', ', $error) : $error,
            ];
        } catch (\Exception $e) {
            Log::error('Mayar API exception', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to connect to payment gateway: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Create a QRIS payment code.
     *
     * @param  int  $amount  Amount in IDR (minimum 1000)
     */
    public function createQRIS(int $amount): array
    {
        if ($amount < 1000) {
            return [
                'success' => false,
                'error' => 'Minimum amount is 1000 IDR',
            ];
        }

        $result = $this->request('post', '/qrcode/create', [
            'amount' => $amount,
        ]);

        if (! $result['success']) {
            return $result;
        }

        // Response format: {"statusCode": 200, "data": {"url": "...", "amount": ...}}
        $data = $result['data']['data'] ?? $result['data'] ?? [];

        return [
            'success' => true,
            'qr_image_url' => $data['url'] ?? null,
            'amount' => $data['amount'] ?? $amount,
        ];
    }

    /**
     * Create an invoice with customer details.
     */
    public function createInvoice(array $data): array
    {
        $items = array_map(function ($item) {
            return [
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'rate' => $item['rate'],
            ];
        }, $data['items'] ?? []);

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
                'transaction_id' => (string) $data['transaction_id'],
                'pos_reference' => $data['pos_reference'],
            ],
        ];

        $result = $this->request('post', '/invoice/create', $payload);

        if (! $result['success']) {
            return $result;
        }

        $responseData = $result['data']['data'] ?? [];

        return [
            'success' => true,
            'payment_url' => $responseData['link'] ?? null,
            'reference' => $responseData['id'] ?? null,
            'transaction_id' => $responseData['transactionId'] ?? null,
            'expires_at' => $responseData['expiredAt'] ?? null,
        ];
    }

    /**
     * Check the status of a payment.
     */
    public function checkStatus(string $reference): array
    {
        $result = $this->request('get', '/invoice/detail', [
            'id' => $reference,
        ]);

        if (! $result['success']) {
            return $result;
        }

        $data = $result['data']['data'] ?? [];
        $status = strtolower($data['status'] ?? 'unknown');

        // Map Mayar status to our standard status
        $mappedStatus = match ($status) {
            'unpaid' => 'pending',
            'paid' => 'paid',
            'expired' => 'expired',
            default => 'unknown',
        };

        return [
            'success' => true,
            'status' => $mappedStatus,
            'paid_at' => $data['paidAt'] ?? null,
        ];
    }

    /**
     * Handle webhook callback from Mayar.
     */
    public function handleWebhook(array $data): array
    {
        $status = strtolower($data['status'] ?? 'unknown');

        // Map Mayar webhook status
        $mappedStatus = match ($status) {
            'unpaid' => 'pending',
            'paid' => 'paid',
            'expired' => 'expired',
            default => 'unknown',
        };

        return [
            'reference' => $data['transactionId'] ?? '',
            'status' => $mappedStatus,
            'amount' => $data['amount'] ?? 0,
            'payment_method' => $data['paymentMethod'] ?? null,
            'external_reference' => $data['extraData']['transaction_id'] ?? null,
            'paid_at' => $data['paidAt'] ?? null,
        ];
    }

    /**
     * Cancel a pending payment.
     */
    public function cancelPayment(string $reference): bool
    {
        $result = $this->request('post', '/invoice/close', [
            'id' => $reference,
        ]);

        return $result['success'];
    }
}
