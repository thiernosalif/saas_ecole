<?php

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
    //
});
