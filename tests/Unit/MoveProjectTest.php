<?php

declare(strict_types=1);

use App\Actions\MoveProject;

beforeEach(function (): void {
    $this->action = new MoveProject;
    $this->tmpDir = sys_get_temp_dir().'/devportal-move-test-'.uniqid();
    mkdir($this->tmpDir);
    mkdir($this->tmpDir.'/Active');
    mkdir($this->tmpDir.'/Archive');
    mkdir($this->tmpDir.'/Sandbox');
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

    $result = $this->action->execute($sourcePath, $this->tmpDir, 'Archive');

    expect(is_dir($sourcePath))->toBeFalse();
    expect(is_dir($this->tmpDir.'/Archive/chirper'))->toBeTrue();
    expect($result)->toBe($this->tmpDir.'/Archive/chirper');
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

    expect(fn () => $this->action->execute($sourcePath, $this->tmpDir, 'Archive'))
        ->toThrow(InvalidArgumentException::class, 'Source project directory does not exist');
});

// ─── Collision Prevention ─────────────────────────────────────────

test('throws InvalidArgumentException when destination directory already exists', function (): void {
    $sourcePath = $this->tmpDir.'/Active/chirper';
    mkdir($sourcePath);
    mkdir($this->tmpDir.'/Archive/chirper');  // Pre-existing collision

    expect(fn () => $this->action->execute($sourcePath, $this->tmpDir, 'Archive'))
        ->toThrow(InvalidArgumentException::class, 'already exists at the target location');
});
