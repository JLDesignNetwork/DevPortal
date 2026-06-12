<?php

declare(strict_types=1);

use App\Actions\SyncProjectVersion;
use App\Services\ProjectScanner;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

it('auto-generates changelog when git tag is higher than changelog version', function () {
    $baseDir = storage_path('framework/testing/sync_test_base');
    if (File::exists($baseDir)) {
        File::deleteDirectory($baseDir);
    }
    File::makeDirectory($baseDir.'/Active/test_project', 0755, true);
    $tempDir = $baseDir.'/Active/test_project';

    // Initialize git and configure user
    (new Process(['git', 'init'], $tempDir))->run();
    (new Process(['git', 'config', 'user.name', 'Test'], $tempDir))->run();
    (new Process(['git', 'config', 'user.email', 'test@example.com'], $tempDir))->run();

    // Create initial files
    File::put($tempDir.'/CHANGELOG.md', "# Changelog\n\n## [1.0.0] - 2026-01-01\n- Initial release\n");
    File::put($tempDir.'/package.json', json_encode(['version' => '1.0.0', 'name' => 'test']));

    (new Process(['git', 'add', '.'], $tempDir))->run();
    (new Process(['git', 'commit', '-m', 'Initial commit'], $tempDir))->run();
    (new Process(['git', 'tag', 'v1.0.0'], $tempDir))->run();

    // Make new commits
    File::put($tempDir.'/test.txt', 'test');
    (new Process(['git', 'add', 'test.txt'], $tempDir))->run();
    (new Process(['git', 'commit', '-m', 'feat: added test file'], $tempDir))->run();

    // Tag the new version (which is higher than the Changelog)
    (new Process(['git', 'tag', 'v1.1.0'], $tempDir))->run();

    $action = new SyncProjectVersion(new ProjectScanner);

    // Execute action
    $result = $action->execute($tempDir);

    expect($result['success'])->toBeTrue()
        ->and($result['version'])->toBe('1.1.0');

    // Verify Changelog has been updated with the git commit message
    $changelog = File::get($tempDir.'/CHANGELOG.md');
    expect($changelog)->toContain('## [1.1.0]')
        ->and($changelog)->toContain('- feat: added test file');

    // Verify package.json updated
    $pkg = json_decode(File::get($tempDir.'/package.json'), true);
    expect($pkg['version'])->toBe('1.1.0');

    // Cleanup
    File::deleteDirectory($baseDir);
});
