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
                    'category' => 'Sandboxed',
                    'path' => '/some/path/Sandboxed/workshop',
                    'relative_path' => 'Sandboxed/workshop',
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
        $this->mock(MoveProject::class, function ($mock): void {
            $mock->shouldReceive('execute')
                ->once()
                ->with('/some/path/Active/chirper', '/some/path/Archived')
                ->andReturn('/some/path/Archived/chirper');
        });

        $response = $this->postJson('/api/projects/move', [
            'source_path' => '/some/path/Active/chirper',
            'target_path' => '/some/path/Archived',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    });

    it('returns 422 when target_path is missing', function (): void {
        $response = $this->postJson('/api/projects/move', [
            'source_path' => '/some/path/Active/chirper',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['target_path']);
    });

    it('returns 422 when source_path is missing', function (): void {
        $response = $this->postJson('/api/projects/move', [
            'target_path' => '/some/path/Archived',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['source_path']);
    });

    it('returns 422 with error message when MoveProject throws InvalidArgumentException', function (): void {
        $this->mock(MoveProject::class, function ($mock): void {
            $mock->shouldReceive('execute')
                ->once()
                ->andThrow(new InvalidArgumentException('A project named chirper already exists at the target location.'));
        });

        $response = $this->postJson('/api/projects/move', [
            'source_path' => '/some/path/Active/chirper',
            'target_path' => '/some/path/Archived',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'A project named chirper already exists at the target location.',
            ]);
    });

    it('returns 422 when the real MoveProject action rejects an unconfigured target path', function (): void {
        $settingsService = resolve(SettingsService::class);
        $settingsService->set('category_paths', ['Active' => ['/some/path/Active']]);

        $response = $this->postJson('/api/projects/move', [
            'source_path' => '/some/path/Active/chirper',
            'target_path' => '/unauthorized/path',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Target path is not a configured category scan path: /unauthorized/path',
            ]);
    });
});

// ─── DELETE /api/projects ──────────────────────────────────────────

describe('DELETE /api/projects', function (): void {

    it('returns 200 JSON success on a valid delete request', function (): void {
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
                ->with($projectPath);
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
                'error' => 'Project directory does not exist: /non/existent/project/path',
            ]);
    });

    it('returns 422 if project is outside all configured category paths', function (): void {
        $settingsService = resolve(SettingsService::class);
        $settingsService->set('category_paths', ['Active' => ['/some/path']]);

        // Create temp folder outside any configured category path
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
                'error' => "Project path is not inside any configured category scan path: {$projectPath}",
            ]);

        // Clean up
        rmdir($projectPath);
        rmdir(dirname($projectPath));
    });
});
