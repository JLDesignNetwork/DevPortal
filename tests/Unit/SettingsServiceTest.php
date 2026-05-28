<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = new SettingsService;
});

test('get returns default value when setting does not exist', function (): void {
    expect($this->service->get('nonexistent', 'default-value'))->toBe('default-value');
});

test('set creates or updates a setting and get retrieves it', function (): void {
    $this->service->set('my_key', 'my_value');

    expect($this->service->get('my_key'))->toBe('my_value');
    expect(Setting::where('key', 'my_key')->first()->value)->toBe('my_value');

    // Update
    $this->service->set('my_key', 'new_value');
    expect($this->service->get('my_key'))->toBe('new_value');
});

test('isCacheEnabled returns boolean defaults and custom values', function (): void {
    expect($this->service->isCacheEnabled())->toBeFalse();

    $this->service->set('cache_enabled', 'true');
    expect($this->service->isCacheEnabled())->toBeTrue();

    $this->service->set('cache_enabled', 'false');
    expect($this->service->isCacheEnabled())->toBeFalse();

    $this->service->set('cache_enabled', '1');
    expect($this->service->isCacheEnabled())->toBeTrue();
});

test('getCacheTtl returns integer defaults and custom values', function (): void {
    expect($this->service->getCacheTtl())->toBe(300);

    $this->service->set('cache_ttl', '600');
    expect($this->service->getCacheTtl())->toBe(600);
});

test('getAllowlistedPaths returns array default and custom values', function (): void {
    expect($this->service->getAllowlistedPaths())->toBe([base_path('../')]);

    $customPaths = ['/Users/jeff/Sites', '/Users/jeff/Projects'];
    $this->service->set('allowlisted_paths', $customPaths);

    expect($this->service->getAllowlistedPaths())->toBe($customPaths);
});

test('getSplashRecentCount returns default and custom values', function (): void {
    expect($this->service->getSplashRecentCount())->toBe(5);

    $this->service->set('splash_recent_count', 10);
    expect($this->service->getSplashRecentCount())->toBe(10);
});

test('getSplashActiveCount returns default and custom values', function (): void {
    expect($this->service->getSplashActiveCount())->toBe(5);

    $this->service->set('splash_active_count', 8);
    expect($this->service->getSplashActiveCount())->toBe(8);
});

test('getDomainExtension returns default and custom values', function (): void {
    expect($this->service->getDomainExtension())->toBe('test');

    $this->service->set('domain_extension', 'local');
    expect($this->service->getDomainExtension())->toBe('local');
});
