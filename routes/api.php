<?php

use App\Domain\Notes\Http\Controllers\BulletinController;
use App\Domain\Notes\Http\Controllers\NoteController;
use App\Domain\Planning\Http\Controllers\DevoirController;
use App\Domain\Planning\Http\Controllers\EmploiDuTempsController;
use App\Domain\Planning\Http\Controllers\ExamenController;
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

    Route::apiResource('notes', NoteController::class)->only(['store', 'index']);
    Route::post('bulletins/generer', [BulletinController::class, 'generer']);
    Route::get('bulletins/{bulletin}/pdf', [BulletinController::class, 'pdf']);

    // Module Planning (cf. PROJET_LARAVEL.md §4.2) : "emploi-du-temps" ne
    // correspond à aucun nom de modèle (le modèle sous-jacent est Seance),
    // d'où des routes explicites plutôt qu'un apiResource.
    Route::get('emploi-du-temps', [EmploiDuTempsController::class, 'index']); // ?classe_id=|prof_id=
    Route::get('emploi-du-temps/{seance}', [EmploiDuTempsController::class, 'show']);
    Route::post('emploi-du-temps', [EmploiDuTempsController::class, 'store']);
    Route::put('emploi-du-temps/{seance}', [EmploiDuTempsController::class, 'update']); // déplacement

    Route::apiResource('examens', ExamenController::class)->only(['index', 'show', 'store', 'update']);
    Route::apiResource('devoirs', DevoirController::class)->only(['index', 'show', 'store', 'update']);
});
