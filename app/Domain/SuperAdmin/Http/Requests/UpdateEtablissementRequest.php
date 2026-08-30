<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEtablissementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['sometimes', 'string', 'max:200'],
            'adresse' => ['sometimes', 'nullable', 'string'],
            'telephone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'telephone_ecole' => ['sometimes', 'nullable', 'string', 'max:20'],
            'email' => ['sometimes', 'nullable', 'email', 'max:150'],
            'contact_directeur' => ['sometimes', 'nullable', 'string', 'max:150'],
            'ville' => ['sometimes', 'nullable', 'string', 'max:100'],
            'pays' => ['sometimes', 'string', 'max:50'],
            'plan_id' => ['sometimes', 'nullable', 'uuid', 'exists:plan_tarifaire,id'],
            'nb_eleves_max' => ['sometimes', 'integer', 'min:1'],
            'stockage_max_go' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
