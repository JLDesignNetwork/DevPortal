<?php

declare(strict_types=1);

use App\Services\ProjectScanner;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Process\Process;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->scanner = new ProjectScanner(new SettingsService);
    $this->tmpDir = sys_get_temp_dir().'/devportal-test-'.uniqid();
    mkdir($this->tmpDir);
    mkdir($this->tmpDir.'/Active');
    mkdir($this->tmpDir.'/Archived');
    mkdir($this->tmpDir.'/Sandboxed');
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
    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);
    expect($result)->toBeArray()->toHaveCount(0);
});

test('scans a project directory and returns it with expected keys', function (): void {
    mkdir($this->tmpDir.'/Active/my-project');

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

    expect($result)->toHaveCount(1);
    expect($result[0])->toHaveKeys([
        'name', 'version', 'description', 'category', 'path', 'relative_path',
        'type', 'platform_host', 'platform_visibility',
        'last_modified', 'last_modified_timestamp',
        'changelog_version', 'changelog_date', 'changelog_content',
        'git_branch', 'git_dirty_count',
    ]);
});

test('scans projects across all three categories', function (): void {
    mkdir($this->tmpDir.'/Active/project-a');
    mkdir($this->tmpDir.'/Archived/project-b');
    mkdir($this->tmpDir.'/Sandboxed/project-c');

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

    expect($result)->toHaveCount(3);
    $categories = array_column($result, 'category');
    expect($categories)->toContain('Active')->toContain('Archived')->toContain('Sandboxed');
});

test('converts directory name to title case for project name', function (): void {
    mkdir($this->tmpDir.'/Active/my-cool-project');

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

    expect($result[0]['name'])->toBe('My Cool Project');
});

// ─── README Parsing Tests ────────────────────────────────────────────

test('parses project name from README first heading', function (): void {
    mkdir($this->tmpDir.'/Active/chirper');
    file_put_contents($this->tmpDir.'/Active/chirper/README.md', "# Chirper App\n\nA microblogging platform.");

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

    expect($result[0]['name'])->toBe('Chirper App');
});

test('ignores generic section headings like Features as project name', function (): void {
    mkdir($this->tmpDir.'/Active/gemini-chat-nuke-ff');
    file_put_contents($this->tmpDir.'/Active/gemini-chat-nuke-ff/README.md', "## ✨ Features\n\nSome great features.");

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

    // Should fall back to the title-cased folder name
    expect($result[0]['name'])->toBe('Gemini Chat Nuke Ff');
});

test('parses description from README first paragraph', function (): void {
    mkdir($this->tmpDir.'/Active/chirper');
    file_put_contents($this->tmpDir.'/Active/chirper/README.md', "# Chirper App\n\nA microblogging platform.");

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

    expect($result[0]['description'])->toBe('A microblogging platform.');
});

test('parses semver from README', function (): void {
    mkdir($this->tmpDir.'/Active/chirper');
    file_put_contents($this->tmpDir.'/Active/chirper/README.md', "# Chirper\n\nVersion 2.1.3");

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

    expect($result[0]['version'])->toBe('2.1.3');
});

test('detects Laravel version from README', function (): void {
    mkdir($this->tmpDir.'/Active/chirper');
    file_put_contents($this->tmpDir.'/Active/chirper/README.md', "# Chirper\n\nBuilt with Laravel 11.");

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

    expect($result[0]['version'])->toBe('Laravel 11');
});

test('sets description to null when README has no paragraph after heading', function (): void {
    mkdir($this->tmpDir.'/Active/bare-project');
    file_put_contents($this->tmpDir.'/Active/bare-project/README.md', "# Bare Project\n\n## Installation\nRun composer install.");

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

    expect($result[0]['description'])->toBeNull();
});

test('falls back to title-cased dir name when no README exists', function (): void {
    mkdir($this->tmpDir.'/Active/my_app');

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

    expect($result[0]['name'])->toBe('My App');
    expect($result[0]['version'])->toBe('N/A');
    expect($result[0]['description'])->toBeNull();
});

// ─── CHANGELOG Parsing Tests ──────────────────────────────────────────

test('parses version and date from CHANGELOG', function (): void {
    mkdir($this->tmpDir.'/Active/chirper');
    file_put_contents($this->tmpDir.'/Active/chirper/CHANGELOG.md', "# Changelog\n\n## [1.2.0] - 2025-03-15\n\n### Added\n- New login feature\n");

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

    expect($result[0]['changelog_version'])->toBe('1.2.0');
    expect($result[0]['changelog_date'])->toBe('2025-03-15');
});

test('parses changelog content bullet points', function (): void {
    mkdir($this->tmpDir.'/Active/chirper');
    file_put_contents($this->tmpDir.'/Active/chirper/CHANGELOG.md', "# Changelog\n\n## 1.0.0 - 2026-05-22\n\n### Added\n- Initial release\n- Login feature\n");

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

    expect($result[0]['changelog_content'])->toContain('Initial release');
});

test('returns null changelog fields when no CHANGELOG exists', function (): void {
    mkdir($this->tmpDir.'/Active/bare-project');

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

    expect($result[0]['changelog_version'])->toBeNull();
    expect($result[0]['changelog_date'])->toBeNull();
    expect($result[0]['changelog_content'])->toBeNull();
});

// ─── Git Status Tests ──────────────────────────────────────────────

test('returns null git fields when no .git directory exists', function (): void {
    mkdir($this->tmpDir.'/Active/no-git-project');

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

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

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

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

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

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

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

    expect($result[0]['production_version'])->toBe('3.4.1');
});

