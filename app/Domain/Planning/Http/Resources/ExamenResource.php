<?php

declare(strict_types=1);

namespace App\Domain\Planning\Http\Resources;

use App\Domain\Planning\Models\Examen;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Examen */
class ExamenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'classe_id' => $this->classe_id,
            'matiere_id' => $this->matiere_id,
            'trimestre_id' => $this->trimestre_id,
            'salle_id' => $this->salle_id,
            'date_examen' => $this->date_examen?->toDateString(),
            'heure_debut' => $this->heure_debut,
            'duree_minutes' => $this->duree_minutes,
            'bareme' => $this->bareme,
            'libelle' => $this->libelle,
        ];
    }
}
