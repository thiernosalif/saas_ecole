<?php

declare(strict_types=1);

namespace App\Domain\Comptabilite\Http\Resources;

use App\Domain\Comptabilite\Models\Facture;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Facture */
class FactureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'eleve_id' => $this->eleve_id,
            'annee_id' => $this->annee_id,
            'numero' => $this->numero,
            'montant_total' => $this->montant_total,
            'montant_paye' => $this->montant_paye,
            'solde_restant' => $this->soldeRestant(),
            'statut' => $this->statut,
            'due_at' => $this->due_at?->toDateString(),
            'created_at' => $this->created_at?->toIso8601String(),
            'reglements' => ReglementResource::collection($this->whenLoaded('reglements')),
        ];
    }
}
