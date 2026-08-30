<?php

declare(strict_types=1);

namespace App\Domain\Comptabilite\Services;

use App\Domain\Comptabilite\Models\EcritureComptable;
use App\Domain\Comptabilite\Models\Facture;
use App\Domain\Comptabilite\Models\Reglement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReglementService
{
    /**
     * Chemin par défaut (§Session 11) : espèces/chèque/virement, saisi
     * manuellement par ECOLE_ADMIN/SCOLARITE, toujours disponible, aucune
     * dépendance externe. Le paiement en ligne (Wave/Orange Money) est un
     * chemin distinct, cf. PaiementService, gardé derrière le module
     * `paiement_mobile_money`.
     */
    public function enregistrerPaiementManuel(Facture $facture, array $data): Reglement
    {
        unset($data['tenant_id']);

        if (! in_array($data['moyen_paiement'] ?? null, Reglement::MOYENS_MANUELS, true)) {
            throw ValidationException::withMessages([
                'moyen_paiement' => ['Moyen de paiement manuel invalide.'],
            ]);
        }

        return DB::transaction(function () use ($facture, $data) {
            $reglement = Reglement::create([
                ...$data,
                'facture_id' => $facture->id,
                'paid_at' => $data['paid_at'] ?? now(),
                'statut' => Reglement::STATUT_CONFIRME,
            ]);

            $this->appliquerAuSolde($facture, $reglement);

            return $reglement;
        });
    }

    /**
     * Met à jour le solde de la facture et journalise le mouvement — commun
     * aux deux chemins (manuel et en ligne une fois le webhook confirmé),
     * pour ne pas dupliquer la logique de solde.
     */
    public function appliquerAuSolde(Facture $facture, Reglement $reglement): void
    {
        $facture->montant_paye = bcadd((string) $facture->montant_paye, (string) $reglement->montant, 2);
        $facture->statut = bccomp((string) $facture->montant_paye, (string) $facture->montant_total, 2) >= 0
            ? Facture::STATUT_PAYEE
            : Facture::STATUT_PARTIELLE;
        $facture->save();

        EcritureComptable::create([
            'reglement_id' => $reglement->id,
            'facture_id' => $facture->id,
            'montant' => $reglement->montant,
            'moyen_paiement' => $reglement->moyen_paiement,
            'libelle' => "Règlement facture {$facture->numero}",
            'date_mouvement' => $reglement->paid_at,
        ]);
    }
}
