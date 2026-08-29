<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Http\Resources;

use App\Domain\Scolarite\Models\Absence;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Absence */
class AbsenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'eleve_id' => $this->eleve_id,
            'matiere_id' => $this->matiere_id,
            'date' => $this->date?->toDateString(),
            'heure_debut' => $this->heure_debut,
            'heure_fin' => $this->heure_fin,
            'type' => $this->type,
            'justifiee' => $this->justifiee,
            'justificatif' => $this->justificatif,
            'saisie_par' => $this->saisie_par,
        ];
    }
}
