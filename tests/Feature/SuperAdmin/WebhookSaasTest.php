<?php

declare(strict_types=1);

use App\Domain\SuperAdmin\Models\Etablissement;
use App\Domain\SuperAdmin\Models\PlanTarifaire;
use App\Domain\SuperAdmin\Models\ReglementSaas;
use App\Domain\SuperAdmin\Notifications\PaiementAbonnementEchoue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/**
 * @return array{0: Etablissement, 1: User, 2: ReglementSaas}
 */
function creerEcoleAvecEcheanceEnAttente(string $slug): array
{
    $plan = PlanTarifaire::create([
        'nom' => "Plan {$slug}",
        'prix_mensuel' => 15000,
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

    $directeur = User::factory()->create(['tenant_id' => $slug]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($etablissement->id);
    Role::create(['name' => 'ECOLE_ADMIN', 'guard_name' => 'web', 'team_id' => $etablissement->id]);
    $directeur->assignRole('ECOLE_ADMIN');

    $reglement = ReglementSaas::create([
        'etablissement_id' => $etablissement->id,
        'mois' => now()->format('Y-m'),
        'montant' => 15000,
        'moyen_paiement' => ReglementSaas::MOYEN_WAVE,
        'reference' => (string) Str::uuid(),
        'statut' => ReglementSaas::STATUT_EN_ATTENTE,
    ]);

    return [$etablissement, $directeur, $reglement];
}

function envoyerWebhookWave(string $reference, bool $succes, string $secret = 'secret-test'): \Illuminate\Testing\TestResponse
{
    $payload = ['checkout_status' => $succes ? 'complete' : 'failed', 'client_reference' => $reference];
    $corps = json_encode($payload);
    $signature = hash_hmac('sha256', $corps, $secret);

    return test()->call('POST', '/api/webhooks-saas/wave', server: [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_WAVE_SIGNATURE' => $signature,
    ], content: $corps);
}

beforeEach(function () {
    Config::set('services.wave.webhook_secret', 'secret-test');
});

it('rejette une signature Wave invalide sans rien modifier', function () {
    [, , $reglement] = creerEcoleAvecEcheanceEnAttente('ecole-signature-invalide');

    $reponse = test()->call('POST', '/api/webhooks-saas/wave', server: [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_WAVE_SIGNATURE' => 'signature-bidon',
    ], content: json_encode(['checkout_status' => 'complete', 'client_reference' => $reglement->reference]));

    $reponse->assertUnauthorized();
    expect($reglement->fresh()->statut)->toBe(ReglementSaas::STATUT_EN_ATTENTE);
});

it('confirme le paiement Wave et passe l\'échéance à PAYE', function () {
    [, , $reglement] = creerEcoleAvecEcheanceEnAttente('ecole-paiement-confirme');

    envoyerWebhookWave($reglement->reference, true)->assertNoContent();

    expect($reglement->fresh()->statut)->toBe(ReglementSaas::STATUT_PAYE)
        ->and($reglement->fresh()->paid_at)->not->toBeNull();
});

it('notifie le directeur sur échec Wave sans suspendre l\'école ni sortir l\'échéance de EN_ATTENTE', function () {
    Notification::fake();
    [$etablissement, $directeur, $reglement] = creerEcoleAvecEcheanceEnAttente('ecole-paiement-echoue');

    envoyerWebhookWave($reglement->reference, false)->assertNoContent();

    expect($reglement->fresh()->statut)->toBe(ReglementSaas::STATUT_EN_ATTENTE)
        ->and($etablissement->fresh()->statut)->toBe(Etablissement::STATUT_ACTIF);

    Notification::assertSentTo($directeur, PaiementAbonnementEchoue::class);
});
