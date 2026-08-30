<?php

declare(strict_types=1);

use App\Domain\Notes\Livewire\NotesSaisie;
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
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/**
 * Contexte avec un PROF affecté à une seule classe/matière (affectee), et une
 * seconde classe/matière (nonAffectee) du même tenant sur laquelle il n'a
 * aucune affectation — la scope obligatoire de la Session 12 ne doit jamais
 * lui laisser voir ou saisir de note sur cette dernière.
 */
function creerContexteAffectationProf(string $slug): array
{
    $etablissement = Etablissement::create([
        'tenant_id' => $slug,
        'nom' => "École {$slug}",
        'sous_domaine' => $slug,
        'statut' => 'ACTIF',
    ]);

    $annee = AnneeScolaire::withoutTenant()->create([
        'tenant_id' => $slug, 'libelle' => '2025-2026', 'date_debut' => '2025-10-01', 'date_fin' => '2026-07-01', 'active' => true,
    ]);
    $trimestre = Trimestre::withoutTenant()->create([
        'tenant_id' => $slug, 'annee_id' => $annee->id, 'numero' => 1, 'date_debut' => '2025-10-01', 'date_fin' => '2025-12-20',
    ]);
    $niveau = Niveau::withoutTenant()->create(['tenant_id' => $slug, 'libelle' => 'CM2', 'ordre' => 6, 'cycle' => Niveau::CYCLE_PRIMAIRE]);

    $classeAffectee = Classe::withoutTenant()->create([
        'tenant_id' => $slug, 'niveau_id' => $niveau->id, 'annee_id' => $annee->id, 'libelle' => 'CM2-A',
    ]);
    $classeNonAffectee = Classe::withoutTenant()->create([
        'tenant_id' => $slug, 'niveau_id' => $niveau->id, 'annee_id' => $annee->id, 'libelle' => 'CM2-B',
    ]);

    $matiereAffectee = Matiere::withoutTenant()->create(['tenant_id' => $slug, 'libelle' => 'Mathématiques', 'coefficient' => 3]);
    $matiereNonAffectee = Matiere::withoutTenant()->create(['tenant_id' => $slug, 'libelle' => 'Français', 'coefficient' => 3]);

    $prof = Personne::withoutTenant()->create([
        'tenant_id' => $slug, 'type' => Personne::TYPE_PROF, 'nom' => 'Ndiaye', 'prenom' => 'Omar', 'num_acte_naissance' => "{$slug}-PRF-001",
    ]);
    AffectationProf::withoutTenant()->create([
        'tenant_id' => $slug, 'prof_id' => $prof->id, 'classe_id' => $classeAffectee->id, 'matiere_id' => $matiereAffectee->id, 'annee_id' => $annee->id,
    ]);

    $profUser = User::factory()->create(['tenant_id' => $slug, 'personne_id' => $prof->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($etablissement->id);
    Role::create(['name' => 'PROF', 'guard_name' => 'web', 'team_id' => $etablissement->id]);
    $profUser->assignRole('PROF');

    $eleveAffecte = Personne::withoutTenant()->create([
        'tenant_id' => $slug, 'type' => Personne::TYPE_ELEVE, 'nom' => 'Diop', 'prenom' => 'Awa', 'num_acte_naissance' => "{$slug}-ELV-001",
    ]);
    Inscription::withoutTenant()->create([
        'tenant_id' => $slug, 'eleve_id' => $eleveAffecte->id, 'classe_id' => $classeAffectee->id, 'annee_id' => $annee->id,
        'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-10-01',
    ]);

    $eleveNonAffecte = Personne::withoutTenant()->create([
        'tenant_id' => $slug, 'type' => Personne::TYPE_ELEVE, 'nom' => 'Ba', 'prenom' => 'Moussa', 'num_acte_naissance' => "{$slug}-ELV-002",
    ]);
    Inscription::withoutTenant()->create([
        'tenant_id' => $slug, 'eleve_id' => $eleveNonAffecte->id, 'classe_id' => $classeNonAffectee->id, 'annee_id' => $annee->id,
        'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-10-01',
    ]);

    return compact(
        'etablissement', 'annee', 'trimestre', 'profUser', 'prof',
        'classeAffectee', 'classeNonAffectee', 'matiereAffectee', 'matiereNonAffectee',
        'eleveAffecte', 'eleveNonAffecte',
    );
}

function bindTenantEtRole(App\Domain\SuperAdmin\Models\Etablissement $etablissement, User $user): void
{
    Livewire::actingAs($user);
    app()->instance('currentTenantId', $etablissement->tenant_id);
    app()->instance('currentTenant', $etablissement);
    app(PermissionRegistrar::class)->setPermissionsTeamId($etablissement->id);
}

it('empêche un PROF non affecté à une classe/matière de la voir ou d’y saisir une note', function () {
    $ctx = creerContexteAffectationProf('ecole-notes-affect');
    bindTenantEtRole($ctx['etablissement'], $ctx['profUser']);

    $component = Livewire::test(NotesSaisie::class);

    // Les <select> ne proposent que la classe/matière réellement affectées.
    $component->assertSee($ctx['classeAffectee']->libelle)
        ->assertDontSee($ctx['classeNonAffectee']->libelle);

    // Tentative de contournement : forcer côté serveur une classe/matière hors affectation.
    $component->set('classe_id', $ctx['classeNonAffectee']->id)
        ->set('matiere_id', $ctx['matiereNonAffectee']->id)
        ->set('trimestre_id', $ctx['trimestre']->id);

    expect($component->get('lignes'))->toBe([]);

    $component->set('lignes.'.$ctx['eleveNonAffecte']->id.'.valeur', 15)
        ->call('save')
        ->assertForbidden();

    expect(Note::withoutTenant()->where('eleve_id', $ctx['eleveNonAffecte']->id)->count())->toBe(0);
});

it('permet à un PROF affecté de saisir puis de corriger une note pour sa classe/matière', function () {
    $ctx = creerContexteAffectationProf('ecole-notes-affect-ok');
    bindTenantEtRole($ctx['etablissement'], $ctx['profUser']);

    $component = Livewire::test(NotesSaisie::class)
        ->set('classe_id', $ctx['classeAffectee']->id)
        ->set('matiere_id', $ctx['matiereAffectee']->id)
        ->set('trimestre_id', $ctx['trimestre']->id)
        ->set('lignes.'.$ctx['eleveAffecte']->id.'.valeur', 14)
        ->call('save');

    $note = Note::where('eleve_id', $ctx['eleveAffecte']->id)->sole();
    expect((float) $note->valeur)->toBe(14.0)
        ->and($note->saisie_par)->toBe($ctx['prof']->id);

    // Correction : même contexte rechargé, la ligne doit maintenant pointer vers
    // la note existante et la mettre à jour au lieu d'en créer une seconde.
    Livewire::test(NotesSaisie::class)
        ->set('classe_id', $ctx['classeAffectee']->id)
        ->set('matiere_id', $ctx['matiereAffectee']->id)
        ->set('trimestre_id', $ctx['trimestre']->id)
        ->set('lignes.'.$ctx['eleveAffecte']->id.'.valeur', 16)
        ->call('save');

    expect(Note::where('eleve_id', $ctx['eleveAffecte']->id)->count())->toBe(1)
        ->and((float) $note->fresh()->valeur)->toBe(16.0);
});
