<?php

declare(strict_types=1);

use App\Domain\Notes\Models\Matiere;
use App\Domain\Planning\Services\SeanceService;
use App\Domain\Scolarite\Models\AnneeScolaire;
use App\Domain\Scolarite\Models\Classe;
use App\Domain\Scolarite\Models\Niveau;
use App\Domain\SuperAdmin\Models\Etablissement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Test obligatoire à partir de la Session 4 (cf. mémoire de session) : même
 * garantie de défense en profondeur que AbsenceServiceTenantIsolationTest /
 * InscriptionServiceTenantIsolationTest pour SeanceService, un autre point
 * d'entrée qui applique $data (potentiellement fourni hors HTTP) directement
 * sur un modèle fillable tenant_id.
 */
function creerEcoleAvecClasseEtMatiere(string $slug): array
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
    $matiere = Matiere::withoutTenant()->create(['tenant_id' => $slug, 'libelle' => 'Mathématiques', 'coefficient' => 3]);

    return compact('etablissement', 'annee', 'niveau', 'classe', 'matiere');
}

function bindTenantSeance(Etablissement $etablissement): void
{
    app()->instance('currentTenant', $etablissement);
    app()->instance('currentTenantId', $etablissement->tenant_id);
}

afterEach(function () {
    app()->forgetInstance('currentTenant');
    app()->forgetInstance('currentTenantId');
});

it('ignore un tenant_id injecté par l’appelant à la création d’une séance', function () {
    $a = creerEcoleAvecClasseEtMatiere('ecole-seance-a');
    $b = creerEcoleAvecClasseEtMatiere('ecole-seance-b');
    bindTenantSeance($a['etablissement']);

    $seance = app(SeanceService::class)->create([
        'classe_id' => $a['classe']->id,
        'matiere_id' => $a['matiere']->id,
        'jour_semaine' => 1,
        'heure_debut' => '08:00',
        'heure_fin' => '09:00',
        'tenant_id' => $b['etablissement']->tenant_id,
    ]);

    expect($seance->tenant_id)->toBe($a['etablissement']->tenant_id)
        ->and($seance->tenant_id)->not->toBe($b['etablissement']->tenant_id);
});
