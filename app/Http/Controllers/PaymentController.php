<?php

namespace App\Http\Controllers;

use App\Http\Requests\MayarWebhookRequest;
use App\Models\PaymentGatewayConfig;
use App\Models\Store;
use App\Models\Transaction;
use App\Services\PaymentGateway\MayarGateway;
use App\Services\PaymentGateway\PaymentGatewayFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Controller for handling payment gateway operations.
 */
class PaymentController extends Controller
{
    /**
     * Initiate a payment for a transaction.
     */
    public function initiatePayment(Request $request, Transaction $transaction): JsonResponse
    {
        // Validate request
        $validated = $request->validate([
            'method' => 'required|string|in:qris,invoice',
        ]);

        // Check if transaction can be paid
        if ($transaction->payment_gateway_status === 'paid') {
            return response()->json([
                'success' => false,
                'error' => 'Transaction already completed',
            ], 400);
        }

        if ($transaction->payment_gateway_status === 'expired') {
            return response()->json([
                'success' => false,
                'error' => 'Transaction is expired',
            ], 400);
        }

        // Get store's payment gateway config - first check PaymentGatewayConfig, then check stores table
        $config = PaymentGatewayConfig::where('store_id', $transaction->store_id ?? 1)
            ->where('is_active', true)
            ->first();

        // If no PaymentGatewayConfig, check stores table for payment settings
        if (! $config) {
            $store = Store::first();
            if (! $store?->payment_gateway_enabled) {
                return response()->json([
                    'success' => false,
                    'error' => 'Payment gateway not configured',
                ], 400);
            }

            // Create config object from store settings
            $storeConfig = [
                'provider' => $store->payment_gateway_provider ?? 'mayar',
                'sandbox' => $store->payment_gateway_sandbox ?? true,
                'config' => $store->payment_config ?? [],
            ];
            $enabledMethods = $store->payment_enabled_methods ?? ['qris'];

            return $this->processPaymentFromStoreConfig($transaction, $validated['method'], $storeConfig, $enabledMethods);
        }

        // Check if method is enabled
        $enabledMethods = $config->enabled_methods ?? ['qris'];
        if (! in_array($validated['method'], $enabledMethods)) {
            return response()->json([
                'success' => false,
                'error' => 'Payment method not enabled',
            ], 400);
        }

        try {
            // Create gateway instance
            $gateway = PaymentGatewayFactory::make($config->provider, $config->getGatewayConfig());

            if ($validated['method'] === 'qris') {
                $this->expirePendingQrisTransactions($transaction);
                $result = $gateway instanceof MayarGateway
                    ? $gateway->createQRIS((int) $transaction->total, [
                        'app_transaction_id' => (string) $transaction->id,
                        'store_id' => (string) $transaction->store_id,
                        'human_reference' => 'POS-'.$transaction->id,
                    ])
                    : $gateway->createQRIS((int) $transaction->total);

                if ($result['success']) {
                    $expiresAt = now()->addHours(24);
                    $transaction->update([
                        'payment_method' => 'qris',
                        'payment_gateway_provider' => $config->provider,
                        'payment_gateway_status' => 'pending',
                        'payment_gateway_qr_string' => $result['qr_string'] ?? null,
                        'payment_gateway_expires_at' => $expiresAt,
                    ]);
                    $result['expires_at'] = $expiresAt->getTimestampMs();
                }

                return response()->json($result);
            }

            if ($validated['method'] === 'invoice') {
                // Create invoice
                $result = $gateway->createInvoice([
                    'customer_name' => $transaction->customer->name ?? 'Guest',
                    'customer_email' => $transaction->customer->email ?? null,
                    'customer_phone' => $transaction->customer->phone ?? null,
                    'description' => "Order #{$transaction->id}",
                    'amount' => (int) $transaction->total,
                    'callback_url' => route('payment.callback', ['provider' => $config->provider]),
                    'expired_at' => now()->addHours(24),
                    'transaction_id' => $transaction->id,
                    'store_id' => $transaction->store_id,
                    'human_reference' => "POS-{$transaction->id}",
                    'pos_reference' => "POS-{$transaction->id}",
                    'items' => $transaction->items->map(fn ($item) => [
                        'description' => $item->product->name ?? 'Product',
                        'quantity' => $item->quantity,
                        'rate' => (int) $item->price,
                    ])->toArray(),
                ]);

                if ($result['success']) {
                    $transaction->update([
                        'payment_method' => 'invoice',
                        'payment_gateway_provider' => $config->provider,
                        'payment_gateway_reference' => $result['reference'],
                        'payment_gateway_transaction_id' => $result['transaction_id'] ?? null,
                        'payment_gateway_status' => 'pending',
                        'payment_gateway_expires_at' => isset($result['expires_at'])
                            ? now()->setTimestamp($result['expires_at'] / 1000)
                            : now()->addHours(24),
                    ]);
                }

                return response()->json($result);
            }

            return response()->json([
                'success' => false,
                'error' => 'Invalid payment method',
            ], 400);

        } catch (\Exception $e) {
            Log::error('Payment initiation failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to initiate payment: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check the status of a payment.
     */
    public function checkStatus(Transaction $transaction): JsonResponse
    {
        if (! $transaction->payment_gateway_provider) {
            return response()->json([
                'success' => false,
                'error' => 'No payment gateway associated',
            ], 400);
        }

        $config = PaymentGatewayConfig::where('provider', $transaction->payment_gateway_provider)
            ->where('is_active', true)
            ->first();

        if (! $config) {
            // Check if using store config
            $store = Store::first();
            if ($store && $store->payment_gateway_enabled) {
                // For QRIS, status is updated via webhook
                if ($transaction->payment_method === 'qris' && ! $transaction->payment_gateway_reference && ! $transaction->payment_gateway_transaction_id) {
                    return response()->json([
                        'success' => true,
                        'status' => $transaction->payment_gateway_status ?? 'pending',
                        'expires_at' => $transaction->payment_gateway_expires_at,
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'status' => $transaction->payment_gateway_status ?? 'pending',
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Payment gateway configuration not found',
            ], 400);
        }

        try {
            $gateway = PaymentGatewayFactory::make($config->provider, $config->getGatewayConfig());

            // For QRIS, we don't have a reference to check status
            // Status is updated via webhook
            if ($transaction->payment_method === 'qris' && ! $transaction->payment_gateway_reference && ! $transaction->payment_gateway_transaction_id) {
                return response()->json([
                    'success' => true,
                    'status' => $transaction->payment_gateway_status ?? 'pending',
                    'expires_at' => $transaction->payment_gateway_expires_at,
                ]);
            }

            if ($transaction->payment_gateway_reference || $transaction->payment_gateway_transaction_id) {
                $reference = $transaction->payment_gateway_transaction_id
                    ?: $transaction->payment_gateway_reference;
                $result = $gateway->checkStatus($reference);

                if ($result['success'] && $result['status'] !== $transaction->payment_gateway_status) {
                    // Update transaction status
                    $transaction->update([
                        'payment_gateway_status' => $result['status'],
                        'paid_at' => $result['paid_at'] ? now() : $transaction->paid_at,
                        'status' => $result['status'] === 'paid' ? 'completed' : $transaction->status,
                    ]);
                }

                return response()->json($result);
            }

            return response()->json([
                'success' => true,
                'status' => $transaction->payment_gateway_status ?? 'pending',
            ]);

        } catch (\Exception $e) {
            Log::error('Payment status check failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to check payment status',
            ], 500);
        }
    }

    /**
     * Handle webhook callback from Mayar.
     */
    public function handleMayarWebhook(MayarWebhookRequest $request, string $token): JsonResponse
    {
        $config = PaymentGatewayConfig::query()
            ->where('provider', PaymentGatewayConfig::PROVIDER_MAYAR)
            ->where('is_active', true)
            ->where('webhook_path_token', $token)
            ->firstOrFail();
        $gateway = PaymentGatewayFactory::make(PaymentGatewayConfig::PROVIDER_MAYAR, $config->getGatewayConfig());
        if (! $gateway instanceof MayarGateway) {
            return response()->json(['status' => 'retry'], 500);
        }

        $parsed = $gateway->parseWebhook($request->validated());

        if (! $parsed || $parsed['event'] !== 'payment.received') {
            return response()->json(['status' => 'ignored']);
        }

        $detail = $gateway->fetchTransaction($parsed['delivery_id']);
        $detailStatus = $detail['status'] ?? 'unknown';
        $detailAmount = (int) ($detail['amount'] ?? 0);

        if (! ($detail['success'] ?? false)) {
            $logContext = [
                'transaction_id' => null,
                'mayar_status' => $detailStatus,
                'amount' => $detailAmount,
                'ip' => $request->ip(),
            ];

            if (($detail['http_status'] ?? null) === 404) {
                Log::warning('Mayar transaction detail not found', $logContext);

                return response()->json(['status' => 'ignored']);
            }

            Log::warning('Mayar transaction detail unavailable', $logContext);

            return response()->json(['status' => 'retry'], 500);
        }

        $extraData = $detail['extraData'] ?? [];
        $localTransactionId = $extraData['app_transaction_id'] ?? null;
        $transaction = is_scalar($localTransactionId)
            ? Transaction::query()
                ->whereKey((string) $localTransactionId)
                ->where('store_id', $config->store_id)
                ->where('payment_gateway_provider', PaymentGatewayConfig::PROVIDER_MAYAR)
                ->first()
            : null;

        if (! $transaction) {
            Log::warning('Mayar delivery has no matching transaction', [
                'transaction_id' => null,
                'mayar_status' => $detailStatus,
                'amount' => $detailAmount,
                'ip' => $request->ip(),
            ]);

            return response()->json(['status' => 'ignored']);
        }

        $context = [
            'transaction_id' => $transaction->id,
            'mayar_status' => $detailStatus,
            'amount' => $detailAmount,
            'ip' => $request->ip(),
        ];

        if ($detailAmount !== (int) $transaction->total || ! is_scalar($localTransactionId) || (string) $localTransactionId !== (string) $transaction->id) {
            Log::warning('Mayar transaction detail verification failed', $context);

            return response()->json(['status' => 'ignored']);
        }

        if ($transaction->payment_method === 'qris'
            && $transaction->payment_gateway_expires_at
            && $transaction->payment_gateway_expires_at->isPast()
            && $detailStatus === 'paid') {
            Log::warning('Mayar QRIS transaction outside payment window', $context);

            return response()->json(['status' => 'ignored']);
        }

        DB::transaction(function () use ($transaction, $detailStatus, $detail): void {
            $lockedTransaction = Transaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedTransaction || $lockedTransaction->payment_gateway_status === 'paid') {
                return;
            }

            if ($lockedTransaction->payment_gateway_status !== 'pending') {
                return;
            }

            if ($detailStatus === 'paid') {
                $lockedTransaction->update([
                    'payment_gateway_status' => 'paid',
                    'status' => 'completed',
                    'paid_at' => $lockedTransaction->paid_at ?? ($detail['paid_at'] ?? now()),
                ]);

                return;
            }

            if ($detailStatus === 'expired') {
                $lockedTransaction->update([
                    'payment_gateway_status' => 'expired',
                    'status' => 'cancelled',
                ]);
            }
        });

        Log::info('Mayar transaction webhook reconciled', $context);

        return response()->json(['status' => 'ok']);
    }

    public function handleCallback(string $provider): RedirectResponse
    {
        abort_unless(PaymentGatewayFactory::isSupported($provider), 404);

        return redirect('/admin')->with('payment_callback', true);
    }

    protected function expirePendingQrisTransactions(Transaction $transaction): void
    {
        Transaction::query()
            ->where('store_id', $transaction->store_id)
            ->where('id', '!=', $transaction->id)
            ->where('payment_method', 'qris')
            ->where('payment_gateway_status', 'pending')
            ->update([
                'payment_gateway_status' => 'expired',
                'status' => 'cancelled',
            ]);
    }

    /**
     * Process payment using store configuration.
     */
    protected function processPaymentFromStoreConfig(Transaction $transaction, string $method, array $storeConfig, array $enabledMethods): JsonResponse
    {
        if (! in_array($method, $enabledMethods)) {
            return response()->json([
                'success' => false,
                'error' => 'Payment method not enabled',
            ], 400);
        }

        try {
            $gateway = PaymentGatewayFactory::make($storeConfig['provider'], [
                'api_key' => $storeConfig['config']['mayar_api_key'] ?? '',
                'sandbox' => $storeConfig['sandbox'] ?? true,
            ]);

            if ($method === 'qris') {
                $this->expirePendingQrisTransactions($transaction);
                $result = $gateway instanceof MayarGateway
                    ? $gateway->createQRIS((int) $transaction->total, [
                        'app_transaction_id' => (string) $transaction->id,
                        'store_id' => (string) $transaction->store_id,
                        'human_reference' => 'POS-'.$transaction->id,
                    ])
                    : $gateway->createQRIS((int) $transaction->total);

                if ($result['success']) {
                    $expiresAt = now()->addHours(24);
                    $transaction->update([
                        'payment_method' => 'qris',
                        'payment_gateway_provider' => $storeConfig['provider'],
                        'payment_gateway_status' => 'pending',
                        'payment_gateway_qr_string' => $result['qr_string'] ?? null,
                        'payment_gateway_expires_at' => $expiresAt,
                    ]);
                    $result['expires_at'] = $expiresAt->getTimestampMs();
                }

                return response()->json($result);
            }

            if ($method === 'invoice') {
                $result = $gateway->createInvoice([
                    'customer_name' => $transaction->customer->name ?? 'Guest',
                    'customer_email' => $transaction->customer->email ?? null,
                    'customer_phone' => $transaction->customer->phone ?? null,
                    'description' => "Order #{$transaction->id}",
                    'amount' => (int) $transaction->total,
                    'callback_url' => route('payment.callback', ['provider' => $storeConfig['provider']]),
                    'expired_at' => now()->addHours(24),
                    'transaction_id' => $transaction->id,
                    'store_id' => $transaction->store_id,
                    'human_reference' => "POS-{$transaction->id}",
                    'pos_reference' => "POS-{$transaction->id}",
                    'items' => $transaction->items->map(fn ($item) => [
                        'description' => $item->product->name ?? 'Product',
                        'quantity' => $item->quantity,
                        'rate' => (int) $item->price,
                    ])->toArray(),
                ]);

                if ($result['success']) {
                    $transaction->update([
                        'payment_method' => 'invoice',
                        'payment_gateway_provider' => $storeConfig['provider'],
                        'payment_gateway_reference' => $result['reference'],
                        'payment_gateway_transaction_id' => $result['transaction_id'] ?? null,
                        'payment_gateway_status' => 'pending',
                        'payment_gateway_expires_at' => isset($result['expires_at'])
                            ? now()->setTimestamp($result['expires_at'] / 1000)
                            : now()->addHours(24),
                    ]);
                }

                return response()->json($result);
            }

            return response()->json([
                'success' => false,
                'error' => 'Invalid payment method',
            ], 400);

        } catch (\Exception $e) {
            Log::error('Payment from store config failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to initiate payment: '.$e->getMessage(),
            ], 500);
        }
    }
}
