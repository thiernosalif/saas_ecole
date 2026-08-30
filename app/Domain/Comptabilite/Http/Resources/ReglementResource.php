<?php

declare(strict_types=1);

namespace App\Domain\Comptabilite\Http\Resources;

use App\Domain\Comptabilite\Models\Reglement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Reglement */
class ReglementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'facture_id' => $this->facture_id,
            'montant' => $this->montant,
            'moyen_paiement' => $this->moyen_paiement,
            'reference' => $this->reference,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'statut' => $this->statut,
            'notes' => $this->notes,
        ];
    }
}
