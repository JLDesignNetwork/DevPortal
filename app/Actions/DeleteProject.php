<?php

/**
 * @since 1.2.0
 *
 * @version 1.2.0
 */

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class DeleteProject
{
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

        $success = File::deleteDirectory($projectPath);

        if (! $success) {
            throw new InvalidArgumentException("Failed to delete the project directory at '{$projectPath}'. Check filesystem permissions.");
        }
    }
}
