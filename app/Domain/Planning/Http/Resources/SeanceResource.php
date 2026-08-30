<?php

declare(strict_types=1);

namespace App\Domain\Planning\Http\Resources;

use App\Domain\Planning\Models\Seance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Seance */
class SeanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'classe_id' => $this->classe_id,
            'matiere_id' => $this->matiere_id,
            'prof_id' => $this->prof_id,
            'salle_id' => $this->salle_id,
            'annee_id' => $this->annee_id,
            'jour_semaine' => $this->jour_semaine,
            'heure_debut' => $this->heure_debut,
            'heure_fin' => $this->heure_fin,
        ];
    }
}
