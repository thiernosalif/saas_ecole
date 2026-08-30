<?php

declare(strict_types=1);

use App\Domain\Notes\Models\AffectationProf;
use App\Domain\Notes\Models\Matiere;
use App\Domain\Scolarite\Models\AnneeScolaire;
use App\Domain\Scolarite\Models\Classe;
use App\Domain\Scolarite\Models\Niveau;
use App\Domain\Scolarite\Models\Personne;
use App\Domain\SuperAdmin\Models\Etablissement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/**
 * "Un compte désactivé perd l'accès (403) sans que ses données historiques
 * disparaissent" (consigne obligatoire de la session) : jamais de suppression
 * dure, seul `personne.actif = false` change (cf. CompteUtilisateurService::desactiver).
 */
function creerEcoleAvecProf(string $slug, string $password): array
{
    $etablissement = Etablissement::create([
        'tenant_id' => $slug,
        'nom' => "École {$slug}",
        'sous_domaine' => $slug,
        'statut' => 'ACTIF',
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($etablissement->id);
    Role::create(['name' => 'PROF', 'guard_name' => 'web', 'team_id' => $etablissement->id]);

    $personne = Personne::withoutTenant()->create([
        'tenant_id' => $slug,
        'type' => Personne::TYPE_PROF,
        'nom' => 'Diop',
        'prenom' => 'Awa',
        'actif' => true,
    ]);

    $prof = User::factory()->create([
        'tenant_id' => $slug,
        'personne_id' => $personne->id,
        'password' => Hash::make($password),
    ]);
    $prof->assignRole('PROF');

    return [$etablissement, $prof, $personne];
}

it('refuse la connexion d’un compte staff désactivé', function () {
    [$ecole, $prof, $personne] = creerEcoleAvecProf('ecole-a', 'secret123');

    $personne->update(['actif' => false]);

    $this->post('http://ecole-a.plateforme.sn.localhost/login', [
        'email' => $prof->email,
        'password' => 'secret123',
    ]);

    $this->assertGuest();
});

it('bloque à 403 une session déjà authentifiée dès que le compte est désactivé', function () {
    [$ecole, $prof, $personne] = creerEcoleAvecProf('ecole-a', 'secret123');

    Sanctum::actingAs($prof, ['*']);

    $this->getJson('http://ecole-a.plateforme.sn.localhost/api/v1/eleves')->assertOk();

    $personne->update(['actif' => false]);

    // Sanctum::actingAs garde le même objet User en mémoire entre deux appels de
    // test client, contrairement à une vraie requête HTTP qui résout toujours le
    // User (et sa relation personne) depuis la base à chaque fois. On simule donc
    // ici la "requête suivante" en ré-authentifiant une instance fraîche.
    Sanctum::actingAs($prof->fresh(), ['*']);

    $this->getJson('http://ecole-a.plateforme.sn.localhost/api/v1/eleves')->assertForbidden();
});

it('conserve les données historiques liées au compte désactivé', function () {
    [$ecole, $prof, $personne] = creerEcoleAvecProf('ecole-a', 'secret123');

    app()->instance('currentTenantId', $ecole->tenant_id);
    app()->instance('currentTenant', $ecole);

    $niveau = Niveau::create(['tenant_id' => $ecole->tenant_id, 'libelle' => '6ème', 'ordre' => 1, 'cycle' => Niveau::CYCLE_SECONDAIRE]);
    $annee = AnneeScolaire::create(['tenant_id' => $ecole->tenant_id, 'libelle' => '2025-2026', 'date_debut' => '2025-10-01', 'date_fin' => '2026-07-31', 'active' => true]);
    $classe = Classe::create(['tenant_id' => $ecole->tenant_id, 'niveau_id' => $niveau->id, 'annee_id' => $annee->id, 'libelle' => '6ème A', 'capacite_max' => 40]);
    $matiere = Matiere::create(['tenant_id' => $ecole->tenant_id, 'libelle' => 'Mathématiques', 'coefficient' => 3]);

    $affectation = AffectationProf::create([
        'tenant_id' => $ecole->tenant_id,
        'prof_id' => $personne->id,
        'classe_id' => $classe->id,
        'matiere_id' => $matiere->id,
        'annee_id' => $annee->id,
    ]);

    $personne->update(['actif' => false]);

    expect(AffectationProf::find($affectation->id))->not->toBeNull()
        ->and(Personne::find($personne->id))->not->toBeNull()
        ->and(Personne::find($personne->id)->nom)->toBe('Diop')
        ->and(Personne::find($personne->id)->actif)->toBeFalse();
});
