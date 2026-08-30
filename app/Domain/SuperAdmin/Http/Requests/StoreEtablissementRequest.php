<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEtablissementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:200'],
            'adresse' => ['nullable', 'string'],
            'ville' => ['nullable', 'string', 'max:100'],
            'telephone_ecole' => ['nullable', 'string', 'max:20'],
            'contact_directeur' => ['nullable', 'string', 'max:150'],
            'email_directeur' => ['required', 'email', 'max:150', 'unique:users,email'],
            'plan_id' => ['nullable', 'uuid', 'exists:plan_tarifaire,id'],
            'essai_gratuit' => ['nullable', 'boolean'],
            'couleur_primaire' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'couleur_secondaire' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ];
    }
}
