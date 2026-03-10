<?php

namespace App\Services\PaymentGateway;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransGateway implements PaymentGatewayInterface
{
    protected string $serverKey;

    protected string $clientKey;

    protected string $baseUrl;

    protected bool $isProduction;

    protected array $config;

    public function __construct(array $config)
    {
        $this->serverKey = $config['config']['server_key'] ?? '';
        $this->clientKey = $config['config']['client_key'] ?? '';
        $this->isProduction = ($config['config']['environment'] ?? 'sandbox') === 'production';
        $this->config = $config;
        $this->baseUrl = $this->isProduction
            ? 'https://app.midtrans.com/snap/v1'
            : 'https://app.sandbox.midtrans.com/snap/v1';
    }

    public function getProviderName(): string
    {
        return 'midtrans';
    }

    protected function request(string $method, string $endpoint, array $data = []): array
    {
        try {
            $url = $this->baseUrl.$endpoint;

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic '.base64_encode($this->serverKey.':'),
            ])->$method($url, $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            $error = $response->json('error_messages') ?? $response->json('message') ?? 'Unknown error';
            Log::error('Midtrans API error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'error' => $error,
            ]);

            return [
                'success' => false,
                'error' => is_array($error) ? implode(', ', $error) : $error,
            ];
        } catch (\Exception $e) {
            Log::error('Midtrans API exception', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to connect to payment gateway: '.$e->getMessage(),
            ];
        }
    }

    public function createQRIS(int $amount): array
    {
        if ($amount < 1000) {
            return [
                'success' => false,
                'error' => 'Minimum amount is 1000 IDR',
            ];
        }

        $payload = [
            'transaction_details' => [
                'order_id' => 'POS-'.time().'-'.rand(1000, 9999),
                'gross_amount' => $amount,
            ],
            'enabled_payments' => ['gopay', 'shopeepay', 'other_qris'],
        ];

        $result = $this->request('post', '/transactions', $payload);

        if (! $result['success']) {
            return $result;
        }

        $data = $result['data'] ?? [];

        return [
            'success' => true,
            'qr_image_url' => $data['qr_code_url'] ?? null,
            'amount' => $amount,
            'reference' => $data['order_id'] ?? null,
            'transaction_id' => $data['transaction_id'] ?? null,
            'redirect_url' => $data['redirect_url'] ?? null,
        ];
    }

    public function createInvoice(array $data): array
    {
        $payload = [
            'transaction_details' => [
                'order_id' => $data['transaction_id'] ?? ('POS-'.time()),
                'gross_amount' => $data['amount'] ?? 0,
            ],
            'customer_details' => [
                'first_name' => $data['customer_name'] ?? 'Guest',
                'email' => $data['customer_email'] ?? null,
                'phone' => $data['customer_phone'] ?? null,
            ],
            'item_details' => array_map(fn ($item) => [
                'id' => $item['sku'] ?? rand(1000, 9999),
                'price' => $item['rate'] ?? 0,
                'quantity' => $item['quantity'] ?? 1,
                'name' => $item['description'] ?? 'Product',
            ], $data['items'] ?? []),
            'enabled_payments' => ['credit_card', 'gopay', 'shopeepay', 'bank_transfer'],
        ];

        $result = $this->request('post', '/transactions', $payload);

        if (! $result['success']) {
            return $result;
        }

        $responseData = $result['data'] ?? [];

        return [
            'success' => true,
            'payment_url' => $responseData['redirect_url'] ?? null,
            'reference' => $responseData['order_id'] ?? null,
            'transaction_id' => $responseData['transaction_id'] ?? null,
            'expires_at' => isset($responseData['expiry_time'])
                ? strtotime($responseData['expiry_time']) * 1000
                : null,
        ];
    }

    public function checkStatus(string $paymentId): array
    {
        $result = $this->request('get', '/transactions/'.$paymentId.'/status', []);

        if (! $result['success']) {
            return $result;
        }

        $data = $result['data'] ?? [];
        $status = $data['transaction_status'] ?? 'unknown';

        $statusMap = [
            'capture' => 'paid',
            'settlement' => 'paid',
            'pending' => 'pending',
            'deny' => 'failed',
            'cancel' => 'cancelled',
            'expire' => 'expired',
            'refund' => 'refunded',
        ];

        return [
            'success' => true,
            'status' => $statusMap[$status] ?? 'unknown',
            'original_status' => $status,
            'data' => $data,
        ];
    }

    public function cancelPayment(string $paymentId): bool
    {
        $result = $this->request('post', '/transactions/'.$paymentId.'/cancel', []);

        return $result['success'];
    }

    public function handleWebhook(array $payload): array
    {
        $signatureKey = $payload['signature_key'] ?? '';
        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';

        $expectedSignature = hash('sha512', $orderId.$statusCode.$grossAmount.$this->serverKey);

        if (! hash_equals($expectedSignature, $signatureKey)) {
            return [
                'success' => false,
                'error' => 'Invalid signature',
            ];
        }

        $status = $payload['transaction_status'] ?? 'unknown';
        $statusMap = [
            'capture' => 'paid',
            'settlement' => 'paid',
            'pending' => 'pending',
            'deny' => 'failed',
            'cancel' => 'cancelled',
            'expire' => 'expired',
        ];

        return [
            'success' => true,
            'status' => $statusMap[$status] ?? 'unknown',
            'order_id' => $orderId,
            'data' => $payload,
        ];
    }
}
