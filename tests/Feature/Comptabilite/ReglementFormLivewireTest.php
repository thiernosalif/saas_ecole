<?php

declare(strict_types=1);

use App\Domain\Comptabilite\Livewire\FacturesListe;
use App\Domain\Comptabilite\Livewire\ReglementForm;
use App\Domain\Comptabilite\Models\Facture;
use App\Domain\Comptabilite\Models\Reglement;
use App\Domain\Scolarite\Models\Personne;
use App\Domain\SuperAdmin\Models\Etablissement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

function ecoleAvecFactureEtStaff(string $slug, string $role): array
{
    $etablissement = Etablissement::create([
        'tenant_id' => $slug, 'nom' => "École {$slug}", 'sous_domaine' => $slug, 'statut' => 'ACTIF',
    ]);

    $eleve = Personne::withoutTenant()->create([
        'tenant_id' => $slug, 'type' => Personne::TYPE_ELEVE, 'nom' => 'Diop', 'prenom' => 'Awa', 'num_acte_naissance' => "{$slug}-001",
    ]);

    $facture = Facture::withoutTenant()->create([
        'tenant_id' => $slug, 'eleve_id' => $eleve->id, 'numero' => "FA-{$slug}",
        'montant_total' => 50000, 'montant_paye' => 0, 'statut' => Facture::STATUT_EN_ATTENTE,
    ]);

    $user = User::factory()->create(['tenant_id' => $slug]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($etablissement->id);
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web', 'team_id' => $etablissement->id]);
    $user->assignRole($role);

    return compact('etablissement', 'eleve', 'facture', 'user');
}

function connecterCommeStaff(Etablissement $etablissement, User $user): void
{
    Livewire::actingAs($user);
    app()->instance('currentTenantId', $etablissement->tenant_id);
    app()->instance('currentTenant', $etablissement);
    app(PermissionRegistrar::class)->setPermissionsTeamId($etablissement->id);
}

/**
 * Le prompt de session exige explicitement qu'un règlement manuel saisi depuis
 * le portail (ReglementForm) applique le même solde que le chemin API déjà
 * testé en Session 11 (cf. ComptabiliteApiTest) — les deux entrées appellent
 * ReglementService::enregistrerPaiementManuel(), pas de logique dupliquée.
 */
it('un règlement manuel enregistré depuis le portail applique le même solde que le chemin API', function () {
    ['etablissement' => $ecole, 'facture' => $facture, 'user' => $admin] = ecoleAvecFactureEtStaff('ecole-portail-a', 'ECOLE_ADMIN');
    connecterCommeStaff($ecole, $admin);

    Livewire::test(ReglementForm::class, ['facture' => $facture])
        ->set('montant', '20000')
        ->set('moyen_paiement', Reglement::MOYEN_ESPECES)
        ->call('save')
        ->assertHasNoErrors();

    $facture->refresh();
    expect((string) $facture->montant_paye)->toBe('20000.00')
        ->and($facture->statut)->toBe(Facture::STATUT_PARTIELLE);

    Livewire::test(ReglementForm::class, ['facture' => $facture])
        ->set('montant', '30000')
        ->set('moyen_paiement', Reglement::MOYEN_CHEQUE)
        ->set('reference', 'CHQ-001')
        ->call('save')
        ->assertHasNoErrors();

    $facture->refresh();
    expect((string) $facture->montant_paye)->toBe('50000.00')
        ->and($facture->statut)->toBe(Facture::STATUT_PAYEE);

    expect(\App\Domain\Comptabilite\Models\EcritureComptable::where('facture_id', $facture->id)->count())->toBe(2);
});

it('refuse à un PROF d’ouvrir ReglementForm ou d’y enregistrer un règlement', function () {
    ['etablissement' => $ecole, 'facture' => $facture, 'user' => $prof] = ecoleAvecFactureEtStaff('ecole-portail-b', 'PROF');
    connecterCommeStaff($ecole, $prof);

    Livewire::test(ReglementForm::class, ['facture' => $facture])
        ->assertForbidden();
});

it('rejette un moyen de paiement en ligne saisi depuis le portail', function () {
    ['etablissement' => $ecole, 'facture' => $facture, 'user' => $admin] = ecoleAvecFactureEtStaff('ecole-portail-c', 'ECOLE_ADMIN');
    connecterCommeStaff($ecole, $admin);

    Livewire::test(ReglementForm::class, ['facture' => $facture])
        ->set('montant', '10000')
        ->set('moyen_paiement', Reglement::MOYEN_WAVE)
        ->call('save')
        ->assertHasErrors(['moyen_paiement']);

    expect((string) $facture->fresh()->montant_paye)->toBe('0.00');
});

/**
 * Défense en profondeur pour la nouvelle surface Livewire (§règle de session à
 * partir de la Session 4) : le TenantScope sur Facture/Personne doit empêcher
 * un membre du staff d'une école de charger la page ReglementForm/FacturesListe
 * sur la facture ou l'élève d'une autre école, même en devinant l'UUID dans
 * l'URL. Vérifié via une vraie requête HTTP (pas Livewire::test() avec un objet
 * déjà chargé hors-tenant, qui contournerait la résolution de route model
 * binding — donc le TenantScope — sans rien prouver).
 */
it('bloque avec un 404 l’accès à la facture ou à l’élève d’un autre tenant depuis le portail', function () {
    ['facture' => $factureAutreEcole, 'eleve' => $eleveAutreEcole] = ecoleAvecFactureEtStaff('ecole-portail-d', 'ECOLE_ADMIN');
    ['user' => $admin] = ecoleAvecFactureEtStaff('ecole-portail-e', 'ECOLE_ADMIN');

    $this->actingAs($admin)
        ->get("http://ecole-portail-e.plateforme.sn.localhost/factures/{$factureAutreEcole->id}/reglements")
        ->assertNotFound();

    $this->actingAs($admin)
        ->get("http://ecole-portail-e.plateforme.sn.localhost/eleves/{$eleveAutreEcole->id}/factures")
        ->assertNotFound();
});
