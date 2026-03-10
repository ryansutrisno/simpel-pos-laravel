<?php

namespace App\Services;

use App\Models\PaymentGatewayConfig;
use App\Services\PaymentGateway\MayarGateway;
use App\Services\PaymentGateway\PaymentGatewayInterface;

class PaymentGatewayService
{
    private ?PaymentGatewayInterface $gateway = null;

    public function initiatePayment(int $storeId, string $provider, int $amount, string $orderId, array $customerData): array
    {
        $config = $this->getActiveConfig($storeId, $provider);

        if (! $config) {
            return [
                'success' => false,
                'error' => "Payment gateway config for {$provider} not found or inactive",
            ];
        }

        $gateway = $this->getGateway($config);

        if (! $gateway) {
            return [
                'success' => false,
                'error' => "Payment gateway {$provider} not supported",
            ];
        }

        return $gateway->createQRIS($amount);
    }

    public function checkStatus(string $paymentId, string $provider): array
    {
        $config = PaymentGatewayConfig::forProvider($provider)->active()->first();

        if (! $config) {
            return [
                'success' => false,
                'error' => "Payment gateway config for {$provider} not found",
            ];
        }

        $gateway = $this->getGateway($config);

        if (! $gateway) {
            return [
                'success' => false,
                'error' => "Payment gateway {$provider} not supported",
            ];
        }

        return $gateway->checkStatus($paymentId);
    }

    public function handleWebhook(string $provider, array $payload): array
    {
        $config = PaymentGatewayConfig::forProvider($provider)->active()->first();

        if (! $config) {
            return [
                'success' => false,
                'error' => "Payment gateway config for {$provider} not found",
            ];
        }

        $gateway = $this->getGateway($config);

        if (! $gateway) {
            return [
                'success' => false,
                'error' => "Payment gateway {$provider} not supported",
            ];
        }

        return $gateway->handleWebhook($payload);
    }

    public function cancelPayment(string $paymentId, string $provider): bool
    {
        $config = PaymentGatewayConfig::forProvider($provider)->active()->first();

        if (! $config) {
            return false;
        }

        $gateway = $this->getGateway($config);

        if (! $gateway) {
            return false;
        }

        return $gateway->cancelPayment($paymentId);
    }

    public function getActiveConfig(int $storeId, string $provider): ?PaymentGatewayConfig
    {
        return PaymentGatewayConfig::forStore($storeId)
            ->forProvider($provider)
            ->active()
            ->first();
    }

    private function getGateway(PaymentGatewayConfig $config): ?PaymentGatewayInterface
    {
        if ($this->gateway) {
            return $this->gateway;
        }

        $gatewayConfig = [
            'api_key' => $config->api_key,
            'sandbox' => $config->isSandbox(),
            'config' => $config->getGatewayConfig(),
        ];

        $this->gateway = match ($config->provider) {
            PaymentGatewayConfig::PROVIDER_MAYAR => new MayarGateway($gatewayConfig),
            default => null,
        };

        return $this->gateway;
    }
}
