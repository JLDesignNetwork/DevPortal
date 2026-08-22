<?php

/**
 * @since 2605.1.0-bs
 *
 * @version 2605.4.1-bs
 */

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
        if ($this->settingsService->isCacheEnabled()) {
            $ttl = $this->settingsService->getCacheTtl();
            $projects = Cache::remember('devportal.projects', $ttl, fn (): array => $this->scanner->scan());
        } else {
            $projects = $this->scanner->scan();
        }

        return response()->json($projects);
    }

    /**
     * Move a project into another configured category scan path.
     */
    public function move(Request $request): JsonResponse
    {
        $request->validate([
            'source_path' => ['required', 'string'],
            'target_path' => ['required', 'string'],
        ]);

        try {
            $this->moveProject->execute(
                $request->input('source_path'),
                $request->input('target_path'),
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

        try {
            $this->deleteProject->execute($request->input('path'));

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
