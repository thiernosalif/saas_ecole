<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClasseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('classe'));
    }

    public function rules(): array
    {
        return [
            'niveau_id' => ['nullable', 'uuid', 'exists:niveau,id'],
            'annee_id' => ['nullable', 'uuid', 'exists:annee_scolaire,id'],
            'libelle' => ['sometimes', 'string', 'max:20'],
            'capacite_max' => ['nullable', 'integer', 'min:1'],
            'prof_principal_id' => ['nullable', 'uuid', 'exists:personne,id'],
        ];
    }
}
