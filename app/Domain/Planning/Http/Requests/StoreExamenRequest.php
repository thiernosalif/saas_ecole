<?php

declare(strict_types=1);

namespace App\Domain\Planning\Http\Requests;

use App\Domain\Planning\Models\Examen;
use Illuminate\Foundation\Http\FormRequest;

class StoreExamenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Examen::class);
    }

    public function rules(): array
    {
        return [
            'classe_id' => ['required', 'uuid', 'exists:classe,id'],
            'matiere_id' => ['required', 'uuid', 'exists:matiere,id'],
            'trimestre_id' => ['nullable', 'uuid', 'exists:trimestre,id'],
            'salle_id' => ['nullable', 'uuid', 'exists:salle,id'],
            'date_examen' => ['required', 'date'],
            'heure_debut' => ['nullable', 'date_format:H:i'],
            'duree_minutes' => ['nullable', 'integer', 'min:1'],
            'bareme' => ['sometimes', 'numeric', 'min:1'],
            'libelle' => ['nullable', 'string', 'max:200'],
        ];
    }
}
