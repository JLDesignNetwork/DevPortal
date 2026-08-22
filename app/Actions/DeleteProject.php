<?php

/**
 * @since 2605.3.0-bs
 *
 * @version 2605.4.1-bs
 */

declare(strict_types=1);

namespace App\Actions;

use App\Services\SettingsService;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class DeleteProject
{
    public function __construct(
        private readonly SettingsService $settingsService,
    ) {}

    /**
     * Delete a project directory completely.
     *
     * @param  string  $projectPath  The absolute path of the project folder
     *
     * @throws InvalidArgumentException
     */
    public function execute(string $projectPath): void
    {
        if (! File::isDirectory($projectPath)) {
            throw new InvalidArgumentException("Project directory does not exist: {$projectPath}");
        }

        $realProjectPath = realpath($projectPath);

        $categoryPath = dirname($realProjectPath);
        $basePath = dirname($categoryPath);
        $category = basename($categoryPath);

        if (! in_array($category, $this->settingsService->getAllowedCategories(), true)) {
            throw new InvalidArgumentException("Invalid project category directory structure: {$category}");
        }

        $isAllowlisted = false;
        foreach ($this->settingsService->getAllowlistedPaths() as $path) {
            if (realpath($path) === $basePath) {
                $isAllowlisted = true;
                break;
            }
        }

        if (! $isAllowlisted) {
            throw new InvalidArgumentException("Project path is not inside any allowlisted scan path: {$projectPath}");
        }

        $success = File::deleteDirectory($realProjectPath);

        if (! $success) {
            throw new InvalidArgumentException("Failed to delete the project directory at '{$projectPath}'. Check filesystem permissions.");
        }
    }
}
