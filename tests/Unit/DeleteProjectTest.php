<?php

declare(strict_types=1);

use App\Actions\DeleteProject;

beforeEach(function (): void {
    $this->action = new DeleteProject;
    $this->tmpDir = sys_get_temp_dir().'/devportal-delete-test-'.uniqid();
    mkdir($this->tmpDir);
});

afterEach(function (): void {
    if (is_dir($this->tmpDir)) {
        rmdir($this->tmpDir);
    }
});

test('successfully deletes a project directory', function (): void {
    $projectPath = $this->tmpDir.'/chirper';
    mkdir($projectPath);
    file_put_contents($projectPath.'/README.md', '# Chirper');

    $this->action->execute($projectPath);

    expect(is_dir($projectPath))->toBeFalse();
});

test('throws InvalidArgumentException when directory does not exist', function (): void {
    $projectPath = $this->tmpDir.'/nonexistent';

    expect(fn () => $this->action->execute($projectPath))
        ->toThrow(InvalidArgumentException::class, 'Project directory does not exist');
});
