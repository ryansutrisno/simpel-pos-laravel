<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'printer_device_id',
        'logo_path',
        'receipt_header_message',
        'receipt_footer_message',
        'receipt_tagline',
        'show_cashier_name',
        'show_barcode',
        'show_qr_code',
        'receipt_template_id',
        'receipt_width',
        'return_deadline_days',
        'enable_store_credit',
        'store_credit_expiry_days',
        'store_credit_never_expires',
        'tax_enabled',
        'tax_rate',
        'tax_name',
    ];

    protected function casts(): array
    {
        return [
            'show_cashier_name' => 'boolean',
            'show_barcode' => 'boolean',
            'show_qr_code' => 'boolean',
            'return_deadline_days' => 'integer',
            'enable_store_credit' => 'boolean',
            'store_credit_expiry_days' => 'integer',
            'store_credit_never_expires' => 'boolean',
            'tax_enabled' => 'boolean',
            'tax_rate' => 'decimal:2',
        ];
    }

    public function receiptTemplates(): HasMany
    {
        return $this->hasMany(ReceiptTemplate::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'current_store_id');
    }

    public function productStocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    public function activeReceiptTemplate(): BelongsTo
    {
        return $this->belongsTo(ReceiptTemplate::class, 'receipt_template_id');
    }

    public function paymentGatewayConfig(): HasMany
    {
        return $this->hasMany(PaymentGatewayConfig::class);
    }

    public function activePaymentGatewayConfig(): ?PaymentGatewayConfig
    {
        return $this->paymentGatewayConfig()
            ->where('is_active', true)
            ->first();
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::url($this->logo_path) : null;
    }

    public function getReturnDeadline(): int
    {
        return $this->return_deadline_days ?? 7;
    }

    public function isStoreCreditEnabled(): bool
    {
        return $this->enable_store_credit ?? true;
    }

    public function getStoreCreditExpiryDays(): int
    {
        return $this->store_credit_expiry_days ?? 180;
    }

    public function isStoreCreditNeverExpires(): bool
    {
        return $this->store_credit_never_expires ?? false;
    }

    public function isTaxEnabled(): bool
    {
        return $this->tax_enabled ?? false;
    }

    public function getTaxRate(): float
    {
        return (float) ($this->tax_rate ?? 10.00);
    }

    public function getTaxName(): string
    {
        return $this->tax_name ?? 'PPN';
    }

    public function scopeActive($query)
    {
        return $query->where('id', '>', 0);
    }
}
