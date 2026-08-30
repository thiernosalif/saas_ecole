<?php

declare(strict_types=1);

use App\Domain\Scolarite\Models\AnneeScolaire;
use App\Domain\Scolarite\Models\Classe;
use App\Domain\Scolarite\Models\Niveau;
use App\Domain\Scolarite\Models\Personne;
use App\Domain\Scolarite\Services\InscriptionService;
use App\Domain\SuperAdmin\Models\Etablissement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Complète EleveServiceTenantIsolationTest.php : même garantie de défense en
 * profondeur pour InscriptionService, un autre point d'entrée qui applique
 * $data (potentiellement fourni hors HTTP) directement sur un modèle
 * fillable tenant_id.
 */
function creerEcoleAvecClasseEtEleve(string $slug): array
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
    $niveau = Niveau::withoutTenant()->create(['tenant_id' => $slug, 'libelle' => 'CM2', 'ordre' => 6, 'cycle' => Niveau::CYCLE_PRIMAIRE]);
    $classe = Classe::withoutTenant()->create(['tenant_id' => $slug, 'niveau_id' => $niveau->id, 'annee_id' => $annee->id, 'libelle' => 'CM2-A']);

    $eleve = Personne::withoutTenant()->create([
        'tenant_id' => $slug, 'type' => Personne::TYPE_ELEVE, 'nom' => 'Diop', 'prenom' => 'Awa', 'num_acte_naissance' => "{$slug}-ELV-001",
    ]);

    return compact('etablissement', 'annee', 'niveau', 'classe', 'eleve');
}

function bindTenantInscription(Etablissement $etablissement): void
{
    app()->instance('currentTenant', $etablissement);
    app()->instance('currentTenantId', $etablissement->tenant_id);
}

afterEach(function () {
    app()->forgetInstance('currentTenant');
    app()->forgetInstance('currentTenantId');
});

it('ignore un tenant_id injecté par l’appelant à la création d’une inscription', function () {
    $a = creerEcoleAvecClasseEtEleve('ecole-inscription-a');
    $b = creerEcoleAvecClasseEtEleve('ecole-inscription-b');
    bindTenantInscription($a['etablissement']);

    $inscription = app(InscriptionService::class)->create([
        'eleve_id' => $a['eleve']->id,
        'classe_id' => $a['classe']->id,
        'annee_id' => $a['annee']->id,
        'date_inscription' => '2025-10-01',
        'tenant_id' => $b['etablissement']->tenant_id,
    ]);

    expect($inscription->tenant_id)->toBe($a['etablissement']->tenant_id)
        ->and($inscription->tenant_id)->not->toBe($b['etablissement']->tenant_id);
});
