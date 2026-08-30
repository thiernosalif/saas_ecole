<?php

declare(strict_types=1);

use App\Domain\SuperAdmin\Models\Etablissement;
use App\Domain\SuperAdmin\Models\PlanTarifaire;
use App\Domain\SuperAdmin\Models\ReglementSaas;
use App\Domain\SuperAdmin\Services\AbonnementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function creerEcoleAvecPlan(string $slug, int $prixMensuel = 15000): array
{
    $plan = PlanTarifaire::create([
        'nom' => "Plan {$slug}",
        'prix_mensuel' => $prixMensuel,
        'modules_inclus' => ['scolarite'],
        'actif' => true,
    ]);

    $etablissement = Etablissement::create([
        'tenant_id' => $slug,
        'nom' => "École {$slug}",
        'sous_domaine' => $slug,
        'statut' => Etablissement::STATUT_ACTIF,
        'plan_id' => $plan->id,
    ]);

    return [$etablissement, $plan];
}

it('change de plan et met à jour plan_tarifaire local', function () {
    [$etablissement, $planInitial] = creerEcoleAvecPlan('ecole-changement-plan');
    $planSuperieur = PlanTarifaire::create([
        'nom' => 'Standard',
        'prix_mensuel' => 35000,
        'modules_inclus' => ['scolarite', 'notes'],
        'actif' => true,
    ]);

    $etablissement = app(AbonnementService::class)->changerPlan($etablissement, $planSuperieur);

    expect($etablissement->plan_id)->toBe($planSuperieur->id)
        ->and($etablissement->plan_id)->not->toBe($planInitial->id);
});

it('annule un abonnement en retirant le plan sans toucher au statut de l\'école', function () {
    [$etablissement] = creerEcoleAvecPlan('ecole-annulation');

    $etablissement = app(AbonnementService::class)->annuler($etablissement);

    expect($etablissement->plan_id)->toBeNull()
        ->and($etablissement->statut)->toBe(Etablissement::STATUT_ACTIF);
});

it('génère une échéance idempotente pour le mois courant', function () {
    [$etablissement] = creerEcoleAvecPlan('ecole-echeance', 15000);
    $abonnements = app(AbonnementService::class);

    $premiere = $abonnements->genererEcheanceCourante($etablissement);
    $seconde = $abonnements->genererEcheanceCourante($etablissement->fresh());

    expect($premiere->id)->toBe($seconde->id)
        ->and(ReglementSaas::where('etablissement_id', $etablissement->id)->count())->toBe(1)
        ->and((float) $premiere->montant)->toBe(15000.0)
        ->and($premiere->statut)->toBe(ReglementSaas::STATUT_EN_ATTENTE);
});
