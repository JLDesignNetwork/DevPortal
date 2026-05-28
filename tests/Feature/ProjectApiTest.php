<?php

declare(strict_types=1);

use App\Actions\DeleteProject;
use App\Actions\MoveProject;
use App\Services\ProjectScanner;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── GET /api/projects ─────────────────────────────────────────────

describe('GET /api/projects', function (): void {

    it('returns HTTP 200 with JSON content type', function (): void {
        // Mock the scanner to return a predictable list
        $this->mock(ProjectScanner::class, function ($mock): void {
            $mock->shouldReceive('scan')->once()->andReturn([
                [
                    'name' => 'Chirper',
                    'version' => '1.0.0',
                    'production_version' => '1.0.0',
                    'description' => 'A microblogging platform.',
                    'category' => 'Active',
                    'path' => '/some/path/Active/chirper',
                    'relative_path' => 'Active/chirper',
                    'last_modified' => '2 hours ago',
                    'last_modified_timestamp' => now()->timestamp,
                    'created_at' => '3 days ago',
                    'created_at_timestamp' => now()->subDays(3)->timestamp,
                    'features' => ['Auth', 'Tweets'],
                    'dependencies' => ['composer' => ['php' => '^8.2']],
                    'git_commits' => [],
                    'changelog_version' => '1.0.0',
                    'changelog_date' => '2025-01-01',
                    'changelog_content' => '- Initial release',
                    'git_branch' => 'main',
                    'git_dirty_count' => 0,
                ],
            ]);
        });

        $response = $this->getJson('/api/projects');

        $response->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonCount(1)
            ->assertJsonFragment(['name' => 'Chirper'])
            ->assertJsonFragment(['category' => 'Active']);
    });

    it('returns an empty array when no projects found', function (): void {
        $this->mock(ProjectScanner::class, function ($mock): void {
            $mock->shouldReceive('scan')->once()->andReturn([]);
        });

        $response = $this->getJson('/api/projects');

        $response->assertStatus(200)->assertExactJson([]);
    });

    it('includes all expected project fields in each result', function (): void {
        $this->mock(ProjectScanner::class, function ($mock): void {
            $mock->shouldReceive('scan')->once()->andReturn([
                [
                    'name' => 'Workshop',
                    'version' => 'N/A',
                    'production_version' => 'N/A',
                    'description' => null,
                    'category' => 'Sandbox',
                    'path' => '/some/path/Sandbox/workshop',
                    'relative_path' => 'Sandbox/workshop',
                    'last_modified' => '5 minutes ago',
                    'last_modified_timestamp' => now()->timestamp,
                    'created_at' => '5 minutes ago',
                    'created_at_timestamp' => now()->timestamp,
                    'features' => [],
                    'dependencies' => [],
                    'git_commits' => [],
                    'changelog_version' => null,
                    'changelog_date' => null,
                    'changelog_content' => null,
                    'git_branch' => null,
                    'git_dirty_count' => null,
                ],
            ]);
        });

        $response = $this->getJson('/api/projects');

        $response->assertStatus(200)->assertJsonStructure([
            '*' => [
                'name', 'version', 'production_version', 'description', 'category', 'path',
                'relative_path', 'last_modified', 'last_modified_timestamp',
                'created_at', 'created_at_timestamp', 'features', 'dependencies', 'git_commits',
                'changelog_version', 'changelog_date', 'changelog_content',
                'git_branch', 'git_dirty_count',
            ],
        ]);
    });
});

// ─── POST /api/projects/move ──────────────────────────────────────

