<?php

use App\Domain\SuperAdmin\Http\Controllers\AccesController;
use App\Domain\SuperAdmin\Http\Controllers\AnalyticsController;
use App\Domain\SuperAdmin\Http\Controllers\EcoleController;
use App\Domain\SuperAdmin\Http\Controllers\PersonnalisationController;
use Illuminate\Support\Facades\Route;

// Portail Super Admin (§15.6) : hors ResolveTenant (pas d'école courante),
// gardé par platform.team (fixe le "team" spatie sur la plateforme) puis
// role:SUPER_ADMIN. Ajout des pages Livewire prévu en Session 9.
Route::domain(config('app.admin_subdomain'))
    ->middleware(['auth', 'platform.team', 'role:SUPER_ADMIN'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::apiResource('ecoles', EcoleController::class)->parameters(['ecoles' => 'etablissement']);

        Route::post('ecoles/{etablissement}/suspendre', [AccesController::class, 'suspendre'])->name('ecoles.suspendre');
        Route::post('ecoles/{etablissement}/reactiver', [AccesController::class, 'reactiver'])->name('ecoles.reactiver');
        Route::post('ecoles/{etablissement}/archiver', [AccesController::class, 'archiver'])->name('ecoles.archiver');

        Route::put('ecoles/{etablissement}/personnalisation', [PersonnalisationController::class, 'update'])->name('ecoles.personnalisation.update');
        Route::post('ecoles/{etablissement}/logo', [PersonnalisationController::class, 'uploaderLogo'])->name('ecoles.logo.upload');

        Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    });
