<?php

declare(strict_types=1);

use App\Domain\Scolarite\Models\Absence;
use App\Domain\Scolarite\Models\Personne;
use App\Domain\Scolarite\Services\AbsenceService;
use App\Domain\SuperAdmin\Models\Etablissement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Complète EleveServiceTenantIsolationTest.php : même garantie de défense en
 * profondeur pour AbsenceService, un autre point d'entrée qui applique $data
 * (potentiellement fourni hors HTTP) directement sur un modèle fillable
 * tenant_id.
 */
function creerEcoleAvecEleve(string $slug): array
{
    $etablissement = Etablissement::create([
        'tenant_id' => $slug,
        'nom' => "École {$slug}",
        'sous_domaine' => $slug,
        'statut' => 'ACTIF',
    ]);

    $eleve = Personne::withoutTenant()->create([
        'tenant_id' => $slug, 'type' => Personne::TYPE_ELEVE, 'nom' => 'Diop', 'prenom' => 'Awa', 'num_acte_naissance' => "{$slug}-ELV-001",
    ]);

    return compact('etablissement', 'eleve');
}

function bindTenantScolarite(Etablissement $etablissement): void
{
    app()->instance('currentTenant', $etablissement);
    app()->instance('currentTenantId', $etablissement->tenant_id);
}

afterEach(function () {
    app()->forgetInstance('currentTenant');
    app()->forgetInstance('currentTenantId');
});

it('ignore un tenant_id injecté par l’appelant à la création d’une absence', function () {
    $a = creerEcoleAvecEleve('ecole-absence-a');
    $b = creerEcoleAvecEleve('ecole-absence-b');
    bindTenantScolarite($a['etablissement']);

    $absence = app(AbsenceService::class)->create([
        'eleve_id' => $a['eleve']->id,
        'date' => '2026-01-15',
        'type' => Absence::TYPE_ABSENCE,
        'tenant_id' => $b['etablissement']->tenant_id,
    ], null);

    expect($absence->tenant_id)->toBe($a['etablissement']->tenant_id)
        ->and($absence->tenant_id)->not->toBe($b['etablissement']->tenant_id);
});
