<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteProject;
use App\Actions\MoveProject;
use App\Services\ProjectScanner;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

final class ProjectController extends Controller
{
    /**
     * Use constructor promotion to inject services/actions.
     */
    public function __construct(
        private readonly ProjectScanner $scanner,
        private readonly MoveProject $moveProject,
        private readonly SettingsService $settingsService,
        private readonly DeleteProject $deleteProject,
    ) {}

    /**
     * List all projects.
     */
    public function index(): JsonResponse
    {
        $paths = $this->settingsService->getAllowlistedPaths();

        if ($this->settingsService->isCacheEnabled()) {
            $ttl = $this->settingsService->getCacheTtl();
            $projects = Cache::remember('devportal.projects', $ttl, fn (): array => $this->scanner->scan($paths));
        } else {
            $projects = $this->scanner->scan($paths);
        }

        return response()->json($projects);
    }

    /**
     * Move a project to a new category and location.
     */
    public function move(Request $request): JsonResponse
    {
        $request->validate([
            'source_path' => ['required', 'string'],
            'target_base_path' => ['required', 'string'],
            'target_category' => ['required', 'string'],
        ]);

        $sourcePath = $request->input('source_path');
        $targetBasePath = $request->input('target_base_path');
        $targetCategory = $request->input('target_category');

        $allowlistedPaths = $this->settingsService->getAllowlistedPaths();

        // Security check: ensure target_base_path is explicitly allowlisted
        if (! in_array($targetBasePath, $allowlistedPaths, true)) {
            return response()->json([
                'success' => false,
                'error' => 'The target location is not in the allowlisted scan paths.',
            ], 422);
        }

        // Security check: ensure source_path is inside one of the allowlisted paths
        $isSourcePathValid = false;
        foreach ($allowlistedPaths as $path) {
            $realSource = realpath($sourcePath) ?: $sourcePath;
            $realPath = realpath($path) ?: $path;
            if (str_starts_with((string) $realSource, $realPath)) {
                $isSourcePathValid = true;
                break;
            }
        }

        if (! $isSourcePathValid) {
            return response()->json([
                'success' => false,
                'error' => 'The source path is not inside any allowlisted scan paths.',
            ], 422);
        }

        try {
            $this->moveProject->execute(
                $sourcePath,
                $targetBasePath,
                $targetCategory
            );

            Cache::forget('devportal.projects');

            return response()->json([
                'success' => true,
                'message' => 'Project moved successfully.',
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete a project from disk.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'path' => ['required', 'string'],
        ]);

        $projectPath = $request->input('path');
        $allowlistedPaths = $this->settingsService->getAllowlistedPaths();

        $realProjectPath = realpath($projectPath);
        if ($realProjectPath === false) {
            return response()->json([
                'success' => false,
                'error' => 'The project directory does not exist.',
            ], 422);
        }

        $categoryPath = dirname($realProjectPath);
        $basePath = dirname($categoryPath);
        $category = basename($categoryPath);

        $allowedCategories = ['Active', 'Archive', 'Sandbox'];
        if (! in_array($category, $allowedCategories, true)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid project category directory structure.',
            ], 422);
        }

        $isPathAllowlisted = false;
        foreach ($allowlistedPaths as $path) {
            $realAllowlisted = realpath($path);
            if ($realAllowlisted === $basePath) {
                $isPathAllowlisted = true;
                break;
            }
        }

        if (! $isPathAllowlisted) {
            return response()->json([
                'success' => false,
                'error' => 'The project is not located in any allowlisted scan paths.',
            ], 422);
        }

        try {
            $this->deleteProject->execute($realProjectPath);

            Cache::forget('devportal.projects');

            return response()->json([
                'success' => true,
                'message' => 'Project deleted successfully from disk.',
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
