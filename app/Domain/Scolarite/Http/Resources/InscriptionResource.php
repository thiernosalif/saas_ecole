<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Http\Resources;

use App\Domain\Scolarite\Models\Inscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Inscription */
class InscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'eleve_id' => $this->eleve_id,
            'classe_id' => $this->classe_id,
            'annee_id' => $this->annee_id,
            'statut' => $this->statut,
            'date_inscription' => $this->date_inscription?->toDateString(),
            'frais_inscription' => $this->frais_inscription,
        ];
    }
}
