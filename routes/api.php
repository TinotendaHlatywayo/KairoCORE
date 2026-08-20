<?php

use App\Http\Controllers\Api\AcademicWorkflowController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('auth/token', [AuthController::class, 'token']);

Route::prefix('academic')->middleware(['auth:sanctum'])->group(function () {
    Route::get('workflow', [AcademicWorkflowController::class, 'workflow']);
    Route::get('workflow/progress', [AcademicWorkflowController::class, 'progress']);
    Route::get('workflow/steps', [AcademicWorkflowController::class, 'steps']);
    Route::get('workflow/steps/{step}', [AcademicWorkflowController::class, 'step'])->where('step', '[a-z_]+');
    Route::post('workflow/steps/{step}/complete', [AcademicWorkflowController::class, 'complete'])->where('step', '[a-z_]+');
    Route::post('workflow/steps/{step}/skip', [AcademicWorkflowController::class, 'skip'])->where('step', '[a-z_]+');
    Route::post('workflow/steps/{step}/reset', [AcademicWorkflowController::class, 'reset'])->where('step', '[a-z_]+');
    Route::get('readiness', [AcademicWorkflowController::class, 'readiness']);
    Route::get('readiness/timeline', [AcademicWorkflowController::class, 'timeline']);
    Route::get('readiness/kpis', [AcademicWorkflowController::class, 'kpis']);
});
