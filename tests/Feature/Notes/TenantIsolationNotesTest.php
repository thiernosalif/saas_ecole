<?php

declare(strict_types=1);

use App\Domain\Notes\Models\AffectationProf;
use App\Domain\Notes\Models\Matiere;
use App\Domain\Notes\Models\Note;
use App\Domain\Scolarite\Models\AnneeScolaire;
use App\Domain\Scolarite\Models\Classe;
use App\Domain\Scolarite\Models\Inscription;
use App\Domain\Scolarite\Models\Niveau;
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
 * Test obligatoire à partir de la Session 4 (cf. mémoire de session) : le module
 * Notes manipule des données scolaires sensibles (moyennes, bulletins PDF), donc
 * l'isolation tenant doit être vérifiée de bout en bout via l'API, pas seulement
 * sur le Global Scope brut.
 */
function creerContexteNotesTenant(string $slug): array
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
    $trimestre = Trimestre::withoutTenant()->create([
        'tenant_id' => $slug, 'annee_id' => $annee->id, 'numero' => 1, 'date_debut' => '2025-10-01', 'date_fin' => '2025-12-20',
    ]);
    $niveau = Niveau::withoutTenant()->create(['tenant_id' => $slug, 'libelle' => 'CM2', 'ordre' => 6, 'cycle' => Niveau::CYCLE_PRIMAIRE]);
    $classe = Classe::withoutTenant()->create(['tenant_id' => $slug, 'niveau_id' => $niveau->id, 'annee_id' => $annee->id, 'libelle' => 'CM2-A']);

    $eleve = Personne::withoutTenant()->create([
        'tenant_id' => $slug, 'type' => Personne::TYPE_ELEVE, 'nom' => 'Diop', 'prenom' => 'Awa', 'num_acte_naissance' => "{$slug}-ELV-001",
    ]);
    Inscription::withoutTenant()->create([
        'tenant_id' => $slug, 'eleve_id' => $eleve->id, 'classe_id' => $classe->id, 'annee_id' => $annee->id,
        'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-10-01',
    ]);

    $prof = Personne::withoutTenant()->create([
        'tenant_id' => $slug, 'type' => Personne::TYPE_PROF, 'nom' => 'Ndiaye', 'prenom' => 'Omar', 'num_acte_naissance' => "{$slug}-PRF-001",
    ]);
    $matiere = Matiere::withoutTenant()->create(['tenant_id' => $slug, 'libelle' => 'Mathématiques', 'coefficient' => 3]);
    AffectationProf::withoutTenant()->create([
        'tenant_id' => $slug, 'prof_id' => $prof->id, 'classe_id' => $classe->id, 'matiere_id' => $matiere->id, 'annee_id' => $annee->id,
    ]);

    $note = Note::withoutTenant()->create([
        'tenant_id' => $slug, 'eleve_id' => $eleve->id, 'matiere_id' => $matiere->id, 'trimestre_id' => $trimestre->id,
        'valeur' => 15, 'coefficient' => 1,
    ]);

    return compact('etablissement', 'admin', 'annee', 'trimestre', 'niveau', 'classe', 'eleve', 'matiere', 'note');
}

it('ne retourne jamais les notes d’un autre tenant même en forçant son eleve_id', function () {
    $a = creerContexteNotesTenant('ecole-notes-a');
    $b = creerContexteNotesTenant('ecole-notes-b');

    Sanctum::actingAs($a['admin'], ['*']);

    $reponseA = $this->getJson("http://ecole-notes-a.plateforme.sn.localhost/api/v1/notes?eleve_id={$a['eleve']->id}")
        ->assertOk();

    expect(collect($reponseA->json('data'))->pluck('id'))->toContain($a['note']->id);

    // Même en passant l'eleve_id de B, le TenantScope empêche de voir ses notes
    // depuis le contexte tenant A.
    $this->getJson("http://ecole-notes-a.plateforme.sn.localhost/api/v1/notes?eleve_id={$b['eleve']->id}")
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('bloque le téléchargement du bulletin PDF d’un autre tenant avec un 404, pas une fuite', function () {
    $a = creerContexteNotesTenant('ecole-notes-a');
    $b = creerContexteNotesTenant('ecole-notes-b');

    Sanctum::actingAs($b['admin'], ['*']);

    $generation = $this->postJson('http://ecole-notes-b.plateforme.sn.localhost/api/v1/bulletins/generer', [
        'trimestre_id' => $b['trimestre']->id,
        'eleve_id' => $b['eleve']->id,
    ])->assertCreated();

    $bulletinIdB = $generation->json('data.id');

    Sanctum::actingAs($a['admin'], ['*']);

    $this->getJson("http://ecole-notes-a.plateforme.sn.localhost/api/v1/bulletins/{$bulletinIdB}/pdf")
        ->assertNotFound();
});
