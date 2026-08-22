<?php

/**
 * @since 2605.2.0-bs
 *
 * @version 2605.4.1-bs
 */

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
     * Get the configured scan paths per category (e.g. "Active" => ["/Users/.../Sites/Active"]).
     * Every allowed category is always present as a key, defaulting to an empty array
     * when the user hasn't configured a location for it yet.
     *
     * @return array<string, array<int, string>>
     */
    public function getCategoryPaths(): array
    {
        $val = $this->get('category_paths');
        $decoded = $val !== null ? json_decode((string) $val, true) : null;
        if (! is_array($decoded)) {
            $decoded = [];
        }

        $result = [];
        foreach ($this->getAllowedCategories() as $category) {
            $paths = $decoded[$category] ?? [];
            $result[$category] = is_array($paths) ? array_values(array_filter($paths, 'is_string')) : [];
        }

        return $result;
    }

    /**
     * Get the configured scan paths for a single category.
     *
     * @return array<int, string>
     */
    public function getCategoryPathsFor(string $category): array
    {
        return $this->getCategoryPaths()[$category] ?? [];
    }

    /**
     * Get the allowed project category names.
     *
     * @return array<int, string>
     */
    public function getAllowedCategories(): array
    {
        $val = $this->get('allowed_categories');
        if ($val === null) {
            return ['Active', 'Archived', 'Sandboxed'];
        }

        $decoded = json_decode((string) $val, true);
        if (! is_array($decoded) || $decoded === []) {
            return ['Active', 'Archived', 'Sandboxed'];
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

    /**
     * Get the default sort mode.
     */
    public function getDefaultSort(): string
    {
        $val = $this->get('default_sort');

        return $val !== null ? (string) $val : 'date-desc';
    }

    /**
     * Get a setting value as an array.
     *
     * @param  array<int, string>  $default
     * @return array<int, string>
     */
    public function getArray(string $key, array $default = []): array
    {
        $val = $this->get($key);
        if ($val === null) {
            return $default;
        }

        $decoded = json_decode((string) $val, true);
        if (! is_array($decoded)) {
            return $default;
        }

        return $decoded;
    }
}
