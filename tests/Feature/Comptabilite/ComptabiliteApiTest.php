<?php

declare(strict_types=1);

use App\Domain\Comptabilite\Models\Facture;
use App\Domain\Comptabilite\Models\Reglement;
use App\Domain\Scolarite\Models\LienParente;
use App\Domain\Scolarite\Models\Personne;
use App\Domain\SuperAdmin\Models\Etablissement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ecole = Etablissement::create([
        'tenant_id' => 'ecole-compta-test', 'nom' => 'École Compta Test', 'sous_domaine' => 'ecole-compta-test', 'statut' => 'ACTIF',
    ]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->ecole->id);

    $this->eleve = Personne::withoutTenant()->create([
        'tenant_id' => 'ecole-compta-test', 'type' => Personne::TYPE_ELEVE, 'nom' => 'Diop', 'prenom' => 'Awa', 'num_acte_naissance' => 'C-001',
    ]);

    $this->facture = Facture::withoutTenant()->create([
        'tenant_id' => 'ecole-compta-test',
        'eleve_id' => $this->eleve->id,
        'numero' => 'FA-2026-001',
        'montant_total' => 50000,
        'montant_paye' => 0,
        'statut' => Facture::STATUT_EN_ATTENTE,
    ]);
});

function utilisateurAvecRoleCompta(Etablissement $ecole, string $role): User
{
    $user = User::factory()->create(['tenant_id' => $ecole->tenant_id]);
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web', 'team_id' => $ecole->id]);
    $user->assignRole($role);

    return $user;
}

it('permet à ECOLE_ADMIN d’enregistrer un paiement en espèces sans aucune configuration Wave/Orange Money', function () {
    expect(config('services.wave.api_key') ?? env('WAVE_API_KEY'))->toBeEmpty();

    $admin = utilisateurAvecRoleCompta($this->ecole, 'ECOLE_ADMIN');
    Sanctum::actingAs($admin, ['*']);

    $response = $this->postJson("http://ecole-compta-test.plateforme.sn.localhost/api/v1/factures/{$this->facture->id}/reglements", [
        'montant' => 20000,
        'moyen_paiement' => Reglement::MOYEN_ESPECES,
    ])->assertCreated()
        ->assertJsonPath('data.moyen_paiement', Reglement::MOYEN_ESPECES)
        ->assertJsonPath('data.statut', Reglement::STATUT_CONFIRME);

    $this->facture->refresh();
    expect((string) $this->facture->montant_paye)->toBe('20000.00')
        ->and($this->facture->statut)->toBe(Facture::STATUT_PARTIELLE);

    expect(\App\Domain\Comptabilite\Models\EcritureComptable::where('reglement_id', $response->json('data.id'))->exists())->toBeTrue();
});

it('un second règlement espèces solde intégralement la facture', function () {
    $scolarite = utilisateurAvecRoleCompta($this->ecole, 'SCOLARITE');
    Sanctum::actingAs($scolarite, ['*']);

    $this->postJson("http://ecole-compta-test.plateforme.sn.localhost/api/v1/factures/{$this->facture->id}/reglements", [
        'montant' => 30000,
        'moyen_paiement' => Reglement::MOYEN_CHEQUE,
        'reference' => 'CHQ-001',
    ])->assertCreated();

    $this->postJson("http://ecole-compta-test.plateforme.sn.localhost/api/v1/factures/{$this->facture->id}/reglements", [
        'montant' => 20000,
        'moyen_paiement' => Reglement::MOYEN_VIREMENT,
    ])->assertCreated();

    $this->facture->refresh();
    expect((string) $this->facture->montant_paye)->toBe('50000.00')
        ->and($this->facture->statut)->toBe(Facture::STATUT_PAYEE);
});

