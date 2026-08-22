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
     * Move a project directory to another watch location and category.
     *
     * @param  string  $sourcePath  The absolute path of the source project folder
     * @param  string  $targetBasePath  The target base path to move the project to
     * @param  string  $targetCategory  The category to move to (Active, Archived, Sandboxed)
     * @return string The new absolute path of the project directory.
     *
     * @throws InvalidArgumentException
     */
    public function execute(string $sourcePath, string $targetBasePath, string $targetCategory): string
    {
        $allowlistedPaths = $this->settingsService->getAllowlistedPaths();
        $allowedCategories = $this->settingsService->getAllowedCategories();

        // 1. Validate target category
        if (! in_array($targetCategory, $allowedCategories, true)) {
            throw new InvalidArgumentException("Invalid target category: {$targetCategory}. Allowed categories are: ".implode(', ', $allowedCategories));
        }

        // 2. Validate source path existence
        if (! File::isDirectory($sourcePath)) {
            throw new InvalidArgumentException("Source project directory does not exist: {$sourcePath}");
        }

        // 2a. Validate target base path is allowlisted
        $isTargetAllowlisted = false;
        foreach ($allowlistedPaths as $path) {
            if (realpath($path) === realpath($targetBasePath) || $path === $targetBasePath) {
                $isTargetAllowlisted = true;
                break;
            }
        }

        if (! $isTargetAllowlisted) {
            throw new InvalidArgumentException("Target base path is not an allowlisted scan path: {$targetBasePath}");
        }

        // 2b. Validate source path is contained within an allowlisted path
        $realSource = realpath($sourcePath) ?: $sourcePath;
        $isSourceAllowlisted = false;
        foreach ($allowlistedPaths as $path) {
            $realAllowlisted = realpath($path) ?: $path;
            if (str_starts_with($realSource, $realAllowlisted)) {
                $isSourceAllowlisted = true;
                break;
            }
        }

        if (! $isSourceAllowlisted) {
            throw new InvalidArgumentException("Source path is not inside any allowlisted scan path: {$sourcePath}");
        }

        // 3. Define target path
        $projectDirName = basename($sourcePath);
        $targetPath = $targetBasePath.'/'.$targetCategory.'/'.$projectDirName;

        // 4. Check if it's already in the target category & location (no-op)
        if (realpath($sourcePath) === realpath($targetPath)) {
            return $sourcePath;
        }

        // 5. Prevent collision (destination directory must not exist)
        if (File::exists($targetPath)) {
            throw new InvalidArgumentException("A project named '{$projectDirName}' already exists at the target location.");
        }

        // Ensure target category directory exists under target base path
        $targetCategoryDir = $targetBasePath.'/'.$targetCategory;
        if (! File::exists($targetCategoryDir)) {
            File::makeDirectory($targetCategoryDir, 0755, true);
        }

        // 6. Perform the move
        $success = File::move($sourcePath, $targetPath);

        if (! $success) {
            throw new InvalidArgumentException("Failed to move the project directory from '{$sourcePath}' to '{$targetPath}'. Check filesystem permissions.");
        }

        return $targetPath;
    }
}