test('extracts production version from composer.json version', function (): void {
    mkdir($this->tmpDir.'/Active/php-project');
    $composerJson = json_encode(['name' => 'php-project', 'version' => '1.5.0']);
    file_put_contents($this->tmpDir.'/Active/php-project/composer.json', $composerJson);

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

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

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

    expect($result[0]['dependencies']['composer'])->toHaveKey('laravel/framework', '^11.0')
        ->and($result[0]['dependencies']['composer_dev'])->toHaveKey('pestphp/pest', '^2.0')
        ->and($result[0]['dependencies']['npm'])->toHaveKey('vue', '^3.4.0')
        ->and($result[0]['dependencies']['npm_dev'])->toHaveKey('vite', '^5.0.0');
});

test('returns timestamps of creation and modification', function (): void {
    mkdir($this->tmpDir.'/Active/dated-project');

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

    expect($result[0]['created_at'])->toBeString()
        ->and($result[0]['created_at_timestamp'])->toBeInt()
        ->and($result[0]['last_modified'])->toBeString()
        ->and($result[0]['last_modified_timestamp'])->toBeInt();
});

test('ignores framework version tags in production version fallback', function (): void {
    mkdir($this->tmpDir.'/Active/ignored-framework-project');
    file_put_contents($this->tmpDir.'/Active/ignored-framework-project/README.md', "# My App\n\nBuilt with Laravel 13");

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

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

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

    expect($result[0]['production_version'])->toBe('1.6.1')
        ->and($result[0]['changelog_version'])->toBe('1.6.1')
        ->and($result[0]['changelog_date'])->toBe('2026-05-22')
        ->and($result[0]['changelog_content'])->toContain('Fixed production relation backups');
});

test('parses GVS version tags from README badges and CHANGELOG', function (): void {
    mkdir($this->tmpDir.'/Active/gvs-project');
    $readmeContent = <<<'README'
# GVS Project

[![GVS](https://img.shields.io/badge/GVS-2605.4.1--bs-purple?style=flat-square)](https://github.com/JLDesignNetwork)

A project adhering to GVS.
README;
    file_put_contents($this->tmpDir.'/Active/gvs-project/README.md', $readmeContent);

    $changelogContent = <<<'CHANGELOG'
# Changelog

## [2605.4.1-bs] - 2026-08-18

### Changed
- GVS standardized release.
CHANGELOG;
    file_put_contents($this->tmpDir.'/Active/gvs-project/CHANGELOG.md', $changelogContent);

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

    expect($result[0]['version'])->toBe('2605.4.1-bs')
        ->and($result[0]['changelog_version'])->toBe('2605.4.1-bs')
        ->and($result[0]['changelog_date'])->toBe('2026-08-18')
        ->and($result[0]['production_version'])->toBe('2605.4.1-bs');
});

// ─── JLDN Frontmatter & Type Resolution Tests ─────────────────────────────────

test('type defaults to general when no frontmatter and no recognisable file signatures', function (): void {
    mkdir($this->tmpDir.'/Active/plain-project');

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

    expect($result[0]['type'])->toBe('general');
});

test('reads type from JLDN frontmatter in README.md', function (): void {
    mkdir($this->tmpDir.'/Active/typed-project');
    $readme = <<<'MD'
---
{
  "metadata": {
    "author": "Jeff Langdon",
    "type": "ruleset",
    "platform": "github:private"
  }
}
---
# Typed Project
MD;
    file_put_contents($this->tmpDir.'/Active/typed-project/README.md', $readme);

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

    expect($result[0]['type'])->toBe('ruleset')
        ->and($result[0]['platform_host'])->toBe('github')
        ->and($result[0]['platform_visibility'])->toBe('private');
});

test('reads gitlab:public from frontmatter correctly', function (): void {
    mkdir($this->tmpDir.'/Active/live-project');
    $readme = <<<'MD'
---
{
  "metadata": {
    "type": "live-site",
    "platform": "gitlab:public"
  }
}
---
# Live Project
MD;
    file_put_contents($this->tmpDir.'/Active/live-project/README.md', $readme);

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

    expect($result[0]['type'])->toBe('live-site')
        ->and($result[0]['platform_host'])->toBe('gitlab')
        ->and($result[0]['platform_visibility'])->toBe('public');
});

test('defaults platform_host to github and visibility to private when platform absent', function (): void {
    mkdir($this->tmpDir.'/Active/no-platform-project');
    $readme = <<<'MD'
---
{
  "metadata": {
    "type": "cli"
  }
}
---
# No Platform Project
MD;
    file_put_contents($this->tmpDir.'/Active/no-platform-project/README.md', $readme);

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

    expect($result[0]['platform_host'])->toBe('github')
        ->and($result[0]['platform_visibility'])->toBe('private');
});

test('infers web-app type from laravel/framework in composer.json', function (): void {
    mkdir($this->tmpDir.'/Active/laravel-project');
    $composer = json_encode(['require' => ['laravel/framework' => '^13.0']]);
    file_put_contents($this->tmpDir.'/Active/laravel-project/composer.json', $composer);

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

    expect($result[0]['type'])->toBe('web-app');
});

test('infers plugin type from keymaps directory presence', function (): void {
    mkdir($this->tmpDir.'/Active/pulsar-plugin');
    mkdir($this->tmpDir.'/Active/pulsar-plugin/keymaps');

    $result = new ProjectScanner(new SettingsService)->scan([$this->tmpDir]);

    expect($result[0]['type'])->toBe('plugin');
});
