<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Services;

use App\Domain\SuperAdmin\Models\Etablissement;
use App\Domain\SuperAdmin\Models\PlanTarifaire;
use App\Domain\SuperAdmin\Models\ReglementSaas;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Facturation de l'abonnement plateforme d'une école (Session 13). Pas de
 * Stripe/Cashier ici : `reglement_saas` (Session 8) reste la seule source
 * d'échéance, désormais réglable aussi en ligne (Wave/Orange Money, même
 * intégration que la Comptabilite en Session 11) en plus du règlement
 * manuel existant. `CheckAbonnementsCommand` continue de piloter le cycle
 * de rappel/suspension à partir des lignes EN_ATTENTE qu'on produit ici.
 */
class AbonnementService
{
    public function creerAbonnement(Etablissement $etablissement, PlanTarifaire $plan): Etablissement
    {
        $etablissement->update(['plan_id' => $plan->id]);
        $this->genererEcheanceCourante($etablissement->fresh());

        return $etablissement->fresh();
    }

    public function changerPlan(Etablissement $etablissement, PlanTarifaire $plan): Etablissement
    {
        $etablissement->update(['plan_id' => $plan->id]);

        return $etablissement->fresh();
    }

    /**
     * Annulation volontaire par le Super Admin : retire le plan sans jamais
     * toucher à `statut` — la suspension/l'archivage restent le rôle exclusif
     * d'AccesService + CheckAbonnementsCommand (§15.4), pas de celui-ci.
     */
    public function annuler(Etablissement $etablissement): Etablissement
    {
        $etablissement->update(['plan_id' => null]);

        return $etablissement->fresh();
    }

    /**
     * Idempotent : une seule ligne EN_ATTENTE par école et par mois, réutilisée
     * si déjà créée (ex: un deuxième clic « payer maintenant » le même mois).
     */
    public function genererEcheanceCourante(Etablissement $etablissement): ReglementSaas
    {
        $plan = $etablissement->plan ?? $etablissement->load('plan')->plan;

        if (! $plan) {
            throw ValidationException::withMessages([
                'plan_id' => ["Cette école n'a aucun plan tarifaire actif."],
            ]);
        }

        $mois = now()->format('Y-m');

        $existante = ReglementSaas::where('etablissement_id', $etablissement->id)
            ->where('mois', $mois)
            ->first();

        if ($existante) {
            return $existante;
        }

        return ReglementSaas::create([
            'etablissement_id' => $etablissement->id,
            'mois' => $mois,
            'montant' => $plan->prix_mensuel,
            'statut' => ReglementSaas::STATUT_EN_ATTENTE,
        ]);
    }

    public function initierPaiement(ReglementSaas $reglement, string $moyen): array
    {
        if (! in_array($moyen, ReglementSaas::MOYENS_EN_LIGNE, true)) {
            throw ValidationException::withMessages([
                'moyen_paiement' => ['Moyen de paiement en ligne invalide.'],
            ]);
        }

        if ($reglement->statut !== ReglementSaas::STATUT_EN_ATTENTE) {
            throw ValidationException::withMessages([
                'statut' => ['Cette échéance a déjà été réglée.'],
            ]);
        }

        $reference = $reglement->reference ?? (string) Str::uuid();

        DB::transaction(fn () => $reglement->update([
            'moyen_paiement' => $moyen,
            'reference' => $reference,
        ]));

        $urlPaiement = $moyen === ReglementSaas::MOYEN_WAVE
            ? $this->demarrerSessionWave($reglement->fresh())
            : $this->demarrerSessionOrangeMoney($reglement->fresh());

        return ['reglement' => $reglement->fresh(), 'url_paiement' => $urlPaiement];
    }

    /**
     * @return Collection<int, ReglementSaas>
     */
    public function historique(Etablissement $etablissement, int $limite = 12): Collection
    {
        return $etablissement->reglementsSaas()->orderByDesc('mois')->limit($limite)->get();
    }

    private function demarrerSessionWave(ReglementSaas $reglement): string
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

    private function demarrerSessionOrangeMoney(ReglementSaas $reglement): string
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
