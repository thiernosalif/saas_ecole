<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Http\Resources;

use App\Domain\SuperAdmin\Models\ReglementSaas;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReglementSaas */
class ReglementSaasResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mois' => $this->mois,
            'montant' => $this->montant,
            'moyen_paiement' => $this->moyen_paiement,
            'statut' => $this->statut,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
