<?php

/**
 * @since 1.2.0
 *
 * @version 1.2.0
 */

declare(strict_types=1);

namespace App\Actions;

use App\Services\ProjectScanner;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

class SyncProjectVersion
{
    public function __construct(
        private readonly ProjectScanner $scanner
    ) {}

    /**
     * Synchronize the highest version found in the project across all supported files.
     *
     * @return array<string, mixed> Summary of what was updated
     */
    public function execute(string $projectPath): array
    {
        $realPath = realpath($projectPath);
        if (! $realPath || ! File::isDirectory($realPath)) {
            throw new InvalidArgumentException("Invalid project directory: {$projectPath}");
        }

        // Use the scanner to determine the absolute highest version available
        $scannedProjects = $this->scanner->scan([dirname(dirname($realPath))]);
        $projectData = null;
        foreach ($scannedProjects as $p) {
            if ($p['path'] === $realPath) {
                $projectData = $p;
                break;
            }
        }

        if (! $projectData) {
            throw new InvalidArgumentException("Could not scan project directory: {$projectPath}");
        }

        $highestVersion = $projectData['production_version'] ?? 'N/A';
        if ($highestVersion === 'N/A') {
            throw new InvalidArgumentException("Could not determine a valid production version for {$projectPath}.");
        }

        $cleanVersion = ltrim(trim($highestVersion), 'vV');
        $oldChangelogVersion = $projectData['changelog_version'] ?? '0.0.0';
        $oldCleanVersion = ltrim(trim($oldChangelogVersion), 'vV');

        $updatedFiles = [];
        $changelogPath = $realPath.'/CHANGELOG.md';

        // 1. Auto-generate changelog if mathematical version is higher
        if (version_compare($cleanVersion, $oldCleanVersion, '>')) {
            // Attempt to get git log between old version tag and new version tag
            $logProcess = new Process(['git', 'log', "v{$oldCleanVersion}..v{$cleanVersion}", '--pretty=format:- %s'], $realPath);
            $logProcess->run();
            $commits = trim($logProcess->getOutput());

            // Fallbacks if the exact tags didn't match
            if (! $logProcess->isSuccessful() || $commits === '') {
                // Try without 'v' prefix
                $logProcess = new Process(['git', 'log', "{$oldCleanVersion}..{$cleanVersion}", '--pretty=format:- %s'], $realPath);
                $logProcess->run();
                $commits = trim($logProcess->getOutput());
            }

            if (! $logProcess->isSuccessful() || $commits === '') {
                // Just grab the last 10 commits as a fallback
                $logProcess = new Process(['git', 'log', '-n', '10', '--pretty=format:- %s'], $realPath);
                $logProcess->run();
                $commits = trim($logProcess->getOutput());
            }

            if ($commits === '') {
                $commits = "- Auto-synced version bumped to {$cleanVersion}";
            }

            $date = date('Y-m-d');
            $newBlock = "## [{$cleanVersion}] - {$date}\n{$commits}\n\n";

            if (File::exists($changelogPath)) {
                $content = File::get($changelogPath);
                if (preg_match('/^(#\s+Changelog\s*\n)/mi', $content, $matches)) {
                    $newContent = str_replace($matches[1], $matches[1]."\n".$newBlock, $content);
                } else {
                    $newContent = "# Changelog\n\n".$newBlock.$content;
                }
                File::put($changelogPath, $newContent);
            } else {
                File::put($changelogPath, "# Changelog\n\n".$newBlock);
            }
            $updatedFiles[] = 'CHANGELOG.md';
        }

        // 2. Update package.json
        $packageJsonPath = $realPath.'/package.json';
        if (File::exists($packageJsonPath)) {
            $content = File::get($packageJsonPath);
            $decoded = json_decode($content, true);
            if (is_array($decoded) && isset($decoded['version']) && $decoded['version'] !== $cleanVersion) {
                $decoded['version'] = $cleanVersion;
                File::put($packageJsonPath, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
                $updatedFiles[] = 'package.json';
            }
        }

        // 3. Update composer.json
        $composerJsonPath = $realPath.'/composer.json';
        if (File::exists($composerJsonPath)) {
            $content = File::get($composerJsonPath);
            $decoded = json_decode($content, true);
            if (is_array($decoded) && isset($decoded['version']) && $decoded['version'] !== $cleanVersion) {
                $decoded['version'] = $cleanVersion;
                File::put($composerJsonPath, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
                $updatedFiles[] = 'composer.json';
            }
        }

        // 4. Update README.md
        $readmePath = $realPath.'/README.md';
        if (File::exists($readmePath)) {
            $content = File::get($readmePath);
            $newContent = preg_replace('/(Version\s+|v)(\d+\.\d+\.\d+)/i', '${1}'.$cleanVersion, $content, 1, $count);
            if ($count > 0 && $newContent !== $content) {
                File::put($readmePath, $newContent);
                $updatedFiles[] = 'README.md';
            }
        }

        // 5. Update script files (.php, .js, .css, .ts)
        $finder = new Finder;
        $finder->files()
            ->in($realPath)
            ->name(['*.php', '*.js', '*.ts', '*.css'])
            ->exclude(['vendor', 'node_modules', 'dist', 'build', 'storage', '.git', 'public/build']);

        $scriptUpdatedCount = 0;
        foreach ($finder as $file) {
            $filePath = $file->getRealPath();
            $content = File::get($filePath);

            $pattern = '/((?:\*|\/\/)\s*@?version\s*:?\s*)(v?\d+\.\d+(?:\.\d+)?(?:-[a-zA-Z0-9.]+)?)/i';

            $newContent = preg_replace_callback($pattern, function ($matches) use ($cleanVersion) {
                $hasV = stripos($matches[2], 'v') === 0;
                $replacementVersion = $hasV ? 'v'.$cleanVersion : $cleanVersion;

                return $matches[1].$replacementVersion;
            }, $content, -1, $count);

            if ($count > 0 && $newContent !== $content) {
                File::put($filePath, $newContent);
                $scriptUpdatedCount++;
            }
        }

        if ($scriptUpdatedCount > 0) {
            $updatedFiles[] = "{$scriptUpdatedCount} script file(s)";
        }

        // 6. Auto-commit and push if anything was updated
        if (! empty($updatedFiles) && is_dir($realPath.'/.git')) {
            $process = new Process(['git', 'status', '--porcelain'], $realPath);
            $process->run();
            if ($process->isSuccessful() && trim($process->getOutput()) !== '') {
                (new Process(['git', 'add', '-A'], $realPath))->run();
                $commitMsg = "chore(release): sync version to v{$cleanVersion} [skip ci]";
                (new Process(['git', 'commit', '-m', $commitMsg], $realPath))->run();

                // Push quietly, ignoring failure if no upstream branch
                (new Process(['git', 'push'], $realPath))->run();

                $updatedFiles[] = 'Git Commit & Push';
            }
        }

        return [
            'success' => true,
            'version' => $cleanVersion,
            'updated_files' => $updatedFiles,
        ];
    }
}
