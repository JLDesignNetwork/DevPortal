<?php

/**
 * @since 1.2.0
 *
 * @version 1.2.0
 */

declare(strict_types=1);

use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SettingsController;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (): Factory|\Illuminate\Contracts\View\View => view('welcome'));

Route::get('/api/projects', [ProjectController::class, 'index']);
Route::post('/api/projects/move', [ProjectController::class, 'move']);
Route::delete('/api/projects', [ProjectController::class, 'destroy']);
Route::get('/api/settings', [SettingsController::class, 'index']);
Route::post('/api/settings', [SettingsController::class, 'update']);
Route::post('/api/maintenance/sync-version', [MaintenanceController::class, 'syncVersion']);
Route::post('/api/maintenance/sync-all-versions', [MaintenanceController::class, 'syncAllVersions']);
Route::post('/api/maintenance/test-entry-points', [MaintenanceController::class, 'testEntryPoints']);
