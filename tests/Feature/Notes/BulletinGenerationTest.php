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
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

function creerContexteBulletin(): array
{
    $etablissement = Etablissement::create([
        'tenant_id' => 'ecole-bulletin', 'nom' => 'École Bulletin', 'sous_domaine' => 'ecole-bulletin', 'statut' => 'ACTIF',
    ]);

    $admin = User::factory()->create(['tenant_id' => 'ecole-bulletin']);
    app(PermissionRegistrar::class)->setPermissionsTeamId($etablissement->id);
    Role::create(['name' => 'ECOLE_ADMIN', 'guard_name' => 'web', 'team_id' => $etablissement->id]);
    $admin->assignRole('ECOLE_ADMIN');

    $annee = AnneeScolaire::withoutTenant()->create([
        'tenant_id' => 'ecole-bulletin', 'libelle' => '2025-2026', 'date_debut' => '2025-10-01', 'date_fin' => '2026-07-01', 'active' => true,
    ]);
    $trimestre = Trimestre::withoutTenant()->create([
        'tenant_id' => 'ecole-bulletin', 'annee_id' => $annee->id, 'numero' => 1, 'date_debut' => '2025-10-01', 'date_fin' => '2025-12-20',
    ]);
    $niveau = Niveau::withoutTenant()->create(['tenant_id' => 'ecole-bulletin', 'libelle' => 'CM2', 'ordre' => 6, 'cycle' => Niveau::CYCLE_PRIMAIRE]);
    $classe = Classe::withoutTenant()->create(['tenant_id' => 'ecole-bulletin', 'niveau_id' => $niveau->id, 'annee_id' => $annee->id, 'libelle' => 'CM2-A']);

    $prof = Personne::withoutTenant()->create([
        'tenant_id' => 'ecole-bulletin', 'type' => Personne::TYPE_PROF, 'nom' => 'Ndiaye', 'prenom' => 'Omar', 'num_acte_naissance' => 'PRF-001',
    ]);

    // Coefficients volontairement simples (2 et 1) pour obtenir des moyennes
    // pondérées rondes et éviter toute ambiguïté d'arrondi dans les assertions.
    $maths = Matiere::withoutTenant()->create(['tenant_id' => 'ecole-bulletin', 'libelle' => 'Mathématiques', 'coefficient' => 2]);
    $francais = Matiere::withoutTenant()->create(['tenant_id' => 'ecole-bulletin', 'libelle' => 'Français', 'coefficient' => 1]);

    foreach ([$maths, $francais] as $matiere) {
        AffectationProf::withoutTenant()->create([
            'tenant_id' => 'ecole-bulletin', 'prof_id' => $prof->id, 'classe_id' => $classe->id,
            'matiere_id' => $matiere->id, 'annee_id' => $annee->id,
        ]);
    }

    $awa = Personne::withoutTenant()->create([
        'tenant_id' => 'ecole-bulletin', 'type' => Personne::TYPE_ELEVE, 'nom' => 'Diop', 'prenom' => 'Awa', 'num_acte_naissance' => 'ELV-001',
    ]);
    $moussa = Personne::withoutTenant()->create([
        'tenant_id' => 'ecole-bulletin', 'type' => Personne::TYPE_ELEVE, 'nom' => 'Ba', 'prenom' => 'Moussa', 'num_acte_naissance' => 'ELV-002',
    ]);

    foreach ([$awa, $moussa] as $eleve) {
        Inscription::withoutTenant()->create([
            'tenant_id' => 'ecole-bulletin', 'eleve_id' => $eleve->id, 'classe_id' => $classe->id, 'annee_id' => $annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-10-01',
        ]);
    }

    // Awa : moyenne Maths = 14, moyenne Français = 8 → générale = (14*2 + 8*1) / 3 = 12.
    Note::withoutTenant()->create(['tenant_id' => 'ecole-bulletin', 'eleve_id' => $awa->id, 'matiere_id' => $maths->id, 'trimestre_id' => $trimestre->id, 'valeur' => 14, 'coefficient' => 1]);
    Note::withoutTenant()->create(['tenant_id' => 'ecole-bulletin', 'eleve_id' => $awa->id, 'matiere_id' => $francais->id, 'trimestre_id' => $trimestre->id, 'valeur' => 8, 'coefficient' => 1]);

    // Moussa : moyenne Maths = 10, moyenne Français = 10 → générale = (10*2 + 10*1) / 3 = 10.
    Note::withoutTenant()->create(['tenant_id' => 'ecole-bulletin', 'eleve_id' => $moussa->id, 'matiere_id' => $maths->id, 'trimestre_id' => $trimestre->id, 'valeur' => 10, 'coefficient' => 1]);
    Note::withoutTenant()->create(['tenant_id' => 'ecole-bulletin', 'eleve_id' => $moussa->id, 'matiere_id' => $francais->id, 'trimestre_id' => $trimestre->id, 'valeur' => 10, 'coefficient' => 1]);

    return compact('etablissement', 'admin', 'trimestre', 'classe', 'awa', 'moussa');
}

it('calcule la moyenne pondérée par coefficient via MoyenneService', function () {
    $ctx = creerContexteBulletin();

    $service = app(App\Domain\Notes\Services\MoyenneService::class);

    expect($service->moyenneGenerale($ctx['awa']->id, $ctx['trimestre']->id))->toBe(12.0)
        ->and($service->moyenneGenerale($ctx['moussa']->id, $ctx['trimestre']->id))->toBe(10.0);
});

it('classe les élèves d’une classe par moyenne décroissante', function () {
    $ctx = creerContexteBulletin();

    $classement = app(App\Domain\Notes\Services\MoyenneService::class)
        ->classement($ctx['classe']->id, $ctx['trimestre']->id);

    expect($classement)->toHaveCount(2)
        ->and($classement[0]['eleve_id'])->toBe($ctx['awa']->id)
        ->and($classement[0]['rang'])->toBe(1)
        ->and($classement[1]['eleve_id'])->toBe($ctx['moussa']->id)
        ->and($classement[1]['rang'])->toBe(2)
        ->and($classement[0]['effectif'])->toBe(2);
});

it('génère les bulletins PDF de toute une classe via l’API et les rend téléchargeables', function () {
    Storage::fake('local');

    $ctx = creerContexteBulletin();
    Sanctum::actingAs($ctx['admin'], ['*']);

    $response = $this->postJson('http://ecole-bulletin.plateforme.sn.localhost/api/v1/bulletins/generer', [
        'trimestre_id' => $ctx['trimestre']->id,
        'classe_id' => $ctx['classe']->id,
    ])->assertOk();

    $bulletins = collect($response->json('data'));

    expect($bulletins)->toHaveCount(2);

    $bulletinAwa = $bulletins->firstWhere('eleve_id', $ctx['awa']->id);
    expect($bulletinAwa['moyenne_generale'])->toEqual(12.0)
        ->and($bulletinAwa['rang'])->toBe(1)
        ->and($bulletinAwa['effectif_classe'])->toBe(2)
        ->and($bulletinAwa['pdf_disponible'])->toBeTrue();

    Storage::disk('local')->assertExists("bulletins/{$bulletinAwa['id']}.pdf");

    $this->getJson("http://ecole-bulletin.plateforme.sn.localhost/api/v1/bulletins/{$bulletinAwa['id']}/pdf")
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
