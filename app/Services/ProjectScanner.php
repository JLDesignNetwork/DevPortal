<?php

/**
 * @since 2605.1.0-bs
 *
 * @version 2605.4.1-bs
 */

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class ProjectScanner
{
    public function __construct(
        private readonly SettingsService $settingsService,
    ) {}

    /**
     * Scan each category's configured paths and return parsed project details.
     *
     * @return array<int, array<string, mixed>>
     */
    public function scan(): array
    {
        $categoryPaths = $this->settingsService->getCategoryPaths();
        $allProjects = [];
        $processedPaths = [];

        foreach ($categoryPaths as $category => $paths) {
            foreach ($paths as $categoryPath) {
                if (File::isDirectory($categoryPath)) {
                    try {
                        $projectDirs = File::directories($categoryPath);
                    } catch (\UnexpectedValueException) {
                        continue;
                    }

                    foreach ($projectDirs as $projectPath) {
                        $projectPathStr = (string) $projectPath;
                        if (in_array($projectPathStr, $processedPaths, true)) {
                            continue;
                        }
                        $processedPaths[] = $projectPathStr;

                        $projectDirName = basename($projectPathStr);
                        $projectName = $this->toTitleCase($projectDirName);

                        $readmeData = [
                            'name' => $projectName,
                            'version' => 'N/A',
                            'description' => null,
                        ];
                        $changelogData = [
                            'changelog_version' => null,
                            'changelog_date' => null,
                            'changelog_content' => null,
                        ];
                        $features = [];

                        // Read README
                        $readmePath = $projectPathStr.'/README.md';
                        if (File::exists($readmePath)) {
                            $readmeContent = File::get($readmePath);
                            $readmeData = $this->parseReadme($readmeContent, $projectName);
                            $features = $this->parseFeatures($readmeContent);
                        }

                        // Read CHANGELOG
                        $changelogPath = $projectPathStr.'/CHANGELOG.md';
                        if (File::exists($changelogPath)) {
                            $changelogContent = File::get($changelogPath);
                            $changelogData = $this->parseChangelog($changelogContent);
                        }

                        // Get Git details
                        $gitDetails = $this->getGitDetails($projectPathStr);
                        $gitCommits = $this->getGitCommits($projectPathStr);

                        // Timestamps
                        $mtime = File::lastModified($projectPathStr);
                        $lastModified = Carbon::createFromTimestamp($mtime)->diffForHumans();
                        $createdData = $this->getCreatedTime($projectPathStr);

                        // Production Version
                        $productionVersion = $this->getProductionVersion($projectPathStr, $readmeData['version'], $changelogData['changelog_version']);

                        // Dependencies
                        $dependencies = $this->getDependencies($projectPathStr);

                        // Git activity count (last 30 days)
                        $gitActivityCount = $this->getGitCommitCountLast30Days($projectPathStr);

                        $allProjects[] = [
                            'name' => $readmeData['name'],
                            'version' => $readmeData['version'],
                            'production_version' => $productionVersion,
                            'description' => $readmeData['description'],
                            'category' => $category,
                            'type' => $this->resolveProjectType($projectPathStr, $dependencies),
                            'platform_host' => $this->resolvePlatformHost($projectPathStr),
                            'platform_visibility' => $this->resolvePlatformVisibility($projectPathStr),
                            'path' => $projectPathStr, // Absolute path for actions
                            'relative_path' => $category.'/'.$projectDirName,
                            'last_modified' => $lastModified,
                            'last_modified_timestamp' => $mtime,
                            'created_at' => $createdData['created_at'],
                            'created_at_timestamp' => $createdData['created_at_timestamp'],
                            'features' => $features,
                            'dependencies' => $dependencies,
                            'git_commits' => $gitCommits,
                            'git_activity_count' => $gitActivityCount,
                            'has_web_entry' => $this->hasWebEntryPoint($projectPathStr),
                            ...$changelogData,
                            ...$gitDetails,
                        ];
                    }
                }
            }
        }

        // Sort projects by last modified timestamp descending by default
        usort($allProjects, fn (array $a, array $b): int => $b['last_modified_timestamp'] <=> $a['last_modified_timestamp']);

        return $allProjects;
    }

    private function hasWebEntryPoint(string $path): bool
    {
        $entryPoints = [
            'index.php',
            'index.html',
            'public/index.php',
            'public/index.html',
            'routes/web.php',
            'src/index.js',
            'src/index.ts',
            'src/main.js',
            'src/main.ts',
            'src/App.vue',
            'src/App.jsx',
            'src/App.tsx',
        ];

        return array_any($entryPoints, fn ($entry) => File::exists($path.'/'.$entry));
    }

    /**
     * Parse the JLDN Metadata Frontmatter block from the primary project document.
     * Reads README.md first, then falls back to any root-level .md file.
     *
     * @return array<string, mixed>
     */
    private function parseJldnFrontmatter(string $projectPath): array
    {
        $candidates = ['README.md'];

        // Gather any other root-level .md files as fallbacks
        try {
            $mdFiles = glob($projectPath.'/*.md') ?: [];
            foreach ($mdFiles as $file) {
                $basename = basename($file);
                if ($basename !== 'README.md' && ! in_array($basename, ['CHANGELOG.md', 'LICENSE.md', 'CONTRIBUTING.md', 'SECURITY.md'], true)) {
                    $candidates[] = $basename;
                }
            }
        } catch (\Throwable) {
            // Ignore
        }

        foreach ($candidates as $filename) {
            $filePath = $projectPath.'/'.$filename;
            if (! File::exists($filePath)) {
                continue;
            }

            $content = File::get($filePath);

            // Match ---\n{...}\n--- frontmatter block
            if (preg_match('/^---\s*\n(\{.*?\})\s*\n---/s', $content, $matches)) {
                $decoded = json_decode($matches[1], true);
                if (is_array($decoded) && isset($decoded['metadata']) && is_array($decoded['metadata'])) {
                    return $decoded['metadata'];
                }
            }
        }

        return [];
    }

    /**
     * Resolve the project type from frontmatter, falling back to smart inference.
     */
    private function resolveProjectType(string $projectPath, array $dependencies): string
    {
        $meta = $this->parseJldnFrontmatter($projectPath);
        if (! empty($meta['type'])) {
            return (string) $meta['type'];
        }

        return $this->inferProjectType($projectPath, $dependencies);
    }

    /**
     * Infer the project type from file signatures when frontmatter is absent.
     */
    private function inferProjectType(string $path, array $dependencies): string
    {
        $composerDeps = array_merge(
            array_keys($dependencies['composer'] ?? []),
            array_keys($dependencies['composer_dev'] ?? [])
        );
        $npmDeps = array_merge(
            array_keys($dependencies['npm'] ?? []),
            array_keys($dependencies['npm_dev'] ?? [])
        );

        // Laravel web application
        if (in_array('laravel/framework', $composerDeps, true)) {
            return 'web-app';
        }
        // Next.js or Vite SPA
        if (in_array('next', $npmDeps, true) || in_array('vite', $npmDeps, true)) {
            return 'web-app';
        }
        // Pulsar/IDE plugin
        if (File::exists($path.'/keymaps') || File::exists($path.'/menus')) {
            return 'plugin';
        }
        // Book/CSS paged media
        if (File::exists($path.'/book.css') || count(glob($path.'/*.css') ?: []) > 0) {
            $cssFiles = glob($path.'/*.css') ?: [];
            foreach ($cssFiles as $css) {
                if (str_contains((string) File::get($css), '@page')) {
                    return 'book';
                }
            }
        }
        // PHP library (no laravel, no web entry)
        if (! empty($composerDeps) && ! $this->hasWebEntryPoint($path)) {
            return 'library';
        }
        // npm-only library
        if (empty($composerDeps) && ! empty($npmDeps) && ! $this->hasWebEntryPoint($path)) {
            return 'library';
        }
        // Docs heavy (no src, no public)
        if (! File::isDirectory($path.'/src') && ! File::isDirectory($path.'/public') && count(glob($path.'/*.md') ?: []) > 2) {
            return 'docs';
        }

        return 'general';
    }

    /**
     * Resolve the remote platform host (github|gitlab) from frontmatter.
     */
    private function resolvePlatformHost(string $projectPath): string
    {
        $meta = $this->parseJldnFrontmatter($projectPath);
        $platform = (string) ($meta['platform'] ?? 'github:private');

        return explode(':', $platform)[0] ?? 'github';
    }

    /**
     * Resolve the platform visibility (public|private) from frontmatter.
     */
    private function resolvePlatformVisibility(string $projectPath): string
    {
        $meta = $this->parseJldnFrontmatter($projectPath);
        $platform = (string) ($meta['platform'] ?? 'github:private');
        $parts = explode(':', $platform);

        return $parts[1] ?? 'private';
    }

    private function toTitleCase(string $name): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $name));
    }

    /**
     * Parse README contents.
     */
    private function parseReadme(string $content, string $defaultName): array
    {
        $projectName = $defaultName;
        $projectVersion = 'N/A';
        $description = null;

        // Get name (first heading)
        if (preg_match('/^\s*#{1,2}\s+(.*)$/m', $content, $matches)) {
            $extracted = trim(strip_tags($matches[1]));

            // Ignore generic section headers (often preceded by emojis)
            $isGenericSection = preg_match('/^(?:[\p{So}]\s*)?(features|installation|getting started|usage|configuration|setup|about|license|changelog)\b/iu', $extracted);

            if (! $isGenericSection && $extracted !== '') {
                $projectName = $extracted;
            }
        }

        // Get version
        if (preg_match('/img\.shields\.io\/badge\/GVS-([^-\?]+(?:--[^\-?]+)?)-[a-zA-Z0-9]+/i', $content, $matches)) {
            $projectVersion = str_replace('--', '-', $matches[1]);
        } elseif (preg_match('/(?:Version|v)\s*(\d{4}\.\d+\.\d+-[a-zA-Z0-9]+|\d+\.\d+\.\d+)/i', $content, $matches)) {
            $projectVersion = $matches[1];
        } elseif (preg_match('/img\.shields\.io\/packagist\/v\/[^\/]+\/[^\/]+(?:\?label=)?(v?\d+\.\d+(?:\.\d+)?)/i', $content, $matches)) {
            $projectVersion = $matches[1];
        } elseif (preg_match('/Laravel\s*(\d+)/i', $content, $matches)) {
            $projectVersion = 'Laravel '.$matches[1];
        }

        // Get first paragraph after heading
        $lines = explode("\n", $content);
        $headingFound = false;
        $paragraphLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                if ($headingFound && $paragraphLines !== []) {
                    break;
                }

                continue;
            }

            if (str_starts_with($trimmed, '#')) {
                if ($headingFound) {
                    break;
                }
                $headingFound = true;

                continue;
            }

            if ($headingFound) {
                // skip image badges or tags
                if (str_starts_with($trimmed, '[!')) {
                    continue;
                }
                if (str_starts_with($trimmed, '<p')) {
                    continue;
                }
                if (str_starts_with($trimmed, '![')) {
                    continue;
                }
                if (str_contains($trimmed, 'shields.io')) {
                    continue;
                }
                $paragraphLines[] = $trimmed;
            }
        }

        if ($paragraphLines !== []) {
            $description = implode(' ', $paragraphLines);
        }

        return [
            'name' => $projectName,
            'version' => $projectVersion,
            'description' => $description,
        ];
    }

    /**
     * Parse CHANGELOG contents.
     */
    public function parseChangelog(string $content): array
    {
        $version = null;
        $date = null;
        $bulletPoints = [];

        $lines = explode("\n", $content);
        $sectionFound = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (str_starts_with($trimmed, '## ')) {
                if ($sectionFound) {
                    break;
                }

                $versionStr = trim(substr($trimmed, 3));
                if (preg_match('/^\[?(\d{4}\.\d+\.\d+-[a-zA-Z0-9]+|\d+\.\d+(?:\.\d+)?(?:-[a-zA-Z0-9.]+)?)\]?\s*(?:-|\—|\–|\s)+\s*(\d{4}-\d{2}-\d{2})/u', $versionStr, $matches)) {
                    $version = $matches[1];
                    $date = $matches[2];
                    $sectionFound = true;
                }

                continue;
            }

            if ($sectionFound && $trimmed !== '') {
                $lineText = $trimmed;
                if (str_starts_with($lineText, '### ')) {
                    $bulletPoints[] = '<strong>'.trim(substr($lineText, 4)).'</strong>:';
                } else {
                    if (str_starts_with($lineText, '- ') || str_starts_with($lineText, '* ')) {
                        $lineText = trim($line);
                    }

                    // Simple Markdown replacement for bold and inline code
                    $lineText = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $lineText);
                    $lineText = preg_replace('/`(.*?)`/', '<code>$1</code>', (string) $lineText);

                    $bulletPoints[] = $lineText;
                }
            }
        }

        $changelogDetails = $bulletPoints === [] ? null : implode("\n", array_slice($bulletPoints, 0, 5));

        return [
            'changelog_version' => $version,
            'changelog_date' => $date,
            'changelog_content' => $changelogDetails,
        ];
    }

    /**
     * Get Git repository details.
     */
    private function getGitDetails(string $projectPath): array
    {
        if (! is_dir($projectPath.'/.git')) {
            return [
                'git_branch' => null,
                'git_dirty_count' => null,
            ];
        }

        // Get branch name
        $processBranch = new Process(['git', 'branch', '--show-current'], $projectPath);
        $processBranch->run();
        $gitBranch = $processBranch->isSuccessful() ? trim($processBranch->getOutput()) : null;

        if ($gitBranch === '') {
            $gitBranch = 'HEAD detached';
        }

        // Get count of modified/untracked files
        $processStatus = new Process(['git', 'status', '--porcelain'], $projectPath);
        $processStatus->run();
        if ($processStatus->isSuccessful()) {
            $statusOutput = trim($processStatus->getOutput());
            $gitDirtyCount = $statusOutput === '' ? 0 : count(explode("\n", $statusOutput));
        } else {
            $gitDirtyCount = null;
        }

        return [
            'git_branch' => $gitBranch,
            'git_dirty_count' => $gitDirtyCount,
        ];
    }

    private function getCreatedTime(string $path): array
    {
        $createdTimestamp = null;
        if (PHP_OS_FAMILY === 'Darwin') {
            $process = new Process(['stat', '-f', '%B', $path]);
            $process->run();
            if ($process->isSuccessful()) {
                $createdTimestamp = (int) trim($process->getOutput());
            }
        }

        if ($createdTimestamp === null || $createdTimestamp <= 0) {
            $createdTimestamp = File::exists($path) ? filectime($path) : time();
        }

        return [
            'created_at' => Carbon::createFromTimestamp($createdTimestamp)->diffForHumans(),
            'created_at_timestamp' => $createdTimestamp,
        ];
    }

    public function getProductionVersion(string $path, ?string $readmeVersion, ?string $changelogVersion): string
    {
        $versions = [];

        // 1. Check package.json
        $packageJsonPath = $path.'/package.json';
        if (File::exists($packageJsonPath)) {
            $content = File::get($packageJsonPath);
            $decoded = json_decode($content, true);
            if (is_array($decoded) && isset($decoded['version'])) {
                $versions['package'] = (string) $decoded['version'];
            }
        }

        // 2. Check composer.json
        $composerJsonPath = $path.'/composer.json';
        if (File::exists($composerJsonPath)) {
            $content = File::get($composerJsonPath);
            $decoded = json_decode($content, true);
            if (is_array($decoded) && isset($decoded['version'])) {
                $versions['composer'] = (string) $decoded['version'];
            }
        }

        // 3. Check git tag
        if (is_dir($path.'/.git')) {
            $process = new Process(['git', 'describe', '--tags', '--abbrev=0'], $path);
            $process->run();
            if ($process->isSuccessful()) {
                $tag = trim($process->getOutput());
                if ($tag !== '') {
                    $versions['git'] = $tag;
                }
            }
        }

        // 4. Check CHANGELOG version
        if ($changelogVersion !== null && $changelogVersion !== '') {
            $versions['changelog'] = $changelogVersion;
        }

        // 5. Fallback to README version, but ignore if it's just a framework tag (e.g. "Laravel 13")
        if ($readmeVersion !== null && $readmeVersion !== '' && ! str_contains(strtolower($readmeVersion), 'laravel')) {
            $versions['readme'] = $readmeVersion;
        }

        if ($versions === []) {
            return 'N/A';
        }

        $highestVersion = null;
        $highestCleanVersion = null;

        foreach ($versions as $rawVersion) {
            $cleanVersion = ltrim(trim($rawVersion), 'vV');

            if ($highestCleanVersion === null || version_compare($cleanVersion, $highestCleanVersion, '>')) {
                $highestCleanVersion = $cleanVersion;
                $highestVersion = $rawVersion;
            }
        }

        return $highestVersion ?? 'N/A';
    }

    private function getDependencies(string $path): array
    {
        $dependencies = [
            'composer' => [],
            'composer_dev' => [],
            'npm' => [],
            'npm_dev' => [],
        ];

        // Parse composer.json
        $composerPath = $path.'/composer.json';
        if (File::exists($composerPath)) {
            $content = File::get($composerPath);
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                if (isset($decoded['require']) && is_array($decoded['require'])) {
                    $dependencies['composer'] = $decoded['require'];
                }
                if (isset($decoded['require-dev']) && is_array($decoded['require-dev'])) {
                    $dependencies['composer_dev'] = $decoded['require-dev'];
                }
            }
        }

        // Parse package.json
        $packagePath = $path.'/package.json';
        if (File::exists($packagePath)) {
            $content = File::get($packagePath);
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                if (isset($decoded['dependencies']) && is_array($decoded['dependencies'])) {
                    $dependencies['npm'] = $decoded['dependencies'];
                }
                if (isset($decoded['devDependencies']) && is_array($decoded['devDependencies'])) {
                    $dependencies['npm_dev'] = $decoded['devDependencies'];
                }
            }
        }

        return $dependencies;
    }

    private function getGitCommits(string $path): array
    {
        if (! is_dir($path.'/.git')) {
            return [];
        }

        // Format: hash|short_hash|author|date_relative|message
        $process = new Process([
            'git', 'log', '-n', '5', '--pretty=format:%H|%h|%an|%ad|%s', '--date=relative',
        ], $path);

        $process->run();
        if (! $process->isSuccessful()) {
            return [];
        }

        $output = trim($process->getOutput());
        if ($output === '') {
            return [];
        }

        $commits = [];
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            $parts = explode('|', $line, 5);
            if (count($parts) === 5) {
                $commits[] = [
                    'hash' => $parts[0],
                    'short_hash' => $parts[1],
                    'author' => $parts[2],
                    'date' => $parts[3],
                    'message' => $parts[4],
                ];
            }
        }

        return $commits;
    }

    private function parseFeatures(string $content): array
    {
        $features = [];
        $lines = explode("\n", $content);
        $inFeaturesSection = false;
        $featuresHeaderLevel = 0;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            // Check if we hit a header
            if (str_starts_with($trimmed, '#')) {
                // If we were already in the features section, check if this new header has an equal or higher level (fewer or equal #)
                // If so, we've left the features section.
                if ($inFeaturesSection) {
                    preg_match('/^(#+)/', $trimmed, $matches);
                    $level = strlen($matches[1]);
                    if ($level <= $featuresHeaderLevel) {
                        break;
                    }
                }

                // Check if this header is a "Features" header
                if (preg_match('/^#+\s*(?:[\w-]+\s+)*features\b/i', $trimmed)) {
                    $inFeaturesSection = true;
                    preg_match('/^(#+)/', $trimmed, $matches);
                    $featuresHeaderLevel = strlen($matches[1]);

                    continue;
                }
            }

            if ($inFeaturesSection) {
                // Parse bullet points
                if (preg_match('/^(?:-|\*|\+)\s+(.+)$/', $trimmed, $matches)) {
                    $features[] = trim($matches[1]);
                } elseif (preg_match('/^\d+\.\s+(.+)$/', $trimmed, $matches)) {
                    $features[] = trim($matches[1]);
                }
            }
        }

        return $features;
    }

    private function getGitCommitCountLast30Days(string $path): int
    {
        if (! is_dir($path.'/.git')) {
            return 0;
        }

        $process = new Process(['git', 'rev-list', '--count', '--since=30 days ago', 'HEAD'], $path);
        $process->run();

        if ($process->isSuccessful()) {
            return (int) trim($process->getOutput());
        }

        return 0;
    }
}
