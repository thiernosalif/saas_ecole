<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PersonnaliserEtablissementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'couleur_primaire' => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'couleur_secondaire' => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'nom_court' => ['sometimes', 'nullable', 'string', 'max:50'],
            'slogan' => ['sometimes', 'nullable', 'string', 'max:200'],
        ];
    }
}
