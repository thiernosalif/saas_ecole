<?php

declare(strict_types=1);

use App\Domain\Comptabilite\Models\Facture;
use App\Domain\Notes\Models\Matiere;
use App\Domain\Notes\Models\Note;
use App\Domain\Scolarite\Models\AnneeScolaire;
use App\Domain\Scolarite\Models\LienParente;
use App\Domain\Scolarite\Models\Personne;
use App\Domain\Scolarite\Models\Trimestre;
use App\Domain\SuperAdmin\Models\Etablissement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/**
 * Test obligatoire (Session 14 (2/2), §14) : maintenant que des comptes
 * PARENT/ELEVE peuvent réellement exister, le risque n° 1 n'est plus
 * l'isolation tenant (déjà couverte ailleurs, cf. TenantIsolationNotesTest,
 * ComptableRolePermissionsTest) mais l'isolation lien_parente À L'INTÉRIEUR
 * d'un même tenant : un PARENT ne doit voir ni les factures ni les notes
 * d'un autre élève de la même école/classe que le sien, même en devinant son
 * eleve_id — FacturePolicy::view le faisait déjà correctement (§4.4), mais
 * NoteController::index() ne vérifiait que le rôle (NotePolicy::viewAny),
 * jamais la propriété de l'élève demandé : corrigé dans cette session.
 */
beforeEach(function () {
    $this->ecole = Etablissement::create([
        'tenant_id' => 'ecole-famille-test', 'nom' => 'École Famille Test',
        'sous_domaine' => 'ecole-famille-test', 'statut' => 'ACTIF',
    ]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->ecole->id);
    foreach (['ECOLE_ADMIN', 'PARENT', 'ELEVE'] as $role) {
        Role::create(['name' => $role, 'guard_name' => 'web', 'team_id' => $this->ecole->id]);
    }

    $slug = $this->ecole->tenant_id;

    // Deux élèves de la même école/classe, chacun avec son propre parent —
    // aucun lien entre le parent A et l'élève B.
    $this->eleveA = Personne::withoutTenant()->create(['tenant_id' => $slug, 'type' => Personne::TYPE_ELEVE, 'nom' => 'Diop', 'prenom' => 'Awa', 'num_acte_naissance' => 'FAM-A']);
    $this->eleveB = Personne::withoutTenant()->create(['tenant_id' => $slug, 'type' => Personne::TYPE_ELEVE, 'nom' => 'Ba', 'prenom' => 'Moussa', 'num_acte_naissance' => 'FAM-B']);

    $parentA = Personne::withoutTenant()->create(['tenant_id' => $slug, 'type' => Personne::TYPE_PARENT, 'nom' => 'Diop', 'prenom' => 'Modou', 'email' => 'modou@famille-test.test']);
    $parentB = Personne::withoutTenant()->create(['tenant_id' => $slug, 'type' => Personne::TYPE_PARENT, 'nom' => 'Ba', 'prenom' => 'Fatou', 'email' => 'fatou@famille-test.test']);

    LienParente::withoutTenant()->create(['tenant_id' => $slug, 'eleve_id' => $this->eleveA->id, 'parent_id' => $parentA->id, 'tuteur_principal' => true]);
    LienParente::withoutTenant()->create(['tenant_id' => $slug, 'eleve_id' => $this->eleveB->id, 'parent_id' => $parentB->id, 'tuteur_principal' => true]);

    $this->parentA = User::factory()->create(['tenant_id' => $slug, 'personne_id' => $parentA->id]);
    $this->parentA->assignRole('PARENT');

    $this->factureA = Facture::withoutTenant()->create(['tenant_id' => $slug, 'eleve_id' => $this->eleveA->id, 'numero' => 'FA-A', 'montant_total' => 10000, 'montant_paye' => 0, 'statut' => Facture::STATUT_EN_ATTENTE]);
    $this->factureB = Facture::withoutTenant()->create(['tenant_id' => $slug, 'eleve_id' => $this->eleveB->id, 'numero' => 'FA-B', 'montant_total' => 10000, 'montant_paye' => 0, 'statut' => Facture::STATUT_EN_ATTENTE]);

    $annee = AnneeScolaire::withoutTenant()->create(['tenant_id' => $slug, 'libelle' => '2025-2026', 'date_debut' => '2025-10-01', 'date_fin' => '2026-07-01', 'active' => true]);
    $trimestre = Trimestre::withoutTenant()->create(['tenant_id' => $slug, 'annee_id' => $annee->id, 'numero' => 1, 'date_debut' => '2025-10-01', 'date_fin' => '2025-12-20']);
    $matiere = Matiere::withoutTenant()->create(['tenant_id' => $slug, 'libelle' => 'Mathématiques', 'coefficient' => 2]);

    $this->noteA = Note::withoutTenant()->create(['tenant_id' => $slug, 'eleve_id' => $this->eleveA->id, 'matiere_id' => $matiere->id, 'trimestre_id' => $trimestre->id, 'valeur' => 15, 'coefficient' => 1]);
    $this->noteB = Note::withoutTenant()->create(['tenant_id' => $slug, 'eleve_id' => $this->eleveB->id, 'matiere_id' => $matiere->id, 'trimestre_id' => $trimestre->id, 'valeur' => 12, 'coefficient' => 1]);
});

it('permet à un PARENT de voir les factures de son propre enfant', function () {
    Sanctum::actingAs($this->parentA, ['*']);

    $this->getJson("http://ecole-famille-test.plateforme.sn.localhost/api/v1/factures?eleve_id={$this->eleveA->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $this->factureA->id);
});

it('bloque un PARENT qui tente de lire les factures d’un autre élève de la même école', function () {
    Sanctum::actingAs($this->parentA, ['*']);

    $this->getJson("http://ecole-famille-test.plateforme.sn.localhost/api/v1/factures?eleve_id={$this->eleveB->id}")
        ->assertForbidden();
});

it('permet à un PARENT de voir les notes de son propre enfant', function () {
    Sanctum::actingAs($this->parentA, ['*']);

    $this->getJson("http://ecole-famille-test.plateforme.sn.localhost/api/v1/notes?eleve_id={$this->eleveA->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $this->noteA->id);
});

it('bloque un PARENT qui tente de lire les notes d’un autre élève en devinant simplement son eleve_id', function () {
    Sanctum::actingAs($this->parentA, ['*']);

    // Avant le correctif de cette session, NoteController::index() ne vérifiait
    // que NotePolicy::viewAny (le rôle PARENT), jamais que $this->eleveB
    // appartient bien à ce parent — ce test aurait échoué (200 + la note de B).
    $this->getJson("http://ecole-famille-test.plateforme.sn.localhost/api/v1/notes?eleve_id={$this->eleveB->id}")
        ->assertForbidden();
});
