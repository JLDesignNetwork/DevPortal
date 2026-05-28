<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;

class SettingsService
{
    /**
     * Get a setting by key, falling back to a default value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = Setting::where('key', $key)->first();

        return $setting !== null ? $setting->value : $default;
    }

    /**
     * Set a setting value by key.
     */
    public function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => is_scalar($value) || $value === null ? (string) $value : json_encode($value, JSON_THROW_ON_ERROR)]
        );
    }

    /**
     * Determine if caching is enabled.
     */
    public function isCacheEnabled(): bool
    {
        $val = $this->get('cache_enabled');
        if ($val === null) {
            return false;
        }

        return filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get the cache TTL in seconds.
     */
    public function getCacheTtl(): int
    {
        $val = $this->get('cache_ttl');

        return $val !== null ? (int) $val : 300;
    }

    /**
     * Get the allowlisted directory paths to scan.
     *
     * @return array<int, string>
     */
    public function getAllowlistedPaths(): array
    {
        $val = $this->get('allowlisted_paths');
        if ($val === null) {
            return [base_path('../')];
        }

        $decoded = json_decode((string) $val, true);
        if (! is_array($decoded)) {
            return [base_path('../')];
        }

        return $decoded;
    }

    /**
     * Get the splash page recent projects count.
     */
    public function getSplashRecentCount(): int
    {
        $val = $this->get('splash_recent_count');

        return $val !== null ? (int) $val : 5;
    }

    /**
     * Get the splash page active projects count.
     */
    public function getSplashActiveCount(): int
    {
        $val = $this->get('splash_active_count');

        return $val !== null ? (int) $val : 5;
    }

    /**
     * Get the domain extension for local projects (e.g., test, local).
     */
    public function getDomainExtension(): string
    {
        $val = $this->get('domain_extension');

        return $val !== null ? (string) $val : 'test';
    }
}
