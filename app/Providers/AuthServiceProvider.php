<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Scolarite\Models\Personne;
use App\Domain\Scolarite\Policies\ElevePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // ElevePolicy gouverne le modèle Personne (table unifiée élève/parent/prof/staff,
        // cf. PROJET_LARAVEL.md §4.4) : le nom ne suit pas la convention PersonnePolicy
        // devinée par défaut, d'où l'enregistrement explicite.
        Gate::policy(Personne::class, ElevePolicy::class);
    }
}
