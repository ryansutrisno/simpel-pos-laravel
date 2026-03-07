<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class PaymentGatewayConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'provider',
        'is_active',
        'is_sandbox',
        'api_key',
        'config',
        'enabled_methods',
        'webhook_url',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_sandbox' => 'boolean',
            'config' => 'array',
            'enabled_methods' => 'array',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function setApiKeyAttribute(?string $value): void
    {
        if ($value) {
            $this->attributes['api_key'] = Crypt::encryptString($value);
        } else {
            $this->attributes['api_key'] = null;
        }
    }

    public function getApiKeyAttribute(?string $value): ?string
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function isEnabled(): bool
    {
        return $this->is_active;
    }

    public function isSandbox(): bool
    {
        return $this->is_sandbox;
    }

    public function getGatewayConfig(): array
    {
        return [
            'api_key' => $this->api_key,
            'sandbox' => $this->is_sandbox,
            'config' => $this->config ?? [],
        ];
    }
}
