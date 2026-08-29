<?php

declare(strict_types=1);

use App\Domain\Notes\Models\Matiere;
use App\Domain\Notes\Models\Note;
use App\Domain\Scolarite\Models\AnneeScolaire;
use App\Domain\Scolarite\Models\Personne;
use App\Domain\Scolarite\Models\Trimestre;
use App\Domain\SuperAdmin\Models\Etablissement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ecole = Etablissement::create([
        'tenant_id' => 'ecole-note-test', 'nom' => 'École Note Test', 'sous_domaine' => 'ecole-note-test', 'statut' => 'ACTIF',
    ]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->ecole->id);

    $annee = AnneeScolaire::withoutTenant()->create([
        'tenant_id' => 'ecole-note-test', 'libelle' => '2025-2026', 'date_debut' => '2025-10-01', 'date_fin' => '2026-07-01', 'active' => true,
    ]);
    $this->trimestre = Trimestre::withoutTenant()->create([
        'tenant_id' => 'ecole-note-test', 'annee_id' => $annee->id, 'numero' => 1, 'date_debut' => '2025-10-01', 'date_fin' => '2025-12-20',
    ]);
    $this->matiere = Matiere::withoutTenant()->create(['tenant_id' => 'ecole-note-test', 'libelle' => 'Mathématiques', 'coefficient' => 2]);
    $this->eleve = Personne::withoutTenant()->create([
        'tenant_id' => 'ecole-note-test', 'type' => Personne::TYPE_ELEVE, 'nom' => 'Fall', 'prenom' => 'Khady', 'num_acte_naissance' => 'X-042',
    ]);
});

function utilisateurAvecRoleNote(Etablissement $ecole, string $role): User
{
    $user = User::factory()->create(['tenant_id' => $ecole->tenant_id]);
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web', 'team_id' => $ecole->id]);
    $user->assignRole($role);

    return $user;
}

it('permet à un PROF de saisir une note et de la retrouver dans la liste de l’élève', function () {
    $prof = utilisateurAvecRoleNote($this->ecole, 'PROF');
    Sanctum::actingAs($prof, ['*']);

    $store = $this->postJson('http://ecole-note-test.plateforme.sn.localhost/api/v1/notes', [
        'eleve_id' => $this->eleve->id,
        'matiere_id' => $this->matiere->id,
        'trimestre_id' => $this->trimestre->id,
        'valeur' => 15.5,
        'type' => 'DEVOIR',
    ])->assertCreated();

    expect(Note::withoutTenant()->find($store->json('data.id'))->valeur)->toEqual(15.5);

    $this->getJson("http://ecole-note-test.plateforme.sn.localhost/api/v1/notes?eleve_id={$this->eleve->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.valeur', 15.5);
});

it('refuse à un PARENT la saisie d’une note', function () {
    $parent = utilisateurAvecRoleNote($this->ecole, 'PARENT');
    Sanctum::actingAs($parent, ['*']);

    $this->postJson('http://ecole-note-test.plateforme.sn.localhost/api/v1/notes', [
        'eleve_id' => $this->eleve->id,
        'matiere_id' => $this->matiere->id,
        'trimestre_id' => $this->trimestre->id,
        'valeur' => 15,
    ])->assertForbidden();
});

it('rejette une note hors de l’échelle 0-20', function () {
    $prof = utilisateurAvecRoleNote($this->ecole, 'PROF');
    Sanctum::actingAs($prof, ['*']);

    $this->postJson('http://ecole-note-test.plateforme.sn.localhost/api/v1/notes', [
        'eleve_id' => $this->eleve->id,
        'matiere_id' => $this->matiere->id,
        'trimestre_id' => $this->trimestre->id,
        'valeur' => 25,
    ])->assertJsonValidationErrors('valeur');
});
