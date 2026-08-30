<?php

declare(strict_types=1);

namespace App\Domain\Planning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExamenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('examen'));
    }

    public function rules(): array
    {
        return [
            'trimestre_id' => ['nullable', 'uuid', 'exists:trimestre,id'],
            'salle_id' => ['nullable', 'uuid', 'exists:salle,id'],
            'date_examen' => ['sometimes', 'date'],
            'heure_debut' => ['nullable', 'date_format:H:i'],
            'duree_minutes' => ['nullable', 'integer', 'min:1'],
            'bareme' => ['sometimes', 'numeric', 'min:1'],
            'libelle' => ['nullable', 'string', 'max:200'],
        ];
    }
}
