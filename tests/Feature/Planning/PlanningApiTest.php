<?php

declare(strict_types=1);

use App\Domain\Notes\Models\Matiere;
use App\Domain\Planning\Models\Salle;
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

beforeEach(function () {
    $this->ecole = Etablissement::create([
        'tenant_id' => 'ecole-planning-test', 'nom' => 'École Planning Test', 'sous_domaine' => 'ecole-planning-test', 'statut' => 'ACTIF',
    ]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->ecole->id);

    $annee = AnneeScolaire::withoutTenant()->create([
        'tenant_id' => 'ecole-planning-test', 'libelle' => '2025-2026', 'date_debut' => '2025-10-01', 'date_fin' => '2026-07-01', 'active' => true,
    ]);
    $niveau = Niveau::withoutTenant()->create(['tenant_id' => 'ecole-planning-test', 'libelle' => 'CM2', 'ordre' => 6, 'cycle' => Niveau::CYCLE_PRIMAIRE]);
    $this->classe = Classe::withoutTenant()->create(['tenant_id' => 'ecole-planning-test', 'niveau_id' => $niveau->id, 'annee_id' => $annee->id, 'libelle' => 'CM2-A']);
    $this->matiere = Matiere::withoutTenant()->create(['tenant_id' => 'ecole-planning-test', 'libelle' => 'Mathématiques', 'coefficient' => 2]);
    $this->salle = Salle::withoutTenant()->create(['tenant_id' => 'ecole-planning-test', 'nom' => 'Salle 1']);
});

function utilisateurAvecRolePlanning(Etablissement $ecole, string $role): User
{
    $user = User::factory()->create(['tenant_id' => $ecole->tenant_id]);
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web', 'team_id' => $ecole->id]);
    $user->assignRole($role);

    return $user;
}

it('permet à SCOLARITE de créer une séance', function () {
    $scolarite = utilisateurAvecRolePlanning($this->ecole, 'SCOLARITE');
    Sanctum::actingAs($scolarite, ['*']);

    $this->postJson('http://ecole-planning-test.plateforme.sn.localhost/api/v1/emploi-du-temps', [
        'classe_id' => $this->classe->id,
        'matiere_id' => $this->matiere->id,
        'salle_id' => $this->salle->id,
        'jour_semaine' => 1,
        'heure_debut' => '08:00',
        'heure_fin' => '09:00',
    ])->assertCreated();
});

it('refuse à un PROF la création d’une séance', function () {
    $prof = utilisateurAvecRolePlanning($this->ecole, 'PROF');
    Sanctum::actingAs($prof, ['*']);

    $this->postJson('http://ecole-planning-test.plateforme.sn.localhost/api/v1/emploi-du-temps', [
        'classe_id' => $this->classe->id,
        'matiere_id' => $this->matiere->id,
        'jour_semaine' => 1,
        'heure_debut' => '08:00',
        'heure_fin' => '09:00',
    ])->assertForbidden();
});

it('rejette une séance dont le créneau chevauche une salle déjà occupée', function () {
    $scolarite = utilisateurAvecRolePlanning($this->ecole, 'SCOLARITE');
    Sanctum::actingAs($scolarite, ['*']);

    $this->postJson('http://ecole-planning-test.plateforme.sn.localhost/api/v1/emploi-du-temps', [
        'classe_id' => $this->classe->id,
        'matiere_id' => $this->matiere->id,
        'salle_id' => $this->salle->id,
        'jour_semaine' => 1,
        'heure_debut' => '08:00',
        'heure_fin' => '09:00',
    ])->assertCreated();

    $this->postJson('http://ecole-planning-test.plateforme.sn.localhost/api/v1/emploi-du-temps', [
        'classe_id' => $this->classe->id,
        'matiere_id' => $this->matiere->id,
        'salle_id' => $this->salle->id,
        'jour_semaine' => 1,
        'heure_debut' => '08:30',
        'heure_fin' => '09:30',
    ])->assertJsonValidationErrors('salle_id');
});

it('permet de déplacer une séance vers un créneau libre', function () {
    $scolarite = utilisateurAvecRolePlanning($this->ecole, 'SCOLARITE');
    Sanctum::actingAs($scolarite, ['*']);

    $store = $this->postJson('http://ecole-planning-test.plateforme.sn.localhost/api/v1/emploi-du-temps', [
        'classe_id' => $this->classe->id,
        'matiere_id' => $this->matiere->id,
        'salle_id' => $this->salle->id,
        'jour_semaine' => 1,
        'heure_debut' => '08:00',
        'heure_fin' => '09:00',
    ])->assertCreated();

    $this->putJson("http://ecole-planning-test.plateforme.sn.localhost/api/v1/emploi-du-temps/{$store->json('data.id')}", [
        'jour_semaine' => 2,
        'heure_debut' => '10:00',
        'heure_fin' => '11:00',
    ])->assertOk()
        ->assertJsonPath('data.jour_semaine', 2);
});
