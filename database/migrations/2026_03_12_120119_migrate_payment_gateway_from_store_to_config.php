<?php

use App\Models\PaymentGatewayConfig;
use App\Models\Store;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate payment gateway config from stores table to payment_gateway_configs table
        $stores = Store::where('payment_gateway_enabled', true)->get();

        foreach ($stores as $store) {
            $paymentConfig = is_string($store->payment_config)
                ? json_decode($store->payment_config, true)
                : $store->payment_config;

            $enabledMethods = is_string($store->payment_enabled_methods)
                ? json_decode($store->payment_enabled_methods, true)
                : $store->payment_enabled_methods;

            PaymentGatewayConfig::firstOrCreate(
                [
                    'store_id' => $store->id,
                    'provider' => $store->payment_gateway_provider ?? 'mayar',
                ],
                [
                    'is_active' => true,
                    'is_sandbox' => $store->payment_gateway_sandbox ?? true,
                    'api_key' => $paymentConfig['mayar_api_key'] ?? null,
                    'config' => $paymentConfig ?? [],
                    'provider_config' => [
                        'webhook_url' => $paymentConfig['webhook_url'] ?? null,
                    ],
                    'enabled_methods' => $enabledMethods ?? ['qris'],
                    'webhook_url' => $paymentConfig['webhook_url'] ?? null,
                ]
            );
        }
    }

    public function down(): void
    {
        // Cannot reliably reverse this migration as stores data may have changed
    }
};
