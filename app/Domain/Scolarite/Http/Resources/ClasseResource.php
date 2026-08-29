<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Http\Resources;

use App\Domain\Scolarite\Models\Classe;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Classe */
class ClasseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'libelle' => $this->libelle,
            'capacite_max' => $this->capacite_max,
            'niveau' => $this->whenLoaded('niveau', fn () => [
                'id' => $this->niveau->id,
                'libelle' => $this->niveau->libelle,
            ]),
            'annee_scolaire' => $this->whenLoaded('anneeScolaire', fn () => [
                'id' => $this->anneeScolaire->id,
                'libelle' => $this->anneeScolaire->libelle,
            ]),
            'prof_principal_id' => $this->prof_principal_id,
        ];
    }
}
