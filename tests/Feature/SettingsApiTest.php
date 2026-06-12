<?php

declare(strict_types=1);

use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

describe('GET /api/settings', function (): void {
    it('returns the current settings with default values', function (): void {
        $response = $this->getJson('/api/settings');

        $response->assertStatus(200)
            ->assertJson([
                'cache_enabled' => false,
                'cache_ttl' => 300,
                'allowlisted_paths' => [base_path('../')],
                'splash_recent_count' => 5,
                'splash_active_count' => 5,
                'domain_extension' => 'test',
                'default_sort' => 'date-desc',
                'sync_exclude_categories' => ['Sandbox'],
                'sync_exclude_projects' => [],
                'sync_include_categories' => [],
                'sync_include_projects' => [],
                'entry_exclude_categories' => ['Archive'],
                'entry_exclude_projects' => [],
                'entry_include_categories' => [],
                'entry_include_projects' => [],
            ]);
    });
});

describe('POST /api/settings', function (): void {
    it('updates settings successfully with valid parameters', function (): void {
        // Use real existing paths (like base_path() and app_path()) to satisfy File::isDirectory
        $validPaths = [base_path(), app_path()];

        $response = $this->postJson('/api/settings', [
            'cache_enabled' => true,
            'cache_ttl' => 600,
            'allowlisted_paths' => $validPaths,
            'splash_recent_count' => 10,
            'splash_active_count' => 8,
            'domain_extension' => 'local',
            'default_sort' => 'alpha-asc',
            'sync_exclude_categories' => [],
            'sync_exclude_projects' => [],
            'sync_include_categories' => [],
            'sync_include_projects' => [],
            'entry_exclude_categories' => [],
            'entry_exclude_projects' => [],
            'entry_include_categories' => [],
            'entry_include_projects' => [],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Settings updated successfully.',
            ]);

        // Assert they are persisted in settings service
        $settingsService = resolve(SettingsService::class);
        expect($settingsService->isCacheEnabled())->toBeTrue()
            ->and($settingsService->getCacheTtl())->toBe(600)
            ->and($settingsService->getAllowlistedPaths())->toBe($validPaths)
            ->and($settingsService->getSplashRecentCount())->toBe(10)
            ->and($settingsService->getSplashActiveCount())->toBe(8)
            ->and($settingsService->getDomainExtension())->toBe('local')
            ->and($settingsService->getDefaultSort())->toBe('alpha-asc');
    });

    it('returns 422 if allowlisted path does not exist on disk', function (): void {
        $invalidPaths = ['/non/existent/directory/path/here'];

        $response = $this->postJson('/api/settings', [
            'cache_enabled' => false,
            'cache_ttl' => 300,
            'allowlisted_paths' => $invalidPaths,
            'splash_recent_count' => 5,
            'splash_active_count' => 5,
            'domain_extension' => 'test',
            'default_sort' => 'date-desc',
            'sync_exclude_categories' => [],
            'sync_exclude_projects' => [],
            'sync_include_categories' => [],
            'sync_include_projects' => [],
            'entry_exclude_categories' => [],
            'entry_exclude_projects' => [],
            'entry_include_categories' => [],
            'entry_include_projects' => [],
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'The directory "/non/existent/directory/path/here" does not exist on this machine.',
            ]);
    });

    it('validates required fields', function (): void {
        $response = $this->postJson('/api/settings', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cache_enabled', 'cache_ttl', 'allowlisted_paths', 'splash_recent_count', 'splash_active_count', 'domain_extension']);
    });

    it('validates field types', function (): void {
        $response = $this->postJson('/api/settings', [
            'cache_enabled' => 'not-a-boolean',
            'cache_ttl' => -5,
            'allowlisted_paths' => 'not-an-array',
            'splash_recent_count' => 0,
            'splash_active_count' => 0,
            'domain_extension' => 'invalid@tld',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cache_enabled', 'cache_ttl', 'allowlisted_paths', 'splash_recent_count', 'splash_active_count', 'domain_extension']);
    });
});
