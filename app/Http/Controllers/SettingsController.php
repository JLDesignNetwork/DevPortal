<?php

/**
 * @since 1.2.0
 *
 * @version 1.2.0
 */

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settingsService
    ) {}

    /**
     * Get the current settings.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'cache_enabled' => $this->settingsService->isCacheEnabled(),
            'cache_ttl' => $this->settingsService->getCacheTtl(),
            'allowlisted_paths' => $this->settingsService->getAllowlistedPaths(),
            'splash_recent_count' => $this->settingsService->getSplashRecentCount(),
            'splash_active_count' => $this->settingsService->getSplashActiveCount(),
            'domain_extension' => $this->settingsService->getDomainExtension(),
            'default_sort' => $this->settingsService->getDefaultSort(),
            'sync_exclude_categories' => $this->settingsService->getArray('sync_exclude_categories', ['Sandbox']),
            'sync_exclude_projects' => $this->settingsService->getArray('sync_exclude_projects', []),
            'sync_include_categories' => $this->settingsService->getArray('sync_include_categories', []),
            'sync_include_projects' => $this->settingsService->getArray('sync_include_projects', []),
            'entry_exclude_categories' => $this->settingsService->getArray('entry_exclude_categories', ['Archive']),
            'entry_exclude_projects' => $this->settingsService->getArray('entry_exclude_projects', []),
            'entry_include_categories' => $this->settingsService->getArray('entry_include_categories', []),
            'entry_include_projects' => $this->settingsService->getArray('entry_include_projects', []),
        ]);
    }

    /**
     * Update settings.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cache_enabled' => ['required', 'boolean'],
            'cache_ttl' => ['required', 'integer', 'min:0'],
            'allowlisted_paths' => ['present', 'array'],
            'allowlisted_paths.*' => ['required', 'string'],
            'splash_recent_count' => ['required', 'integer', 'min:1'],
            'splash_active_count' => ['required', 'integer', 'min:1'],
            'domain_extension' => ['required', 'string', 'regex:/^[a-zA-Z0-9\.-]+$/'],
            'default_sort' => ['required', 'string', 'in:date-desc,date-asc,created-desc,created-asc,alpha-asc,alpha-desc'],
            'sync_exclude_categories' => ['present', 'array'],
            'sync_exclude_projects' => ['present', 'array'],
            'sync_include_categories' => ['present', 'array'],
            'sync_include_projects' => ['present', 'array'],
            'entry_exclude_categories' => ['present', 'array'],
            'entry_exclude_projects' => ['present', 'array'],
            'entry_include_categories' => ['present', 'array'],
            'entry_include_projects' => ['present', 'array'],
        ]);

        // Validate that all custom folders exist on disk
        foreach ($validated['allowlisted_paths'] as $path) {
            if (! File::isDirectory($path)) {
                return response()->json([
                    'success' => false,
                    'error' => "The directory \"{$path}\" does not exist on this machine.",
                ], 422);
            }
        }

        // Validate intersection
        $checkIntersections = [
            ['Version Sync Categories', $validated['sync_exclude_categories'], $validated['sync_include_categories']],
            ['Version Sync Projects', $validated['sync_exclude_projects'], $validated['sync_include_projects']],
            ['Entry Point Test Categories', $validated['entry_exclude_categories'], $validated['entry_include_categories']],
            ['Entry Point Test Projects', $validated['entry_exclude_projects'], $validated['entry_include_projects']],
        ];

        foreach ($checkIntersections as [$name, $exclude, $include]) {
            $intersection = array_intersect(
                array_map('strtolower', $exclude),
                array_map('strtolower', $include)
            );
            if (! empty($intersection)) {
                $conflict = implode(', ', array_intersect($exclude, $include) ?: $intersection);

                return response()->json([
                    'success' => false,
                    'error' => "Conflict in {$name}: '{$conflict}' cannot be in both the Blacklist and Whitelist. Please choose one.",
                ], 422);
            }
        }

        $this->settingsService->set('cache_enabled', $validated['cache_enabled']);
        $this->settingsService->set('cache_ttl', $validated['cache_ttl']);
        $this->settingsService->set('allowlisted_paths', $validated['allowlisted_paths']);
        $this->settingsService->set('splash_recent_count', $validated['splash_recent_count']);
        $this->settingsService->set('splash_active_count', $validated['splash_active_count']);
        $this->settingsService->set('domain_extension', $validated['domain_extension']);
        $this->settingsService->set('default_sort', $validated['default_sort']);

        $this->settingsService->set('sync_exclude_categories', $validated['sync_exclude_categories']);
        $this->settingsService->set('sync_exclude_projects', $validated['sync_exclude_projects']);
        $this->settingsService->set('sync_include_categories', $validated['sync_include_categories']);
        $this->settingsService->set('sync_include_projects', $validated['sync_include_projects']);

        $this->settingsService->set('entry_exclude_categories', $validated['entry_exclude_categories']);
        $this->settingsService->set('entry_exclude_projects', $validated['entry_exclude_projects']);
        $this->settingsService->set('entry_include_categories', $validated['entry_include_categories']);
        $this->settingsService->set('entry_include_projects', $validated['entry_include_projects']);

        // Clear project listing cache on settings update to reflect changes immediately
        Cache::forget('devportal.projects');

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully.',
        ]);
    }
}
