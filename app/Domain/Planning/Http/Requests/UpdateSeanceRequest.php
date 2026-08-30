<?php

declare(strict_types=1);

namespace App\Domain\Planning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSeanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('seance'));
    }

    public function rules(): array
    {
        return [
            'prof_id' => ['nullable', 'uuid', 'exists:personne,id'],
            'salle_id' => ['nullable', 'uuid', 'exists:salle,id'],
            'jour_semaine' => ['sometimes', 'integer', 'min:0', 'max:6'],
            'heure_debut' => ['sometimes', 'date_format:H:i'],
            'heure_fin' => ['sometimes', 'date_format:H:i', 'after:heure_debut'],
        ];
    }
}
