<?php

/**
 * @since 2605.1.0-bs
 *
 * @version 2605.4.1-bs
 */

declare(strict_types=1);

namespace App\Actions;

use App\Services\SettingsService;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class MoveProject
{
    public function __construct(
        private readonly SettingsService $settingsService,
    ) {}

    /**
     * Move a project directory into another configured category scan path.
     *
     * @param  string  $sourcePath  The absolute path of the source project folder
     * @param  string  $targetCategoryPath  One of the configured category scan paths to move the project into
     * @return string The new absolute path of the project directory.
     *
     * @throws InvalidArgumentException
     */
    public function execute(string $sourcePath, string $targetCategoryPath): string
    {
        $allConfiguredPaths = array_merge(...array_values($this->settingsService->getCategoryPaths()));

        // 1. Validate target path is a configured category scan path
        $realTargetCategoryPath = realpath($targetCategoryPath);
        $isTargetConfigured = false;
        foreach ($allConfiguredPaths as $path) {
            $realConfiguredPath = realpath($path);
            if ($path === $targetCategoryPath || ($realConfiguredPath !== false && $realConfiguredPath === $realTargetCategoryPath)) {
                $isTargetConfigured = true;
                break;
            }
        }

        if (! $isTargetConfigured) {
            throw new InvalidArgumentException("Target path is not a configured category scan path: {$targetCategoryPath}");
        }

        // 2. Validate source path existence
        if (! File::isDirectory($sourcePath)) {
            throw new InvalidArgumentException("Source project directory does not exist: {$sourcePath}");
        }

        // 3. Validate source path's parent is a configured category scan path
        $realSource = realpath($sourcePath);
        $sourceParent = dirname($realSource);

        $isSourceConfigured = false;
        foreach ($allConfiguredPaths as $path) {
            if (realpath($path) === $sourceParent) {
                $isSourceConfigured = true;
                break;
            }
        }

        if (! $isSourceConfigured) {
            throw new InvalidArgumentException("Source path is not inside any configured category scan path: {$sourcePath}");
        }

        // 4. Define target path
        $projectDirName = basename($realSource);
        $targetPath = rtrim($targetCategoryPath, '/').'/'.$projectDirName;

        // 5. Check if it's already at the target location (no-op)
        if (realpath($targetPath) === $realSource) {
            return $realSource;
        }

        // 6. Prevent collision (destination directory must not exist)
        if (File::exists($targetPath)) {
            throw new InvalidArgumentException("A project named '{$projectDirName}' already exists at the target location.");
        }

        // 7. Perform the move
        $success = File::move($realSource, $targetPath);

        if (! $success) {
            throw new InvalidArgumentException("Failed to move the project directory from '{$sourcePath}' to '{$targetPath}'. Check filesystem permissions.");
        }

        return $targetPath;
    }
}
