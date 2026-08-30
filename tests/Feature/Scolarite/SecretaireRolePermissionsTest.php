<?php

declare(strict_types=1);

use App\Domain\Scolarite\Models\Personne;
use App\Domain\SuperAdmin\Models\Etablissement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/**
 * Session 14 (extension) : SECRETAIRE reçoit le même périmètre que SCOLARITE
 * sur ElevePolicy/AbsencePolicy (viewAny/view/create/update sur Personne,
 * viewAny/create sur Absence) — jamais delete, réservé à ECOLE_ADMIN seul.
 */
function utilisateurSecretaire(Etablissement $ecole): User
{
    $user = User::factory()->create(['tenant_id' => $ecole->tenant_id]);
    Role::firstOrCreate(['name' => 'SECRETAIRE', 'guard_name' => 'web', 'team_id' => $ecole->id]);
    $user->assignRole('SECRETAIRE');

    return $user;
}

it('permet à un SECRETAIRE de lister, créer et modifier un élève', function () {
    $ecole = Etablissement::create([
        'tenant_id' => 'ecole-secretaire-test', 'nom' => 'École Secrétaire Test', 'sous_domaine' => 'ecole-secretaire-test', 'statut' => 'ACTIF',
    ]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($ecole->id);
    $secretaire = utilisateurSecretaire($ecole);
    Sanctum::actingAs($secretaire, ['*']);

    $this->getJson('http://ecole-secretaire-test.plateforme.sn.localhost/api/v1/eleves')->assertOk();

    $response = $this->postJson('http://ecole-secretaire-test.plateforme.sn.localhost/api/v1/eleves', [
        'nom' => 'Ba',
        'prenom' => 'Moussa',
        'date_naissance' => '2013-01-01',
        'num_acte_naissance' => 'SEC-001',
    ])->assertCreated();

    $eleveId = $response->json('data.id');

    $this->putJson("http://ecole-secretaire-test.plateforme.sn.localhost/api/v1/eleves/{$eleveId}", [
        'telephone' => '771234567',
    ])->assertOk();
});

it('refuse à un SECRETAIRE de supprimer un élève (réservé à ECOLE_ADMIN)', function () {
    $ecole = Etablissement::create([
        'tenant_id' => 'ecole-secretaire-test', 'nom' => 'École Secrétaire Test', 'sous_domaine' => 'ecole-secretaire-test', 'statut' => 'ACTIF',
    ]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($ecole->id);

    $eleve = Personne::withoutTenant()->create([
        'tenant_id' => 'ecole-secretaire-test', 'type' => Personne::TYPE_ELEVE, 'nom' => 'Fall', 'prenom' => 'Fatou', 'num_acte_naissance' => 'SEC-002',
    ]);

    $secretaire = utilisateurSecretaire($ecole);
    Sanctum::actingAs($secretaire, ['*']);

    $this->deleteJson("http://ecole-secretaire-test.plateforme.sn.localhost/api/v1/eleves/{$eleve->id}")
        ->assertForbidden();
});

it('permet à un SECRETAIRE de créer une absence', function () {
    $ecole = Etablissement::create([
        'tenant_id' => 'ecole-secretaire-test', 'nom' => 'École Secrétaire Test', 'sous_domaine' => 'ecole-secretaire-test', 'statut' => 'ACTIF',
    ]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($ecole->id);

    $eleve = Personne::withoutTenant()->create([
        'tenant_id' => 'ecole-secretaire-test', 'type' => Personne::TYPE_ELEVE, 'nom' => 'Sow', 'prenom' => 'Ibrahima', 'num_acte_naissance' => 'SEC-003',
    ]);

    $secretaire = utilisateurSecretaire($ecole);
    Sanctum::actingAs($secretaire, ['*']);

    $this->postJson('http://ecole-secretaire-test.plateforme.sn.localhost/api/v1/absences', [
        'eleve_id' => $eleve->id,
        'date' => '2026-01-15',
        'type' => 'ABSENCE',
    ])->assertCreated();
});
