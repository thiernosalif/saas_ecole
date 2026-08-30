<?php

declare(strict_types=1);

use App\Domain\Notes\Models\Matiere;
use App\Domain\Planning\Models\Seance;
use App\Domain\Scolarite\Models\AnneeScolaire;
use App\Domain\Scolarite\Models\Classe;
use App\Domain\Scolarite\Models\Niveau;
use App\Domain\SuperAdmin\Models\Etablissement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/**
 * Test obligatoire à partir de la Session 4 (cf. mémoire de session) : vérifie
 * que le TenantScope + ResolveTenant isolent bien l'emploi du temps de bout
 * en bout via l'API, pas seulement sur SeanceService (cf.
 * SeanceServiceTenantIsolationTest.php).
 */
function creerContextePlanningTenant(string $slug): array
{
    $etablissement = Etablissement::create([
        'tenant_id' => $slug,
        'nom' => "École {$slug}",
        'sous_domaine' => $slug,
        'statut' => 'ACTIF',
    ]);

    $admin = User::factory()->create(['tenant_id' => $slug]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($etablissement->id);
    Role::create(['name' => 'ECOLE_ADMIN', 'guard_name' => 'web', 'team_id' => $etablissement->id]);
    $admin->assignRole('ECOLE_ADMIN');

    $annee = AnneeScolaire::withoutTenant()->create([
        'tenant_id' => $slug, 'libelle' => '2025-2026', 'date_debut' => '2025-10-01', 'date_fin' => '2026-07-01', 'active' => true,
    ]);
    $niveau = Niveau::withoutTenant()->create(['tenant_id' => $slug, 'libelle' => 'CM2', 'ordre' => 6, 'cycle' => Niveau::CYCLE_PRIMAIRE]);
    $classe = Classe::withoutTenant()->create(['tenant_id' => $slug, 'niveau_id' => $niveau->id, 'annee_id' => $annee->id, 'libelle' => 'CM2-A']);
    $matiere = Matiere::withoutTenant()->create(['tenant_id' => $slug, 'libelle' => 'Mathématiques', 'coefficient' => 3]);

    $seance = Seance::withoutTenant()->create([
        'tenant_id' => $slug, 'classe_id' => $classe->id, 'matiere_id' => $matiere->id,
        'jour_semaine' => 1, 'heure_debut' => '08:00', 'heure_fin' => '09:00',
    ]);

    return compact('etablissement', 'admin', 'annee', 'niveau', 'classe', 'matiere', 'seance');
}

it('ne retourne que les séances du tenant résolu depuis le sous-domaine', function () {
    $a = creerContextePlanningTenant('ecole-planning-a');
    $b = creerContextePlanningTenant('ecole-planning-b');

    Sanctum::actingAs($a['admin'], ['*']);

    $response = $this->getJson('http://ecole-planning-a.plateforme.sn.localhost/api/v1/emploi-du-temps')
        ->assertOk();

    $ids = collect($response->json('data'))->pluck('id');

    expect($ids)->toHaveCount(1)
        ->and($ids->first())->toBe($a['seance']->id)
        ->and($ids)->not->toContain($b['seance']->id);
});

it('bloque l’accès à une séance d’un autre tenant avec un 404, pas une fuite de données', function () {
    $a = creerContextePlanningTenant('ecole-planning-a');
    $b = creerContextePlanningTenant('ecole-planning-b');

    Sanctum::actingAs($a['admin'], ['*']);

    $this->getJson("http://ecole-planning-a.plateforme.sn.localhost/api/v1/emploi-du-temps/{$b['seance']->id}")
        ->assertNotFound();
});
