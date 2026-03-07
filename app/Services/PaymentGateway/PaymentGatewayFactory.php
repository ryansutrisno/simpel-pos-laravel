<?php

namespace App\Services\PaymentGateway;

use InvalidArgumentException;

/**
 * Factory class for creating payment gateway instances.
 *
 * This factory instantiates the appropriate payment gateway implementation
 * based on the provider name.
 */
class PaymentGatewayFactory
{
    /**
     * Available payment gateway providers.
     *
     * @var array<string, class-string<PaymentGatewayInterface>>
     */
    protected static array $providers = [
        'mayar' => MayarGateway::class,
        // Future providers can be registered here:
        // 'midtrans' => MidtransGateway::class,
        // 'xendit' => XenditGateway::class,
        // 'tripay' => TripayGateway::class,
    ];

    /**
     * Create a payment gateway instance.
     *
     * @param  string  $provider  The provider name (e.g., 'mayar')
     * @param array{
     *     api_key: string,
     *     sandbox: bool,
     *     config?: array
     * } $config Configuration for the gateway
     *
     * @throws InvalidArgumentException If provider is not supported
     */
    public static function make(string $provider, array $config): PaymentGatewayInterface
    {
        if (! isset(self::$providers[$provider])) {
            throw new InvalidArgumentException(
                "Unsupported payment gateway provider: {$provider}. ".
                'Available providers: '.implode(', ', array_keys(self::$providers))
            );
        }

        $gatewayClass = self::$providers[$provider];

        return new $gatewayClass($config);
    }

    /**
     * Register a new payment gateway provider.
     *
     * @param  string  $name  The provider identifier
     * @param  class-string<PaymentGatewayInterface>  $class  The gateway class
     */
    public static function register(string $name, string $class): void
    {
        self::$providers[$name] = $class;
    }

    /**
     * Get all available provider names.
     *
     * @return array<string>
     */
    public static function availableProviders(): array
    {
        return array_keys(self::$providers);
    }

    /**
     * Check if a provider is supported.
     */
    public static function isSupported(string $provider): bool
    {
        return isset(self::$providers[$provider]);
    }
}
