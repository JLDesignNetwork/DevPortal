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
        $parentPath = dirname($realProjectPath);

        $isConfigured = false;
        foreach ($this->settingsService->getCategoryPaths() as $paths) {
            foreach ($paths as $path) {
                if (realpath($path) === $parentPath) {
                    $isConfigured = true;
                    break 2;
                }
            }
        }

        if (! $isConfigured) {
            throw new InvalidArgumentException("Project path is not inside any configured category scan path: {$projectPath}");
        }

        $success = File::deleteDirectory($realProjectPath);

        if (! $success) {
            throw new InvalidArgumentException("Failed to delete the project directory at '{$projectPath}'. Check filesystem permissions.");
        }
    }
}
