<?php

use App\Domain\Notes\Livewire\NotesSaisie;
use App\Domain\Planning\Livewire\EmploiDuTemps;
use App\Domain\Scolarite\Livewire\AbsenceSaisie;
use App\Domain\Scolarite\Livewire\ClassesListe;
use App\Domain\Scolarite\Livewire\ComptesListe;
use App\Domain\Scolarite\Livewire\EleveForm;
use App\Domain\Scolarite\Livewire\ElevesListe;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Portail école — Livewire full-page, cf. PROJET_LARAVEL.md §14 (Session 6).
// `resolve.tenant` est déjà appliqué globalement aux routes Fortify (config/fortify.php)
// mais doit être redéclaré ici : ces routes ne passent pas par le groupe Fortify.
Route::middleware(['auth', 'resolve.tenant', 'user.actif', 'role:ECOLE_ADMIN,SCOLARITE,PROF'])->group(function () {
    Route::get('/eleves', ElevesListe::class)->name('scolarite.eleves.index');
    Route::get('/eleves/creer', EleveForm::class)->name('scolarite.eleves.create');
    Route::get('/eleves/{eleve}/modifier', EleveForm::class)->name('scolarite.eleves.edit');

    Route::get('/classes', ClassesListe::class)->name('scolarite.classes.index');

    Route::get('/absences/saisie', AbsenceSaisie::class)->name('scolarite.absences.saisie');

    Route::get('/notes/saisie', NotesSaisie::class)->name('notes.saisie');

    Route::get('/emploi-du-temps', EmploiDuTemps::class)->name('planning.emploi-du-temps.index');
});

// Gestion des comptes staff (PROF, SCOLARITE) : pouvoir du directeur uniquement,
// cf. CompteUtilisateurPolicy — la Policy re-vérifie de toute façon dans mount(),
// le rôle en middleware évite juste de monter le composant pour rien.
Route::middleware(['auth', 'resolve.tenant', 'user.actif', 'role:ECOLE_ADMIN'])->group(function () {
    Route::get('/comptes', ComptesListe::class)->name('scolarite.comptes.index');
});

require __DIR__.'/admin.php';
