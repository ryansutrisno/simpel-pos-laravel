<?php

namespace App\Services;

use App\Models\AppSettings;
use Illuminate\Support\Facades\Cache;

class AppSettingsService
{
    private const CACHE_KEY = 'app_settings';

    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get a setting value by key with caching
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->getCachedSettings();

        return $settings[$key] ?? $default;
    }

    /**
     * Set a setting value by key and clear cache
     */
    public function set(string $key, mixed $value): void
    {
        AppSettings::set($key, $value);
        $this->clearCache();
    }

    /**
     * Get all settings as an array
     */
    public function all(): array
    {
        return $this->getCachedSettings();
    }

    /**
     * Get application name
     */
    public function getAppName(): string
    {
        return $this->get('app_name', 'Simpel POS');
    }

    /**
     * Get timezone
     */
    public function getTimezone(): string
    {
        return $this->get('timezone', 'Asia/Jakarta');
    }

    /**
     * Check if maintenance mode is enabled
     */
    public function isMaintenanceMode(): bool
    {
        return $this->get('maintenance_mode', false);
    }

    /**
     * Get cached settings or load from database
     */
    private function getCachedSettings(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return AppSettings::getInstance()->toArray();
        });
    }

    /**
     * Clear the settings cache
     */
    private function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
