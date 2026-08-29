<?php

declare(strict_types=1);

namespace App\Domain\Notes\Http\Resources;

use App\Domain\Notes\Models\Bulletin;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Bulletin */
class BulletinResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'eleve_id' => $this->eleve_id,
            'trimestre_id' => $this->trimestre_id,
            'moyenne_generale' => $this->moyenne_generale !== null ? (float) $this->moyenne_generale : null,
            'rang' => $this->rang,
            'effectif_classe' => $this->effectif_classe,
            'mention' => $this->mention,
            'appreciation_dir' => $this->appreciation_dir,
            'nb_absences_justifiees' => $this->nb_absences_justifiees,
            'nb_absences_non_justifiees' => $this->nb_absences_non_justifiees,
            'valide' => $this->valide,
            'genere_at' => $this->genere_at?->toIso8601String(),
            'pdf_disponible' => filled($this->url_pdf),
        ];
    }
}