describe('POST /api/projects/move', function (): void {

    it('returns 200 JSON success on a valid move request', function (): void {
        $settingsService = resolve(SettingsService::class);
        $settingsService->set('allowlisted_paths', ['/some/path']);

        $this->mock(MoveProject::class, function ($mock): void {
            $mock->shouldReceive('execute')
                ->once()
                ->withArgs(fn ($src, $base, $cat): bool => $src === '/some/path/Active/chirper' && $base === '/some/path' && $cat === 'Archive')
                ->andReturn('/some/path/Archive/chirper');
        });

        $response = $this->postJson('/api/projects/move', [
            'source_path' => '/some/path/Active/chirper',
            'target_base_path' => '/some/path',
            'target_category' => 'Archive',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    });

    it('returns 422 when target_category is missing', function (): void {
        $response = $this->postJson('/api/projects/move', [
            'source_path' => '/some/path/Active/chirper',
            'target_base_path' => '/some/path',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['target_category']);
    });

    it('returns 422 when source_path or target_base_path is missing', function (): void {
        $response = $this->postJson('/api/projects/move', [
            'target_category' => 'Archive',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['source_path', 'target_base_path']);
    });

    it('returns 422 if target_base_path is not allowlisted', function (): void {
        $settingsService = resolve(SettingsService::class);
        $settingsService->set('allowlisted_paths', ['/some/path']);

        $response = $this->postJson('/api/projects/move', [
            'source_path' => '/some/path/Active/chirper',
            'target_base_path' => '/unauthorized/path',
            'target_category' => 'Archive',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'The target location is not in the allowlisted scan paths.',
            ]);
    });

    it('returns 422 if source_path is not inside allowlisted paths', function (): void {
        $settingsService = resolve(SettingsService::class);
        $settingsService->set('allowlisted_paths', ['/some/path']);

        $response = $this->postJson('/api/projects/move', [
            'source_path' => '/unauthorized/path/Active/chirper',
            'target_base_path' => '/some/path',
            'target_category' => 'Archive',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'The source path is not inside any allowlisted scan paths.',
            ]);
    });

    it('returns 422 with error message when MoveProject throws InvalidArgumentException', function (): void {
        $settingsService = resolve(SettingsService::class);
        $settingsService->set('allowlisted_paths', ['/some/path']);

        $this->mock(MoveProject::class, function ($mock): void {
            $mock->shouldReceive('execute')
                ->once()
                ->andThrow(new InvalidArgumentException('A project named chirper already exists at the target location.'));
        });

        $response = $this->postJson('/api/projects/move', [
            'source_path' => '/some/path/Active/chirper',
            'target_base_path' => '/some/path',
            'target_category' => 'Archive',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'A project named chirper already exists at the target location.',
            ]);
    });
});

// ─── DELETE /api/projects ──────────────────────────────────────────

describe('DELETE /api/projects', function (): void {

    it('returns 200 JSON success on a valid delete request', function (): void {
        $settingsService = resolve(SettingsService::class);
        $settingsService->set('allowlisted_paths', [sys_get_temp_dir()]);

        // Create a temporary project path
        $projectPath = sys_get_temp_dir().'/Active/chirper';
        if (! is_dir(dirname($projectPath))) {
            mkdir(dirname($projectPath), 0755, true);
        }
        if (! is_dir($projectPath)) {
            mkdir($projectPath);
        }

        $this->mock(DeleteProject::class, function ($mock) use ($projectPath): void {
            $mock->shouldReceive('execute')
                ->once()
                ->with(realpath($projectPath));
        });

        $response = $this->deleteJson('/api/projects', [
            'path' => $projectPath,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Clean up
        rmdir($projectPath);
        rmdir(dirname($projectPath));
    });

    it('returns 422 when path is missing', function (): void {
        $response = $this->deleteJson('/api/projects', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['path']);
    });

    it('returns 422 if project path does not exist', function (): void {
        $response = $this->deleteJson('/api/projects', [
            'path' => '/non/existent/project/path',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'The project directory does not exist.',
            ]);
    });

    it('returns 422 if project is outside allowlisted paths', function (): void {
        $settingsService = resolve(SettingsService::class);
        $settingsService->set('allowlisted_paths', ['/some/path']);

        // Create temp folder outside allowlisted
        $projectPath = sys_get_temp_dir().'/Active/unauthorized';
        if (! is_dir(dirname($projectPath))) {
            mkdir(dirname($projectPath), 0755, true);
        }
        if (! is_dir($projectPath)) {
            mkdir($projectPath);
        }

        $response = $this->deleteJson('/api/projects', [
            'path' => $projectPath,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'The project is not located in any allowlisted scan paths.',
            ]);

        // Clean up
        rmdir($projectPath);
        rmdir(dirname($projectPath));
    });

    it('returns 422 if project directory has invalid category structure', function (): void {
        $settingsService = resolve(SettingsService::class);
        $settingsService->set('allowlisted_paths', [sys_get_temp_dir()]);

        // Invalid category 'Production'
        $projectPath = sys_get_temp_dir().'/Production/chirper';
        if (! is_dir(dirname($projectPath))) {
            mkdir(dirname($projectPath), 0755, true);
        }
        if (! is_dir($projectPath)) {
            mkdir($projectPath);
        }

        $response = $this->deleteJson('/api/projects', [
            'path' => $projectPath,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Invalid project category directory structure.',
            ]);

        // Clean up
        rmdir($projectPath);
        rmdir(dirname($projectPath));
    });
});
