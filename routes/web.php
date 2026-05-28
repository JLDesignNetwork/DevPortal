<?php

declare(strict_types=1);

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