it('refuse à un PROF d’enregistrer un règlement manuel', function () {
    $prof = utilisateurAvecRoleCompta($this->ecole, 'PROF');
    Sanctum::actingAs($prof, ['*']);

    $this->postJson("http://ecole-compta-test.plateforme.sn.localhost/api/v1/factures/{$this->facture->id}/reglements", [
        'montant' => 10000,
        'moyen_paiement' => Reglement::MOYEN_ESPECES,
    ])->assertForbidden();
});

it('rejette un moyen de paiement en ligne sur le chemin manuel', function () {
    $admin = utilisateurAvecRoleCompta($this->ecole, 'ECOLE_ADMIN');
    Sanctum::actingAs($admin, ['*']);

    $this->postJson("http://ecole-compta-test.plateforme.sn.localhost/api/v1/factures/{$this->facture->id}/reglements", [
        'montant' => 10000,
        'moyen_paiement' => Reglement::MOYEN_WAVE,
    ])->assertJsonValidationErrors('moyen_paiement');
});

it('un PARENT ne voit que les factures de son propre enfant', function () {
    $autreEleve = Personne::withoutTenant()->create([
        'tenant_id' => 'ecole-compta-test', 'type' => Personne::TYPE_ELEVE, 'nom' => 'Fall', 'prenom' => 'Ibou', 'num_acte_naissance' => 'C-002',
    ]);
    Facture::withoutTenant()->create([
        'tenant_id' => 'ecole-compta-test', 'eleve_id' => $autreEleve->id, 'montant_total' => 15000, 'montant_paye' => 0, 'statut' => Facture::STATUT_EN_ATTENTE,
    ]);

    $parentPersonne = Personne::withoutTenant()->create([
        'tenant_id' => 'ecole-compta-test', 'type' => Personne::TYPE_PARENT, 'nom' => 'Diop', 'prenom' => 'Modou', 'num_acte_naissance' => 'C-P01',
    ]);
    LienParente::withoutTenant()->create([
        'tenant_id' => 'ecole-compta-test', 'eleve_id' => $this->eleve->id, 'parent_id' => $parentPersonne->id, 'lien' => 'PERE',
    ]);

    $parentUser = User::factory()->create(['tenant_id' => 'ecole-compta-test', 'personne_id' => $parentPersonne->id]);
    Role::firstOrCreate(['name' => 'PARENT', 'guard_name' => 'web', 'team_id' => $this->ecole->id]);
    $parentUser->assignRole('PARENT');
    Sanctum::actingAs($parentUser, ['*']);

    $this->getJson("http://ecole-compta-test.plateforme.sn.localhost/api/v1/factures?eleve_id={$this->eleve->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->getJson("http://ecole-compta-test.plateforme.sn.localhost/api/v1/factures?eleve_id={$autreEleve->id}")
        ->assertForbidden();
});

it('bloque l’accès à une facture d’un autre tenant avec un 404', function () {
    $autreEcole = Etablissement::create([
        'tenant_id' => 'ecole-compta-autre', 'nom' => 'Autre École', 'sous_domaine' => 'ecole-compta-autre', 'statut' => 'ACTIF',
    ]);
    $autreEleve = Personne::withoutTenant()->create([
        'tenant_id' => 'ecole-compta-autre', 'type' => Personne::TYPE_ELEVE, 'nom' => 'Sow', 'prenom' => 'Fatou', 'num_acte_naissance' => 'D-001',
    ]);
    $factureAutreEcole = Facture::withoutTenant()->create([
        'tenant_id' => 'ecole-compta-autre', 'eleve_id' => $autreEleve->id, 'montant_total' => 15000, 'montant_paye' => 0, 'statut' => Facture::STATUT_EN_ATTENTE,
    ]);

    $admin = utilisateurAvecRoleCompta($this->ecole, 'ECOLE_ADMIN');
    Sanctum::actingAs($admin, ['*']);

    $this->getJson("http://ecole-compta-test.plateforme.sn.localhost/api/v1/factures/{$factureAutreEcole->id}")
        ->assertNotFound();
});
