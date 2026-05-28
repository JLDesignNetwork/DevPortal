<?php

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
            'allowlisted_paths' => ['required', 'array'],
            'allowlisted_paths.*' => ['required', 'string'],
            'splash_recent_count' => ['required', 'integer', 'min:1'],
            'splash_active_count' => ['required', 'integer', 'min:1'],
            'domain_extension' => ['required', 'string', 'regex:/^[a-zA-Z0-9\.-]+$/'],
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

        $this->settingsService->set('cache_enabled', $validated['cache_enabled']);
        $this->settingsService->set('cache_ttl', $validated['cache_ttl']);
        $this->settingsService->set('allowlisted_paths', $validated['allowlisted_paths']);
        $this->settingsService->set('splash_recent_count', $validated['splash_recent_count']);
        $this->settingsService->set('splash_active_count', $validated['splash_active_count']);
        $this->settingsService->set('domain_extension', $validated['domain_extension']);

        // Clear project listing cache on settings update to reflect changes immediately
        Cache::forget('devportal.projects');

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully.',
        ]);
    }
}
