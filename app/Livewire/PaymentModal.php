<?php

namespace App\Livewire;

use App\Models\Transaction;
use Livewire\Component;

class PaymentModal extends Component
{
    public bool $isOpen = false;

    public ?Transaction $transaction = null;

    public string $paymentMethod = 'qris';

    public ?string $qrImageUrl = null;

    public ?string $paymentUrl = null;

    public string $status = 'pending';

    public ?string $error = null;

    public bool $isLoading = false;

    public ?string $expiresAt = null;

    protected $listeners = [
        'openPaymentModal' => 'open',
        'paymentReceived' => 'handlePaymentReceived',
    ];

    public function open(array $data): void
    {
        $transactionId = $data['transactionId'] ?? $data['transaction_id'] ?? null;
        $method = $data['method'] ?? 'qris';

        if (! $transactionId) {
            $this->error = 'Transaction ID not provided';

            return;
        }

        $this->transaction = Transaction::find($transactionId);
        $this->paymentMethod = $method;

        if (! $this->transaction) {
            $this->error = 'Transaction not found';

            return;
        }

        $this->paymentMethod = $method;
        $this->resetState();
        $this->isOpen = true;
        $this->initiatePayment();
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->resetState();
    }

    public function resetState(): void
    {
        $this->qrImageUrl = null;
        $this->paymentUrl = null;
        $this->status = 'pending';
        $this->error = null;
        $this->isLoading = false;
        $this->expiresAt = null;
    }

    public function initiatePayment(): void
    {
        if (! $this->transaction) {
            return;
        }

        $this->isLoading = true;
        $this->error = null;

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Accept' => 'application/json',
                'X-CSRF-TOKEN' => csrf_token(),
            ])->post(route('api.payments.initiate', $this->transaction), [
                'method' => $this->paymentMethod,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['success'] ?? false) {
                    if ($this->paymentMethod === 'qris') {
                        $this->qrImageUrl = $data['qr_image_url'] ?? null;
                    } else {
                        $this->paymentUrl = $data['payment_url'] ?? null;
                    }
                    $this->status = 'pending';
                    $this->expiresAt = $data['expires_at'] ?? null;
                } else {
                    $this->error = $data['error'] ?? 'Failed to initiate payment';
                }
            } else {
                $this->error = 'Failed to connect to payment service';
            }
        } catch (\Exception $e) {
            $this->error = 'An error occurred: '.$e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    public function checkStatus(): void
    {
        if (! $this->transaction || $this->status === 'paid' || $this->status === 'expired') {
            return;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Accept' => 'application/json',
            ])->get(route('api.payments.status', $this->transaction));

            if ($response->successful()) {
                $data = $response->json();

                if ($data['success'] ?? false) {
                    $this->status = $data['status'] ?? 'pending';

                    if ($this->status === 'paid') {
                        $this->handlePaymentSuccess();
                    } elseif ($this->status === 'expired') {
                        $this->handlePaymentExpired();
                    }
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Payment status check failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function handlePaymentSuccess(): void
    {
        $this->dispatch('paymentSuccess', $this->transaction->id);
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Pembayaran berhasil!',
        ]);
    }

    public function handlePaymentExpired(): void
    {
        $this->dispatch('notify', [
            'type' => 'warning',
            'message' => 'Pembayaran kadaluarsa. Silakan coba lagi.',
        ]);
    }

    public function handlePaymentReceived(array $data): void
    {
        if ($this->transaction && $this->transaction->id === ($data['transaction_id'] ?? null)) {
            $this->status = 'paid';
            $this->handlePaymentSuccess();
        }
    }

    public function retry(): void
    {
        $this->initiatePayment();
    }

    public function printReceipt(): void
    {
        $this->dispatch('printReceipt', $this->transaction->id);
    }

    public function render()
    {
        return view('livewire.payment-modal');
    }
}
