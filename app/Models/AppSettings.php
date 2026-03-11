<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSettings extends Model
{
    use HasFactory;

    protected $table = 'app_settings';

    protected $fillable = [
        'app_name',
        'timezone',
        'date_format',
        'time_format',
        'currency',
        'currency_format',
        'email_from',
        'email_from_name',
        'maintenance_mode',
    ];

    protected function casts(): array
    {
        return [
            'maintenance_mode' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('app.settings');
        });

        static::updated(function () {
            Cache::forget('app.settings');
        });
    }

    public static function getInstance(): self
    {
        return self::firstOrCreate(
            ['id' => 1],
            [
                'app_name' => 'Simpel POS',
                'timezone' => 'Asia/Jakarta',
                'date_format' => 'd/m/Y',
                'time_format' => 'H:i',
                'currency' => 'IDR',
                'currency_format' => 'id_ID',
                'email_from' => null,
                'email_from_name' => null,
                'maintenance_mode' => false,
            ]
        );
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $instance = self::getInstance();

        return $instance->$key ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $instance = self::getInstance();
        $instance->$key = $value;
        $instance->save();
    }

    public static function allSettings(): array
    {
        $instance = self::getInstance();

        return $instance->toArray();
    }
}
