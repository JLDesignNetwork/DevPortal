<?php

declare(strict_types=1);

use App\Services\ProjectScanner;
use Symfony\Component\Process\Process;

beforeEach(function (): void {
    $this->scanner = new ProjectScanner;
    $this->tmpDir = sys_get_temp_dir().'/devportal-test-'.uniqid();
    mkdir($this->tmpDir);
    mkdir($this->tmpDir.'/Active');
    mkdir($this->tmpDir.'/Archive');
    mkdir($this->tmpDir.'/Sandbox');
});

afterEach(function (): void {
    // Clean up temporary test directories
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

// ─── Scan Tests ────────────────────────────────────────────────────

test('returns empty array when categories have no project directories', function (): void {
    $result = new ProjectScanner()->scan([$this->tmpDir]);
    expect($result)->toBeArray()->toHaveCount(0);
});

test('scans a project directory and returns it with expected keys', function (): void {
    mkdir($this->tmpDir.'/Active/my-project');

    $result = new ProjectScanner()->scan([$this->tmpDir]);

    expect($result)->toHaveCount(1);
    expect($result[0])->toHaveKeys([
        'name', 'version', 'description', 'category', 'path', 'relative_path',
        'last_modified', 'last_modified_timestamp',
        'changelog_version', 'changelog_date', 'changelog_content',
        'git_branch', 'git_dirty_count',
    ]);
});

test('scans projects across all three categories', function (): void {
    mkdir($this->tmpDir.'/Active/project-a');
    mkdir($this->tmpDir.'/Archive/project-b');
    mkdir($this->tmpDir.'/Sandbox/project-c');

    $result = new ProjectScanner()->scan([$this->tmpDir]);

    expect($result)->toHaveCount(3);
    $categories = array_column($result, 'category');
    expect($categories)->toContain('Active')->toContain('Archive')->toContain('Sandbox');
});

test('converts directory name to title case for project name', function (): void {
    mkdir($this->tmpDir.'/Active/my-cool-project');

    $result = new ProjectScanner()->scan([$this->tmpDir]);

    expect($result[0]['name'])->toBe('My Cool Project');
});

// ─── README Parsing Tests ────────────────────────────────────────────

test('parses project name from README first heading', function (): void {
    mkdir($this->tmpDir.'/Active/chirper');
    file_put_contents($this->tmpDir.'/Active/chirper/README.md', "# Chirper App\n\nA microblogging platform.");

    $result = new ProjectScanner()->scan([$this->tmpDir]);

    expect($result[0]['name'])->toBe('Chirper App');
});

test('parses description from README first paragraph', function (): void {
    mkdir($this->tmpDir.'/Active/chirper');
    file_put_contents($this->tmpDir.'/Active/chirper/README.md', "# Chirper App\n\nA microblogging platform.");

    $result = new ProjectScanner()->scan([$this->tmpDir]);

    expect($result[0]['description'])->toBe('A microblogging platform.');
});

test('parses semver from README', function (): void {
    mkdir($this->tmpDir.'/Active/chirper');
    file_put_contents($this->tmpDir.'/Active/chirper/README.md', "# Chirper\n\nVersion 2.1.3");

    $result = new ProjectScanner()->scan([$this->tmpDir]);

    expect($result[0]['version'])->toBe('2.1.3');
});

test('detects Laravel version from README', function (): void {
    mkdir($this->tmpDir.'/Active/chirper');
    file_put_contents($this->tmpDir.'/Active/chirper/README.md', "# Chirper\n\nBuilt with Laravel 11.");

    $result = new ProjectScanner()->scan([$this->tmpDir]);

    expect($result[0]['version'])->toBe('Laravel 11');
});

test('sets description to null when README has no paragraph after heading', function (): void {
    mkdir($this->tmpDir.'/Active/bare-project');
    file_put_contents($this->tmpDir.'/Active/bare-project/README.md', "# Bare Project\n\n## Installation\nRun composer install.");

    $result = new ProjectScanner()->scan([$this->tmpDir]);

    expect($result[0]['description'])->toBeNull();
});

test('falls back to title-cased dir name when no README exists', function (): void {
    mkdir($this->tmpDir.'/Active/my_app');

    $result = new ProjectScanner()->scan([$this->tmpDir]);

    expect($result[0]['name'])->toBe('My App');
    expect($result[0]['version'])->toBe('N/A');
    expect($result[0]['description'])->toBeNull();
});

// ─── CHANGELOG Parsing Tests ──────────────────────────────────────────

test('parses version and date from CHANGELOG', function (): void {
    mkdir($this->tmpDir.'/Active/chirper');
    file_put_contents($this->tmpDir.'/Active/chirper/CHANGELOG.md', "# Changelog\n\n## [1.2.0] - 2025-03-15\n\n### Added\n- New login feature\n");

    $result = new ProjectScanner()->scan([$this->tmpDir]);

    expect($result[0]['changelog_version'])->toBe('1.2.0');
    expect($result[0]['changelog_date'])->toBe('2025-03-15');
});

test('parses changelog content bullet points', function (): void {
    mkdir($this->tmpDir.'/Active/chirper');
    file_put_contents($this->tmpDir.'/Active/chirper/CHANGELOG.md', "# Changelog\n\n## 1.0.0 - 2026-05-22\n\n### Added\n- Initial release\n- Login feature\n");

    $result = new ProjectScanner()->scan([$this->tmpDir]);

    expect($result[0]['changelog_content'])->toContain('Initial release');
});

test('returns null changelog fields when no CHANGELOG exists', function (): void {
    mkdir($this->tmpDir.'/Active/bare-project');

    $result = new ProjectScanner()->scan([$this->tmpDir]);

    expect($result[0]['changelog_version'])->toBeNull();
    expect($result[0]['changelog_date'])->toBeNull();
    expect($result[0]['changelog_content'])->toBeNull();
});

// ─── Git Status Tests ──────────────────────────────────────────────

test('returns null git fields when no .git directory exists', function (): void {
    mkdir($this->tmpDir.'/Active/no-git-project');

    $result = new ProjectScanner()->scan([$this->tmpDir]);

    expect($result[0]['git_branch'])->toBeNull();
    expect($result[0]['git_dirty_count'])->toBeNull();
    expect($result[0]['git_activity_count'])->toBe(0);
});

test('returns correct git_activity_count when .git directory exists with commits', function (): void {
    $projectPath = $this->tmpDir.'/Active/git-project';
    mkdir($projectPath);

    // Initialize git repo
    new Process(['git', 'init'], $projectPath)->run();

    // Configure git user for tests
    new Process(['git', 'config', 'user.name', 'Test User'], $projectPath)->run();
    new Process(['git', 'config', 'user.email', 'test@example.com'], $projectPath)->run();
    new Process(['git', 'config', 'commit.gpgsign', 'false'], $projectPath)->run();

    file_put_contents($projectPath.'/file.txt', 'hello');

    // Add and commit
    new Process(['git', 'add', 'file.txt'], $projectPath)->run();
    new Process(['git', 'commit', '-m', 'Initial commit'], $projectPath)->run();

    $result = new ProjectScanner()->scan([$this->tmpDir]);

    // We expect 1 commit in the last 30 days
    expect($result[0]['git_activity_count'])->toBe(1);
});

// ─── Metadata Extraction Tests ──────────────────────────────────────────

test('parses features from README.md features section', function (): void {
    mkdir($this->tmpDir.'/Active/feature-project');
    $readmeContent = <<<'MARKDOWN'
# Feature Project
This is a test project.

## Features
- Super fast scanning
- Sleek modern dashboard interface
* Accessible settings page
- PHP 8.5 strictly typed

## Installation
Run pnpm install.
MARKDOWN;
    file_put_contents($this->tmpDir.'/Active/feature-project/README.md', $readmeContent);

    $result = new ProjectScanner()->scan([$this->tmpDir]);

    expect($result[0]['features'])->toBeArray()->toHaveCount(4)
        ->toContain('Super fast scanning')
        ->toContain('Sleek modern dashboard interface')
        ->toContain('Accessible settings page')
        ->toContain('PHP 8.5 strictly typed');
});

test('extracts production version from package.json version', function (): void {
    mkdir($this->tmpDir.'/Active/node-project');
    $packageJson = json_encode(['name' => 'node-project', 'version' => '3.4.1']);
    file_put_contents($this->tmpDir.'/Active/node-project/package.json', $packageJson);

    $result = new ProjectScanner()->scan([$this->tmpDir]);

    expect($result[0]['production_version'])->toBe('3.4.1');
});

test('extracts production version from composer.json version', function (): void {
    mkdir($this->tmpDir.'/Active/php-project');
    $composerJson = json_encode(['name' => 'php-project', 'version' => '1.5.0']);
    file_put_contents($this->tmpDir.'/Active/php-project/composer.json', $composerJson);

    $result = new ProjectScanner()->scan([$this->tmpDir]);

    expect($result[0]['production_version'])->toBe('1.5.0');
});

test('extracts dependencies from composer.json and package.json', function (): void {
    mkdir($this->tmpDir.'/Active/mixed-project');

    $composerJson = json_encode([
        'require' => ['php' => '^8.2', 'laravel/framework' => '^11.0'],
        'require-dev' => ['pestphp/pest' => '^2.0'],
    ]);
    file_put_contents($this->tmpDir.'/Active/mixed-project/composer.json', $composerJson);

    $packageJson = json_encode([
        'dependencies' => ['vue' => '^3.4.0'],
        'devDependencies' => ['vite' => '^5.0.0'],
    ]);
    file_put_contents($this->tmpDir.'/Active/mixed-project/package.json', $packageJson);

    $result = new ProjectScanner()->scan([$this->tmpDir]);

    expect($result[0]['dependencies']['composer'])->toHaveKey('laravel/framework', '^11.0')
        ->and($result[0]['dependencies']['composer_dev'])->toHaveKey('pestphp/pest', '^2.0')
        ->and($result[0]['dependencies']['npm'])->toHaveKey('vue', '^3.4.0')
        ->and($result[0]['dependencies']['npm_dev'])->toHaveKey('vite', '^5.0.0');
});

test('returns timestamps of creation and modification', function (): void {
    mkdir($this->tmpDir.'/Active/dated-project');

    $result = new ProjectScanner()->scan([$this->tmpDir]);

    expect($result[0]['created_at'])->toBeString()
        ->and($result[0]['created_at_timestamp'])->toBeInt()
        ->and($result[0]['last_modified'])->toBeString()
        ->and($result[0]['last_modified_timestamp'])->toBeInt();
});

test('ignores framework version tags in production version fallback', function (): void {
    mkdir($this->tmpDir.'/Active/ignored-framework-project');
    file_put_contents($this->tmpDir.'/Active/ignored-framework-project/README.md', "# My App\n\nBuilt with Laravel 13");

    $result = new ProjectScanner()->scan([$this->tmpDir]);

    expect($result[0]['production_version'])->toBe('N/A')
        ->and($result[0]['version'])->toBe('Laravel 13');
});

test('ignores unreleased changelog sections and parses first dated release version', function (): void {
    mkdir($this->tmpDir.'/Active/unreleased-project');
    $changelogContent = <<<'CHANGELOG'
# Changelog

All notable changes to **iHealth** will be documented in this file.

## [Unreleased]

### Fixed
- Fixed a bug.

## [1.6.1] — 2026-05-22

### Fixed
- Fixed production relation backups.
CHANGELOG;
    file_put_contents($this->tmpDir.'/Active/unreleased-project/CHANGELOG.md', $changelogContent);

    $result = new ProjectScanner()->scan([$this->tmpDir]);

    expect($result[0]['production_version'])->toBe('1.6.1')
        ->and($result[0]['changelog_version'])->toBe('1.6.1')
        ->and($result[0]['changelog_date'])->toBe('2026-05-22')
        ->and($result[0]['changelog_content'])->toContain('Fixed production relation backups');
});
