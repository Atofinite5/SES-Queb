<?php

use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\ConfigController;
use App\Http\Controllers\Api\GitHubController;
use App\Http\Controllers\Api\ScaffoldController;
use App\Http\Controllers\Api\TemplateController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Templates
    Route::get('/templates', [TemplateController::class, 'index']);
    Route::get('/templates/{id}', [TemplateController::class, 'show']);

    // Scaffold
    Route::post('/scaffold', [ScaffoldController::class, 'create']);
    Route::get('/scaffold/{jobId}/status', [ScaffoldController::class, 'status']);
    Route::get('/scaffold/{jobId}/download', [ScaffoldController::class, 'download']);

    // Audit
    Route::post('/audit', [AuditController::class, 'audit']);
    Route::get('/audit/{reportId}', [AuditController::class, 'report']);
    Route::get('/audits', [AuditController::class, 'index']);

    // Configurations
    Route::get('/configs', [ConfigController::class, 'index']);
    Route::post('/configs', [ConfigController::class, 'store']);
    Route::get('/configs/{id}', [ConfigController::class, 'show']);
    Route::delete('/configs/{id}', [ConfigController::class, 'destroy']);

    // GitHub
    Route::post('/github/connect', [GitHubController::class, 'connect']);
    Route::post('/github/push', [GitHubController::class, 'push']);
    Route::get('/github/repositories', [GitHubController::class, 'repositories']);
});
