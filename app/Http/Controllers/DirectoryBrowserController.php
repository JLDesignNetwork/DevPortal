<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DirectoryBrowserController extends Controller
{
    /**
     * List the subdirectories of a given path, for the settings directory picker.
     * Defaults to the user's home directory when no path is given.
     */
    public function index(Request $request): JsonResponse
    {
        $requestedPath = $request->query('path');
        $path = is_string($requestedPath) && $requestedPath !== '' ? $requestedPath : $this->homeDirectory();

        $realPath = realpath($path);

        if ($realPath === false || ! is_dir($realPath) || ! is_readable($realPath)) {
            return response()->json([
                'error' => "The path \"{$path}\" does not exist or is not readable.",
            ], 422);
        }

        $directories = [];
        $entries = @scandir($realPath) ?: [];

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..' || str_starts_with($entry, '.')) {
                continue;
            }

            $entryPath = rtrim($realPath, '/').'/'.$entry;
            if (is_dir($entryPath) && is_readable($entryPath)) {
                $directories[] = ['name' => $entry, 'path' => $entryPath];
            }
        }

        usort($directories, fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));

        $parentPath = dirname($realPath);

        return response()->json([
            'current_path' => $realPath,
            'parent_path' => $parentPath !== $realPath ? $parentPath : null,
            'directories' => $directories,
        ]);
    }

    private function homeDirectory(): string
    {
        $home = getenv('HOME') ?: (getenv('USERPROFILE') ?: ($_SERVER['HOME'] ?? $_SERVER['USERPROFILE'] ?? null));

        return is_string($home) && $home !== '' ? $home : '/';
    }
}
