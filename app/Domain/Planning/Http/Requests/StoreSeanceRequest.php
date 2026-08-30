<?php

declare(strict_types=1);

namespace App\Domain\Planning\Http\Requests;

use App\Domain\Planning\Models\Seance;
use Illuminate\Foundation\Http\FormRequest;

class StoreSeanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Seance::class);
    }

    public function rules(): array
    {
        return [
            'classe_id' => ['required', 'uuid', 'exists:classe,id'],
            'matiere_id' => ['required', 'uuid', 'exists:matiere,id'],
            'prof_id' => ['nullable', 'uuid', 'exists:personne,id'],
            'salle_id' => ['nullable', 'uuid', 'exists:salle,id'],
            'annee_id' => ['nullable', 'uuid', 'exists:annee_scolaire,id'],
            'jour_semaine' => ['required', 'integer', 'min:0', 'max:6'],
            'heure_debut' => ['required', 'date_format:H:i'],
            'heure_fin' => ['required', 'date_format:H:i', 'after:heure_debut'],
        ];
    }
}
