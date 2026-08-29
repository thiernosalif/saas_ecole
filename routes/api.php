<?php

use App\Domain\Scolarite\Http\Controllers\AbsenceController;
use App\Domain\Scolarite\Http\Controllers\ClasseController;
use App\Domain\Scolarite\Http\Controllers\EleveController;
use App\Domain\Scolarite\Http\Controllers\InscriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — mobile Flutter (token Sanctum)
|--------------------------------------------------------------------------
|
| cf. PROJET_LARAVEL.md §6. Prefixe /api/v1, protégé par ['auth:sanctum',
| 'resolve.tenant']. Les endpoints par domaine (Scolarite, Notes, Planning,
| Comptabilite, Documents) sont ajoutés au fil des sessions 4 à 6.
|
*/

require __DIR__.'/auth.php';

Route::middleware(['auth:sanctum', 'resolve.tenant'])->prefix('v1')->group(function () {
    // ->parameters(...) : le singulariseur de Laravel transforme "eleves" en
    // "elefe" (heuristique anglaise -ves → -fe, cf. leaves/leaf), on force
    // donc explicitement le nom du paramètre de route à "eleve".
    Route::apiResource('eleves', EleveController::class)->parameters(['eleves' => 'eleve']);
    // Idem : le singulariseur donne "class" (anglais), les controllers/requêtes
    // utilisent "classe" (français) — sans ce mapping explicite, le binding de
    // route ne matche plus le nom de paramètre et $this->route('classe') (cf.
    // UpdateClasseRequest) renverrait null.
    Route::apiResource('classes', ClasseController::class)->parameters(['classes' => 'classe']);
    Route::apiResource('inscriptions', InscriptionController::class)->only(['store']);
    Route::post('transferts', [InscriptionController::class, 'transferer']);

    Route::post('absences', [AbsenceController::class, 'store']);
    Route::get('absences', [AbsenceController::class, 'index']); // ?eleve_id=
});
