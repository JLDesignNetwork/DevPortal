<?php

declare(strict_types=1);

use App\Actions\MoveProject;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tmpDir = sys_get_temp_dir().'/devportal-move-test-'.uniqid();
    mkdir($this->tmpDir);
    mkdir($this->tmpDir.'/Active');
    mkdir($this->tmpDir.'/Archived');
    mkdir($this->tmpDir.'/Sandboxed');

    $settingsService = new SettingsService;
    $settingsService->set('allowlisted_paths', [$this->tmpDir]);

    $this->action = new MoveProject($settingsService);
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

// ─── Valid Move Tests ─────────────────────────────────────────────

test('successfully moves a project directory to a different category and base path', function (): void {
    $sourcePath = $this->tmpDir.'/Active/chirper';
    mkdir($sourcePath);
    file_put_contents($sourcePath.'/README.md', '# Chirper');

    $result = $this->action->execute($sourcePath, $this->tmpDir, 'Archived');

    expect(is_dir($sourcePath))->toBeFalse();
    expect(is_dir($this->tmpDir.'/Archived/chirper'))->toBeTrue();
    expect($result)->toBe($this->tmpDir.'/Archived/chirper');
});

test('returns the source path without error when moving to same category and base path', function (): void {
    $sourcePath = $this->tmpDir.'/Active/chirper';
    mkdir($sourcePath);

    $result = $this->action->execute($sourcePath, $this->tmpDir, 'Active');

    expect(is_dir($sourcePath))->toBeTrue();
    expect($result)->toBe($sourcePath);
});

// ─── Validation Tests ─────────────────────────────────────────────

test('throws InvalidArgumentException for invalid target category', function (): void {
    $sourcePath = $this->tmpDir.'/Active/chirper';
    mkdir($sourcePath);

    expect(fn () => $this->action->execute($sourcePath, $this->tmpDir, 'Production'))
        ->toThrow(InvalidArgumentException::class, 'Invalid target category');
});

test('throws InvalidArgumentException when source project directory does not exist', function (): void {
    $sourcePath = $this->tmpDir.'/Active/nonexistent';

    expect(fn () => $this->action->execute($sourcePath, $this->tmpDir, 'Archived'))
        ->toThrow(InvalidArgumentException::class, 'Source project directory does not exist');
});

// ─── Collision Prevention ─────────────────────────────────────────

test('throws InvalidArgumentException when destination directory already exists', function (): void {
    $sourcePath = $this->tmpDir.'/Active/chirper';
    mkdir($sourcePath);
    mkdir($this->tmpDir.'/Archived/chirper');  // Pre-existing collision

    expect(fn () => $this->action->execute($sourcePath, $this->tmpDir, 'Archived'))
        ->toThrow(InvalidArgumentException::class, 'already exists at the target location');
});

// ─── Containment Validation ────────────────────────────────────────

test('throws InvalidArgumentException when target base path is not allowlisted', function (): void {
    $sourcePath = $this->tmpDir.'/Active/chirper';
    mkdir($sourcePath);

    $outsideDir = sys_get_temp_dir().'/devportal-move-outside-'.uniqid();
    mkdir($outsideDir);

    try {
        expect(fn () => $this->action->execute($sourcePath, $outsideDir, 'Archived'))
            ->toThrow(InvalidArgumentException::class, 'not an allowlisted scan path');
    } finally {
        rmdir($outsideDir);
    }
});

test('throws InvalidArgumentException when source path is outside allowlisted paths', function (): void {
    $outsideDir = sys_get_temp_dir().'/devportal-move-outside-'.uniqid();
    mkdir($outsideDir);
    $sourcePath = $outsideDir.'/chirper';
    mkdir($sourcePath);

    try {
        expect(fn () => $this->action->execute($sourcePath, $this->tmpDir, 'Archived'))
            ->toThrow(InvalidArgumentException::class, 'not inside any allowlisted scan path');
    } finally {
        rmdir($sourcePath);
        rmdir($outsideDir);
    }
});
