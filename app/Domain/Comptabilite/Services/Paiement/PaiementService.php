<?php

declare(strict_types=1);

namespace App\Domain\Comptabilite\Services\Paiement;

use App\Domain\Comptabilite\Models\Facture;
use App\Domain\Comptabilite\Models\Reglement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Chemin optionnel (module `paiement_mobile_money`, §15.1) — sandbox Wave/
 * Orange Money. Le Reglement est créé EN_ATTENTE dès l'initiation, avant
 * même l'appel HTTP au provider : c'est cette ligne pré-existante que le
 * webhook viendra faire transitionner (jamais d'INSERT côté webhook), ce
 * qui rend le traitement idempotent sans contrainte unique supplémentaire
 * en base, cf. WebhookController.
 */
class PaiementService
{
    public function initier(Facture $facture, string $moyen, array $data = []): array
    {
        if (! in_array($moyen, Reglement::MOYENS_EN_LIGNE, true)) {
            throw ValidationException::withMessages([
                'moyen_paiement' => ['Moyen de paiement en ligne invalide.'],
            ]);
        }

        $reglement = DB::transaction(fn () => Reglement::create([
            'facture_id' => $facture->id,
            'montant' => $data['montant'] ?? $facture->soldeRestant(),
            'moyen_paiement' => $moyen,
            'reference' => (string) Str::uuid(),
            // Rempli dès l'initiation (colonne NOT NULL, §7) — mis à jour à
            // la date réelle de confirmation par le webhook.
            'paid_at' => now(),
            'statut' => Reglement::STATUT_EN_ATTENTE,
        ]));

        try {
            $urlPaiement = $moyen === Reglement::MOYEN_WAVE
                ? $this->demarrerSessionWave($reglement)
                : $this->demarrerSessionOrangeMoney($reglement);
        } catch (\Throwable $e) {
            $reglement->update(['statut' => Reglement::STATUT_ECHEC]);

            throw ValidationException::withMessages([
                'moyen_paiement' => ["Le provider de paiement n'a pas pu être contacté."],
            ]);
        }

        return ['reglement' => $reglement, 'url_paiement' => $urlPaiement];
    }

    private function demarrerSessionWave(Reglement $reglement): string
    {
        $reponse = Http::withToken(config('services.wave.api_key'))
            ->post(config('services.wave.base_url').'/v1/checkout/sessions', [
                'amount' => (string) $reglement->montant,
                'currency' => 'XOF',
                'client_reference' => $reglement->reference,
            ])
            ->throw();

        return $reponse->json('wave_launch_url');
    }

    private function demarrerSessionOrangeMoney(Reglement $reglement): string
    {
        $reponse = Http::withToken(config('services.orange_money.client_secret'))
            ->post(config('services.orange_money.base_url').'/webpayment', [
                'merchant_key' => config('services.orange_money.merchant_key'),
                'currency' => 'XOF',
                'order_id' => $reglement->reference,
                'amount' => (string) $reglement->montant,
            ])
            ->throw();

        return $reponse->json('payment_url');
    }
}
