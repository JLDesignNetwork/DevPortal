<?php

declare(strict_types=1);

use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('GET /api/settings', function (): void {
    it('returns the current settings with default values', function (): void {
        $response = $this->getJson('/api/settings');

        $response->assertStatus(200)
            ->assertJson([
                'cache_enabled' => false,
                'cache_ttl' => 300,
                'categories' => ['Active', 'Archived', 'Sandboxed'],
                'category_paths' => [
                    'Active' => [],
                    'Archived' => [],
                    'Sandboxed' => [],
                ],
                'splash_recent_count' => 5,
                'splash_active_count' => 5,
                'domain_extension' => 'test',
                'default_sort' => 'date-desc',
                'sync_exclude_categories' => ['Sandboxed'],
                'sync_exclude_projects' => [],
                'sync_include_categories' => [],
                'sync_include_projects' => [],
                'entry_exclude_categories' => ['Archived'],
                'entry_exclude_projects' => [],
                'entry_include_categories' => [],
                'entry_include_projects' => [],
            ]);
    });
});

describe('POST /api/settings', function (): void {
    it('updates settings successfully with valid parameters', function (): void {
        // Use real existing paths (like base_path() and app_path()) to satisfy File::isDirectory
        $validCategoryPaths = [
            'Active' => [base_path()],
            'Archived' => [app_path()],
        ];

        $response = $this->postJson('/api/settings', [
            'cache_enabled' => true,
            'cache_ttl' => 600,
            'category_paths' => $validCategoryPaths,
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
            ->and($settingsService->getCategoryPaths())->toBe([
                'Active' => [base_path()],
                'Archived' => [app_path()],
                'Sandboxed' => [],
            ])
            ->and($settingsService->getSplashRecentCount())->toBe(10)
            ->and($settingsService->getSplashActiveCount())->toBe(8)
            ->and($settingsService->getDomainExtension())->toBe('local')
            ->and($settingsService->getDefaultSort())->toBe('alpha-asc');
    });

    it('returns 422 if a configured category path does not exist on disk', function (): void {
        $response = $this->postJson('/api/settings', [
            'cache_enabled' => false,
            'cache_ttl' => 300,
            'category_paths' => ['Active' => ['/non/existent/directory/path/here']],
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

    it('returns 422 for an unknown category key', function (): void {
        $response = $this->postJson('/api/settings', [
            'cache_enabled' => false,
            'cache_ttl' => 300,
            'category_paths' => ['Production' => [base_path()]],
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
                'error' => 'Unknown project category: "Production".',
            ]);
    });

    it('validates required fields', function (): void {
        $response = $this->postJson('/api/settings', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cache_enabled', 'cache_ttl', 'category_paths', 'splash_recent_count', 'splash_active_count', 'domain_extension']);
    });

    it('validates field types', function (): void {
        $response = $this->postJson('/api/settings', [
            'cache_enabled' => 'not-a-boolean',
            'cache_ttl' => -5,
            'category_paths' => 'not-an-array',
            'splash_recent_count' => 0,
            'splash_active_count' => 0,
            'domain_extension' => 'invalid@tld',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cache_enabled', 'cache_ttl', 'category_paths', 'splash_recent_count', 'splash_active_count', 'domain_extension']);
    });
});
