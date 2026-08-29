<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Http\Requests;

use App\Domain\Scolarite\Models\Classe;
use Illuminate\Foundation\Http\FormRequest;

class StoreClasseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Classe::class);
    }

    public function rules(): array
    {
        return [
            'niveau_id' => ['nullable', 'uuid', 'exists:niveau,id'],
            'annee_id' => ['nullable', 'uuid', 'exists:annee_scolaire,id'],
            'libelle' => ['required', 'string', 'max:20'],
            'capacite_max' => ['nullable', 'integer', 'min:1'],
            'prof_principal_id' => ['nullable', 'uuid', 'exists:personne,id'],
        ];
    }
}
