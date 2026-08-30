<?php

use App\Domain\SuperAdmin\Http\Controllers\AbonnementController;
use App\Domain\SuperAdmin\Http\Controllers\AccesController;
use App\Domain\SuperAdmin\Http\Controllers\AnalyticsController;
use App\Domain\SuperAdmin\Http\Controllers\EcoleController;
use App\Domain\SuperAdmin\Http\Controllers\PersonnalisationController;
use App\Domain\SuperAdmin\Livewire\AnalyticsDashboard;
use App\Domain\SuperAdmin\Livewire\Communication;
use App\Domain\SuperAdmin\Livewire\EcoleDetail;
use App\Domain\SuperAdmin\Livewire\EcolesListe;
use App\Domain\SuperAdmin\Livewire\Onboarding;
use Illuminate\Support\Facades\Route;

// Portail Super Admin (§15.6) : hors ResolveTenant (pas d'école courante),
// gardé par platform.team (fixe le "team" spatie sur la plateforme) puis
// role:SUPER_ADMIN. API JSON (consommée par le portail ou un futur client) sous
// /admin/..., pages Livewire (Session 9) à la racine du domaine, cf. plus bas.
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

        Route::get('abonnements/plans', [AbonnementController::class, 'plans'])->name('abonnements.plans');
        Route::post('ecoles/{etablissement}/abonnement/plan', [AbonnementController::class, 'changerPlan'])->name('ecoles.abonnement.plan');
        Route::post('ecoles/{etablissement}/abonnement/annuler', [AbonnementController::class, 'annuler'])->name('ecoles.abonnement.annuler');
        Route::post('ecoles/{etablissement}/abonnement/payer', [AbonnementController::class, 'payer'])->name('ecoles.abonnement.payer');
        Route::get('ecoles/{etablissement}/abonnement/historique', [AbonnementController::class, 'historique'])->name('ecoles.abonnement.historique');

        Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    });

// Portail Super Admin — pages Livewire full-page (§17, Session 9).
Route::domain(config('app.admin_subdomain'))
    ->middleware(['auth', 'platform.team', 'role:SUPER_ADMIN'])
    ->name('admin.portail.')
    ->group(function () {
        Route::redirect('/', '/ecoles');

        Route::get('/ecoles', EcolesListe::class)->name('ecoles.liste');
        Route::get('/ecoles/{etablissement}', EcoleDetail::class)->name('ecoles.detail');
        Route::get('/onboarding', Onboarding::class)->name('onboarding');
        Route::get('/analytics', AnalyticsDashboard::class)->name('analytics');
        Route::get('/communication', Communication::class)->name('communication');
    });
