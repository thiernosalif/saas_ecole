<?php

declare(strict_types=1);

use App\Domain\Comptabilite\Models\Facture;
use App\Domain\Comptabilite\Models\Reglement;
use App\Domain\Scolarite\Models\Personne;
use App\Domain\SuperAdmin\Models\Etablissement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/**
 * Session 14 (extension) : COMPTABLE s'ajoute à ECOLE_ADMIN/SCOLARITE sur
 * FacturePolicy/ReglementPolicy, sans leur retirer aucun accès existant
 * (cf. ComptabiliteApiTest.php, inchangé par cette session).
 */
beforeEach(function () {
    $this->ecole = Etablissement::create([
        'tenant_id' => 'ecole-comptable-test', 'nom' => 'École Comptable Test', 'sous_domaine' => 'ecole-comptable-test', 'statut' => 'ACTIF',
    ]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->ecole->id);

    $this->eleve = Personne::withoutTenant()->create([
        'tenant_id' => 'ecole-comptable-test', 'type' => Personne::TYPE_ELEVE, 'nom' => 'Diop', 'prenom' => 'Awa', 'num_acte_naissance' => 'CPT-001',
    ]);

    $this->facture = Facture::withoutTenant()->create([
        'tenant_id' => 'ecole-comptable-test',
        'eleve_id' => $this->eleve->id,
        'numero' => 'FA-2026-100',
        'montant_total' => 30000,
        'montant_paye' => 0,
        'statut' => Facture::STATUT_EN_ATTENTE,
    ]);
});

function utilisateurAvecRoleComptable(Etablissement $ecole, string $role): User
{
    $user = User::factory()->create(['tenant_id' => $ecole->tenant_id]);
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web', 'team_id' => $ecole->id]);
    $user->assignRole($role);

    return $user;
}

it('permet à un COMPTABLE de lister les factures d’un élève', function () {
    $comptable = utilisateurAvecRoleComptable($this->ecole, 'COMPTABLE');
    Sanctum::actingAs($comptable, ['*']);

    $this->getJson("http://ecole-comptable-test.plateforme.sn.localhost/api/v1/factures?eleve_id={$this->eleve->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('permet à un COMPTABLE de créer une facture et d’enregistrer un règlement manuel', function () {
    $comptable = utilisateurAvecRoleComptable($this->ecole, 'COMPTABLE');
    Sanctum::actingAs($comptable, ['*']);

    $this->postJson('http://ecole-comptable-test.plateforme.sn.localhost/api/v1/factures', [
        'eleve_id' => $this->eleve->id,
        'montant_total' => 15000,
    ])->assertCreated();

    $this->postJson("http://ecole-comptable-test.plateforme.sn.localhost/api/v1/factures/{$this->facture->id}/reglements", [
        'montant' => 10000,
        'moyen_paiement' => Reglement::MOYEN_ESPECES,
    ])->assertCreated();
});

it('refuse toujours à un PARENT de créer une facture ou un règlement', function () {
    $parentPersonne = Personne::withoutTenant()->create([
        'tenant_id' => 'ecole-comptable-test', 'type' => Personne::TYPE_PARENT, 'nom' => 'Diop', 'prenom' => 'Modou', 'num_acte_naissance' => 'CPT-P01',
    ]);
    $parent = User::factory()->create(['tenant_id' => 'ecole-comptable-test', 'personne_id' => $parentPersonne->id]);
    Role::firstOrCreate(['name' => 'PARENT', 'guard_name' => 'web', 'team_id' => $this->ecole->id]);
    $parent->assignRole('PARENT');
    Sanctum::actingAs($parent, ['*']);

    $this->postJson('http://ecole-comptable-test.plateforme.sn.localhost/api/v1/factures', [
        'eleve_id' => $this->eleve->id,
        'montant_total' => 15000,
    ])->assertForbidden();

    $this->postJson("http://ecole-comptable-test.plateforme.sn.localhost/api/v1/factures/{$this->facture->id}/reglements", [
        'montant' => 10000,
        'moyen_paiement' => Reglement::MOYEN_ESPECES,
    ])->assertForbidden();
});
