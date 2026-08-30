<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Services;

use App\Domain\Scolarite\Models\LienParente;
use App\Domain\Scolarite\Models\Personne;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Saisie/liaison des parents d'un élève au moment de l'inscription (fiche
 * élève, EleveForm) — pas un flux séparé. Un parent est réutilisé (retrouvé
 * par email dans le tenant courant) plutôt que dupliqué s'il est déjà lié à
 * un autre enfant de la même famille inscrit précédemment.
 */
class AccesFamilleService
{
    public function __construct(private readonly CompteUtilisateurService $comptes) {}

    /**
     * @param  array<int, array{nom: string, prenom: string, telephone?: ?string, email?: ?string, lien?: ?string, tuteur_principal?: bool, contact_urgence?: bool, creer_compte?: bool}>  $parents
     */
    public function lierParents(Personne $eleve, array $parents): Collection
    {
        return DB::transaction(fn () => collect($parents)->map(
            fn (array $donnees) => $this->lierUnParent($eleve, $donnees)
        ));
    }

    private function lierUnParent(Personne $eleve, array $donnees): Personne
    {
        $parent = $this->trouverOuCreerParent($donnees);

        LienParente::updateOrCreate(
            ['eleve_id' => $eleve->id, 'parent_id' => $parent->id],
            [
                'lien' => $donnees['lien'] ?? null,
                'tuteur_principal' => (bool) ($donnees['tuteur_principal'] ?? false),
                'contact_urgence' => (bool) ($donnees['contact_urgence'] ?? false),
            ],
        );

        if (! empty($donnees['creer_compte']) && ! User::where('personne_id', $parent->id)->exists()) {
            $this->comptes->creerCompteParent($parent);
        }

        return $parent;
    }

    private function trouverOuCreerParent(array $donnees): Personne
    {
        if (! empty($donnees['email'])) {
            $existant = Personne::where('type', Personne::TYPE_PARENT)
                ->where('email', $donnees['email'])
                ->first();

            if ($existant) {
                return $existant;
            }
        }

        return Personne::create([
            'type' => Personne::TYPE_PARENT,
            'nom' => $donnees['nom'],
            'prenom' => $donnees['prenom'],
            'telephone' => $donnees['telephone'] ?? null,
            'email' => $donnees['email'] ?? null,
        ]);
    }
}
