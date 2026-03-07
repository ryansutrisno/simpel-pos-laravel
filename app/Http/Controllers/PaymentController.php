<?php

namespace App\Http\Controllers;

use App\Models\PaymentGatewayConfig;
use App\Models\Store;
use App\Models\Transaction;
use App\Services\PaymentGateway\PaymentGatewayFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
                // Generate QRIS
                $result = $gateway->createQRIS((int) $transaction->total);

                if ($result['success']) {
                    $transaction->update([
                        'payment_method' => 'qris',
                        'payment_gateway_provider' => $config->provider,
                        'payment_gateway_status' => 'pending',
                    ]);
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
                if ($transaction->payment_method === 'qris' && ! $transaction->payment_gateway_reference) {
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
            if ($transaction->payment_method === 'qris' && ! $transaction->payment_gateway_reference) {
                return response()->json([
                    'success' => true,
                    'status' => $transaction->payment_gateway_status ?? 'pending',
                    'expires_at' => $transaction->payment_gateway_expires_at,
                ]);
            }

            if ($transaction->payment_gateway_reference) {
                $result = $gateway->checkStatus($transaction->payment_gateway_reference);

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
    public function handleMayarWebhook(Request $request): JsonResponse
    {
        Log::info('Mayar webhook received', [
            'payload' => $request->all(),
        ]);

        try {
            $config = PaymentGatewayConfig::where('provider', 'mayar')
                ->where('is_active', true)
                ->first();

            if (! $config) {
                Log::warning('Mayar webhook received but no active config', [
                    'payload' => $request->all(),
                ]);

                return response()->json(['error' => 'Config not found'], 404);
            }

            $gateway = PaymentGatewayFactory::make('mayar', $config->getGatewayConfig());
            $data = $gateway->handleWebhook($request->all());

            // Find transaction
            $transaction = null;

            // Try by reference first
            if ($data['reference']) {
                $transaction = Transaction::where('payment_gateway_reference', $data['reference'])
                    ->first();
            }

            // Try by external reference (transaction_id in extraData)
            if (! $transaction && $data['external_reference']) {
                $transaction = Transaction::find($data['external_reference']);
            }

            if (! $transaction) {
                Log::warning('Transaction not found for webhook', [
                    'reference' => $data['reference'],
                    'external_reference' => $data['external_reference'],
                ]);

                return response()->json(['error' => 'Transaction not found'], 404);
            }

            // Update transaction
            $updateData = [
                'payment_gateway_status' => $data['status'],
            ];

            if ($data['status'] === 'paid') {
                $updateData['status'] = 'completed';
                $updateData['paid_at'] = $data['paid_at'] ?? now();
            } elseif ($data['status'] === 'expired') {
                $updateData['status'] = 'cancelled';
            }

            $transaction->update($updateData);

            Log::info('Payment status updated via webhook', [
                'transaction_id' => $transaction->id,
                'status' => $data['status'],
            ]);

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
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
                $result = $gateway->createQRIS((int) $transaction->total);

                if ($result['success']) {
                    $transaction->update([
                        'payment_method' => 'qris',
                        'payment_gateway_provider' => $storeConfig['provider'],
                        'payment_gateway_status' => 'pending',
                    ]);
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
