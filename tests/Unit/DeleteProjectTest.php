<?php

declare(strict_types=1);

use App\Actions\DeleteProject;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tmpDir = sys_get_temp_dir().'/devportal-delete-test-'.uniqid();
    mkdir($this->tmpDir);
    mkdir($this->tmpDir.'/Active');

    $settingsService = new SettingsService;
    $settingsService->set('allowlisted_paths', [$this->tmpDir]);

    $this->action = new DeleteProject($settingsService);
});

afterEach(function (): void {
    if (is_dir($this->tmpDir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->tmpDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->tmpDir);
    }
});

test('successfully deletes a project directory', function (): void {
    $projectPath = $this->tmpDir.'/Active/chirper';
    mkdir($projectPath);
    file_put_contents($projectPath.'/README.md', '# Chirper');

    $this->action->execute($projectPath);

    expect(is_dir($projectPath))->toBeFalse();
});

test('throws InvalidArgumentException when directory does not exist', function (): void {
    $projectPath = $this->tmpDir.'/Active/nonexistent';

    expect(fn () => $this->action->execute($projectPath))
        ->toThrow(InvalidArgumentException::class, 'Project directory does not exist');
});

test('throws InvalidArgumentException when project category is not allowed', function (): void {
    $projectPath = $this->tmpDir.'/Production/chirper';
    mkdir($this->tmpDir.'/Production');
    mkdir($projectPath);

    expect(fn () => $this->action->execute($projectPath))
        ->toThrow(InvalidArgumentException::class, 'Invalid project category directory structure');
});

test('throws InvalidArgumentException when project is outside allowlisted paths', function (): void {
    $outsideDir = sys_get_temp_dir().'/devportal-delete-outside-'.uniqid();
    mkdir($outsideDir);
    mkdir($outsideDir.'/Active');
    $projectPath = $outsideDir.'/Active/chirper';
    mkdir($projectPath);

    try {
        expect(fn () => $this->action->execute($projectPath))
            ->toThrow(InvalidArgumentException::class, 'not inside any allowlisted scan path');
    } finally {
        rmdir($projectPath);
        rmdir($outsideDir.'/Active');
        rmdir($outsideDir);
    }
});
