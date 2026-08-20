<?php

declare(strict_types=1);

use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\DashboardController;
use App\Http\Controllers\Api\v1\ProjectController;
use App\Http\Controllers\Api\v1\WorkflowController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public auth routes
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Authenticated user profile & logout
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        // Dashboard statistics
        Route::get('dashboard', [DashboardController::class, 'index']);

        // Project CRUD
        Route::apiResource('projects', ProjectController::class);

        // Custom Project Timeline/History
        Route::get('projects/{project}/history', [ProjectController::class, 'history']);

        // Secure Private Document Download
        Route::get('projects/{project}/documents/{document}', [ProjectController::class, 'downloadDocument'])
            ->name('projects.documents.download');

        // Workflow state machine transitions
        Route::post('projects/{project}/submit', [WorkflowController::class, 'submit']);
        Route::post('projects/{project}/reviews', [WorkflowController::class, 'review']);
        Route::post('projects/{project}/revision', [WorkflowController::class, 'revision']);
        Route::post('projects/{project}/approve', [WorkflowController::class, 'approve']);
        Route::post('projects/{project}/reject', [WorkflowController::class, 'reject']);
    });
});
