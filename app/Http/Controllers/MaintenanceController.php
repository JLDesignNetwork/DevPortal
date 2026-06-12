<?php

/**
 * @since 1.2.0
 *
 * @version 1.2.0
 */

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SyncProjectVersion;
use App\Actions\TestEntryPoints;
use App\Services\ProjectScanner;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class MaintenanceController extends Controller
{
    public function __construct(
        private readonly SyncProjectVersion $syncProjectVersion,
        private readonly ProjectScanner $projectScanner,
        private readonly SettingsService $settingsService,
    ) {}

    /**
     * Synchronize version for a single project.
     */
    public function syncVersion(Request $request): JsonResponse
    {
        $request->validate([
            'path' => ['required', 'string'],
        ]);

        $projectPath = $request->input('path');

        try {
            $result = $this->syncProjectVersion->execute($projectPath);
            Cache::forget('devportal.projects');

            return response()->json([
                'success' => true,
                'message' => "Successfully synced version {$result['version']}.",
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Synchronize versions for all allowlisted projects.
     */
    public function syncAllVersions(Request $request): JsonResponse
    {
        $paths = $this->settingsService->getAllowlistedPaths();
        $projects = $this->projectScanner->scan($paths);

        $results = [];
        $successCount = 0;
        $failCount = 0;

        $excludeCats = array_map('strtolower', $this->settingsService->getArray('sync_exclude_categories', ['Sandbox']));
        $excludeProjs = array_map('strtolower', $this->settingsService->getArray('sync_exclude_projects', []));
        $includeCats = array_map('strtolower', $this->settingsService->getArray('sync_include_categories', []));
        $includeProjs = array_map('strtolower', $this->settingsService->getArray('sync_include_projects', []));

        foreach ($projects as $project) {
            $cat = strtolower($project['category'] ?? '');
            $name = strtolower($project['name'] ?? '');

            // 1. Check Include Lists (if set, must match)
            if (! empty($includeCats) || ! empty($includeProjs)) {
                $inCats = in_array($cat, $includeCats, true);
                $inProjs = in_array($name, $includeProjs, true);
                if (! $inCats && ! $inProjs) {
                    continue; // Skip because it is not whitelisted
                }
            }

            // 2. Check Exclude Lists (overrides include)
            if (in_array($cat, $excludeCats, true) || in_array($name, $excludeProjs, true)) {
                continue; // Skip because it is blacklisted
            }

            try {
                $syncResult = $this->syncProjectVersion->execute($project['path']);
                $results[] = [
                    'name' => $project['name'],
                    'status' => 'success',
                    'version' => $syncResult['version'],
                    'files' => $syncResult['updated_files'],
                ];
                $successCount++;
            } catch (\Throwable $e) {
                $results[] = [
                    'name' => $project['name'],
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
                $failCount++;
            }
        }

        Cache::forget('devportal.projects');

        return response()->json([
            'success' => true,
            'message' => "Synced {$successCount} projects successfully. {$failCount} projects failed or skipped.",
            'results' => $results,
        ]);
    }

    /**
     * Test entry points for allowlisted projects and return HTML results.
     */
    public function testEntryPoints(TestEntryPoints $action): JsonResponse
    {
        try {
            $markdown = $action->execute();
            // Use Laravel's built-in markdown parser
            $html = Str::markdown($markdown);

            return response()->json([
                'success' => true,
                'html' => $html,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
