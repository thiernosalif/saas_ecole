<?php

declare(strict_types=1);

use App\Domain\Notes\Models\Matiere;
use App\Domain\Notes\Services\NoteService;
use App\Domain\Scolarite\Models\AnneeScolaire;
use App\Domain\Scolarite\Models\Personne;
use App\Domain\Scolarite\Models\Trimestre;
use App\Domain\SuperAdmin\Models\Etablissement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Complète EleveServiceTenantIsolationTest.php : même garantie de défense en
 * profondeur pour NoteService, un autre point d'entrée qui applique $data
 * (potentiellement fourni hors HTTP) directement sur un modèle fillable
 * tenant_id.
 */
function creerEcoleAvecMatiereEtEleve(string $slug): array
{
    $etablissement = Etablissement::create([
        'tenant_id' => $slug,
        'nom' => "École {$slug}",
        'sous_domaine' => $slug,
        'statut' => 'ACTIF',
    ]);

    $annee = AnneeScolaire::withoutTenant()->create([
        'tenant_id' => $slug, 'libelle' => '2025-2026', 'date_debut' => '2025-10-01', 'date_fin' => '2026-07-01', 'active' => true,
    ]);
    $trimestre = Trimestre::withoutTenant()->create([
        'tenant_id' => $slug, 'annee_id' => $annee->id, 'numero' => 1, 'date_debut' => '2025-10-01', 'date_fin' => '2025-12-20',
    ]);
    $matiere = Matiere::withoutTenant()->create(['tenant_id' => $slug, 'libelle' => 'Mathématiques', 'coefficient' => 3]);

    $eleve = Personne::withoutTenant()->create([
        'tenant_id' => $slug, 'type' => Personne::TYPE_ELEVE, 'nom' => 'Diop', 'prenom' => 'Awa', 'num_acte_naissance' => "{$slug}-ELV-001",
    ]);

    return compact('etablissement', 'annee', 'trimestre', 'matiere', 'eleve');
}

function bindTenantNote(Etablissement $etablissement): void
{
    app()->instance('currentTenant', $etablissement);
    app()->instance('currentTenantId', $etablissement->tenant_id);
}

afterEach(function () {
    app()->forgetInstance('currentTenant');
    app()->forgetInstance('currentTenantId');
});

it('ignore un tenant_id injecté par l’appelant à la création d’une note', function () {
    $a = creerEcoleAvecMatiereEtEleve('ecole-note-a');
    $b = creerEcoleAvecMatiereEtEleve('ecole-note-b');
    bindTenantNote($a['etablissement']);

    $note = app(NoteService::class)->create([
        'eleve_id' => $a['eleve']->id,
        'matiere_id' => $a['matiere']->id,
        'trimestre_id' => $a['trimestre']->id,
        'valeur' => 15,
        'tenant_id' => $b['etablissement']->tenant_id,
    ], null);

    expect($note->tenant_id)->toBe($a['etablissement']->tenant_id)
        ->and($note->tenant_id)->not->toBe($b['etablissement']->tenant_id);
});
