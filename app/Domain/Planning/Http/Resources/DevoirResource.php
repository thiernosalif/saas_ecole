<?php

declare(strict_types=1);

namespace App\Domain\Planning\Http\Resources;

use App\Domain\Planning\Models\Devoir;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Devoir */
class DevoirResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'classe_id' => $this->classe_id,
            'matiere_id' => $this->matiere_id,
            'prof_id' => $this->prof_id,
            'titre' => $this->titre,
            'description' => $this->description,
            'date_remise' => $this->date_remise?->toDateString(),
        ];
    }
}
