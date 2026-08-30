<?php

declare(strict_types=1);

use App\Domain\Scolarite\Models\Personne;
use App\Domain\Scolarite\Services\EleveService;
use App\Domain\SuperAdmin\Models\Etablissement;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Complète TenantIsolationEleveTest.php (isolation vue depuis l'API/HTTP) en
 * testant EleveService directement, sans passer par ResolveTenant/Sanctum :
 * la garantie d'isolation doit tenir au niveau service + TenantScope, pas
 * seulement parce que le middleware HTTP filtre par sous-domaine.
 */
function creerEcole(string $slug): Etablissement
{
    return Etablissement::create([
        'tenant_id' => $slug,
        'nom' => "École {$slug}",
        'sous_domaine' => $slug,
        'statut' => 'ACTIF',
    ]);
}

function bindTenant(Etablissement $etablissement): void
{
    app()->instance('currentTenant', $etablissement);
    app()->instance('currentTenantId', $etablissement->tenant_id);
}

afterEach(function () {
    app()->forgetInstance('currentTenant');
    app()->forgetInstance('currentTenantId');
});

it('assigne automatiquement le tenant courant à la création, sans le demander', function () {
    $ecoleA = creerEcole('ecole-a');
    bindTenant($ecoleA);

    $eleve = app(EleveService::class)->create([
        'nom' => 'Diop',
        'prenom' => 'Awa',
        'num_acte_naissance' => 'A-001',
    ]);

    expect($eleve->tenant_id)->toBe($ecoleA->tenant_id);
});

it('ignore un tenant_id injecté par l’appelant à la création', function () {
    $ecoleA = creerEcole('ecole-a');
    $ecoleB = creerEcole('ecole-b');
    bindTenant($ecoleA);

    $eleve = app(EleveService::class)->create([
        'nom' => 'Diop',
        'prenom' => 'Awa',
        'num_acte_naissance' => 'A-001',
        'tenant_id' => $ecoleB->tenant_id,
    ]);

    expect($eleve->tenant_id)->toBe($ecoleA->tenant_id)
        ->and($eleve->tenant_id)->not->toBe($ecoleB->tenant_id);
});

it('empêche de faire migrer un élève vers un autre tenant via update()', function () {
    $ecoleA = creerEcole('ecole-a');
    $ecoleB = creerEcole('ecole-b');
    bindTenant($ecoleA);

    $eleve = app(EleveService::class)->create([
        'nom' => 'Diop',
        'prenom' => 'Awa',
        'num_acte_naissance' => 'A-001',
    ]);

    $eleve = app(EleveService::class)->update($eleve, [
        'nom' => 'Fall',
        'tenant_id' => $ecoleB->tenant_id,
    ]);

    expect($eleve->nom)->toBe('Fall')
        ->and($eleve->tenant_id)->toBe($ecoleA->tenant_id);
});

it('ne retourne jamais les élèves d’un autre tenant via le Global Scope', function () {
    $ecoleA = creerEcole('ecole-a');
    $ecoleB = creerEcole('ecole-b');

    bindTenant($ecoleA);
    $eleveA = app(EleveService::class)->create([
        'nom' => 'Diop',
        'prenom' => 'Awa',
        'num_acte_naissance' => 'A-001',
    ]);

    bindTenant($ecoleB);
    $eleveB = app(EleveService::class)->create([
        'nom' => 'Ba',
        'prenom' => 'Moussa',
        'num_acte_naissance' => 'B-001',
    ]);

    // Toujours dans le contexte tenant B : la requête que EleveController::index()
    // exécute (Personne::eleves()->get()) ne doit voir que l'élève B.
    $visibles = Personne::eleves()->get();
    expect($visibles)->toHaveCount(1)
        ->and($visibles->first()->id)->toBe($eleveB->id);

    bindTenant($ecoleA);
    $visibles = Personne::eleves()->get();
    expect($visibles)->toHaveCount(1)
        ->and($visibles->first()->id)->toBe($eleveA->id);
});

it('rend un élève d’un autre tenant introuvable (ModelNotFoundException), jamais chargeable pour update/delete', function () {
    $ecoleA = creerEcole('ecole-a');
    $ecoleB = creerEcole('ecole-b');

    bindTenant($ecoleB);
    $eleveB = app(EleveService::class)->create([
        'nom' => 'Ba',
        'prenom' => 'Moussa',
        'num_acte_naissance' => 'B-001',
    ]);

    // Le contexte tenant A est celui qui serait résolu par ResolveTenant pour
    // une requête sur ecole-a.plateforme.sn : la liaison de route (Personne
    // $eleve dans EleveController) passe par exactement cette même requête.
    bindTenant($ecoleA);

    expect(fn () => Personne::findOrFail($eleveB->id))
        ->toThrow(ModelNotFoundException::class);
});

it('supprime uniquement l’élève du tenant courant, sans affecter les autres tenants', function () {
    $ecoleA = creerEcole('ecole-a');
    $ecoleB = creerEcole('ecole-b');

    bindTenant($ecoleA);
    $eleveA = app(EleveService::class)->create([
        'nom' => 'Diop',
        'prenom' => 'Awa',
        'num_acte_naissance' => 'A-001',
    ]);

    bindTenant($ecoleB);
    $eleveB = app(EleveService::class)->create([
        'nom' => 'Ba',
        'prenom' => 'Moussa',
        'num_acte_naissance' => 'B-001',
    ]);

    bindTenant($ecoleA);
    app(EleveService::class)->delete($eleveA);

    expect(Personne::withoutTenant()->find($eleveA->id))->toBeNull()
        ->and(Personne::withoutTenant()->find($eleveB->id))->not->toBeNull();
});
