<?php

namespace App\Services\PaymentGateway;

/**
 * Interface for payment gateway implementations.
 *
 * This interface defines the contract that all payment gateway providers
 * must implement to be used within the POS system.
 */
interface PaymentGatewayInterface
{
    /**
     * Create a QRIS payment code.
     *
     * @param  int  $amount  Amount in IDR (minimum 1000)
     * @return array{
     *     success: bool,
     *     qr_image_url?: string,
     *     amount?: int,
     *     error?: string
     * }
     */
    public function createQRIS(int $amount): array;

    /**
     * Create an invoice with customer details.
     *
     * @param array{
     *     customer_name: string,
     *     customer_email?: string,
     *     customer_phone?: string,
     *     description: string,
     *     amount: int,
     *     callback_url: string,
     *     expired_at: \DateTime,
     *     transaction_id: string|int,
     *     pos_reference: string,
     *     items: array<int, array{description: string, quantity: int, rate: int}>
     * } $data
     * @return array{
     *     success: bool,
     *     payment_url?: string,
     *     reference?: string,
     *     transaction_id?: string,
     *     expires_at?: int,
     *     error?: string
     * }
     */
    public function createInvoice(array $data): array;

    /**
     * Check the status of a payment.
     *
     * @param  string  $reference  The payment reference/invoice ID
     * @return array{
     *     success: bool,
     *     status?: 'pending'|'paid'|'expired'|'failed',
     *     paid_at?: ?string,
     *     error?: string
     * }
     */
    public function checkStatus(string $reference): array;

    /**
     * Handle webhook callback from the payment gateway.
     *
     * @param  array  $data  The webhook payload
     * @return array{
     *     reference: string,
     *     status: 'pending'|'paid'|'expired'|'failed',
     *     amount?: int,
     *     payment_method?: string,
     *     external_reference?: string|null,
     *     paid_at?: ?string
     * }
     */
    public function handleWebhook(array $data): array;

    /**
     * Cancel a pending payment.
     *
     * @param  string  $reference  The payment reference/invoice ID
     * @return bool True if successfully cancelled
     */
    public function cancelPayment(string $reference): bool;

    /**
     * Get the provider name.
     *
     * @return string The provider identifier (e.g., 'mayar', 'midtrans')
     */
    public function getProviderName(): string;
}
