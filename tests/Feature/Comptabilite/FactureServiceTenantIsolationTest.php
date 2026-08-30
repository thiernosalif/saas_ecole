<?php

declare(strict_types=1);

use App\Domain\Comptabilite\Services\FactureService;
use App\Domain\Scolarite\Models\Personne;
use App\Domain\SuperAdmin\Models\Etablissement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Test obligatoire à partir de la Session 4 (cf. mémoire de session) : même
 * garantie de défense en profondeur que EleveServiceTenantIsolationTest /
 * SeanceServiceTenantIsolationTest pour FactureService.
 */
function creerEcoleAvecEleveFacture(string $slug): array
{
    $etablissement = Etablissement::create([
        'tenant_id' => $slug, 'nom' => "École {$slug}", 'sous_domaine' => $slug, 'statut' => 'ACTIF',
    ]);

    $eleve = Personne::withoutTenant()->create([
        'tenant_id' => $slug, 'type' => Personne::TYPE_ELEVE, 'nom' => 'Diop', 'prenom' => 'Awa', 'num_acte_naissance' => "{$slug}-001",
    ]);

    return compact('etablissement', 'eleve');
}

function bindTenantFacture(Etablissement $etablissement): void
{
    app()->instance('currentTenant', $etablissement);
    app()->instance('currentTenantId', $etablissement->tenant_id);
}

afterEach(function () {
    app()->forgetInstance('currentTenant');
    app()->forgetInstance('currentTenantId');
});

it('ignore un tenant_id injecté par l’appelant à la création d’une facture', function () {
    $a = creerEcoleAvecEleveFacture('ecole-facture-a');
    $b = creerEcoleAvecEleveFacture('ecole-facture-b');
    bindTenantFacture($a['etablissement']);

    $facture = app(FactureService::class)->create([
        'eleve_id' => $a['eleve']->id,
        'montant_total' => 50000,
        'tenant_id' => $b['etablissement']->tenant_id,
    ]);

    expect($facture->tenant_id)->toBe($a['etablissement']->tenant_id)
        ->and($facture->tenant_id)->not->toBe($b['etablissement']->tenant_id);
});

it('le TenantScope empêche de retrouver par ID la facture d’un autre tenant', function () {
    $a = creerEcoleAvecEleveFacture('ecole-facture-c');
    $b = creerEcoleAvecEleveFacture('ecole-facture-d');

    bindTenantFacture($b['etablissement']);
    $factureB = app(FactureService::class)->create(['eleve_id' => $b['eleve']->id, 'montant_total' => 40000]);

    bindTenantFacture($a['etablissement']);

    expect(\App\Domain\Comptabilite\Models\Facture::find($factureB->id))->toBeNull()
        ->and(app(FactureService::class)->pourEleve($a['eleve']->id))->toHaveCount(0);
});
