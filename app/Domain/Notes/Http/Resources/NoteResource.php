<?php

declare(strict_types=1);

namespace App\Domain\Notes\Http\Resources;

use App\Domain\Notes\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Note */
class NoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'eleve_id' => $this->eleve_id,
            'matiere_id' => $this->matiere_id,
            'trimestre_id' => $this->trimestre_id,
            'valeur' => (float) $this->valeur,
            'coefficient' => (float) $this->coefficient,
            'type' => $this->type,
            'appreciation' => $this->appreciation,
            'saisie_par' => $this->saisie_par,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
