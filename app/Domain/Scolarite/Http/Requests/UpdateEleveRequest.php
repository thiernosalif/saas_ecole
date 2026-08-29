<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEleveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('eleve'));
    }

    public function rules(): array
    {
        return [
            'nom' => ['sometimes', 'string', 'max:100'],
            'prenom' => ['sometimes', 'string', 'max:100'],
            'date_naissance' => ['sometimes', 'date'],
            'lieu_naissance' => ['nullable', 'string', 'max:100'],
            'genre' => ['nullable', 'string', 'in:M,F'],
            'matricule' => ['nullable', 'string', 'max:50'],
            'photo_url' => ['nullable', 'string'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'adresse' => ['nullable', 'string'],
            'nationalite' => ['nullable', 'string', 'max:50'],
            'num_acte_naissance' => ['sometimes', 'string', 'max:50'],
            'groupe_sanguin' => ['nullable', 'string', 'max:5'],
            'allergies' => ['nullable', 'string'],
            'actif' => ['sometimes', 'boolean'],
        ];
    }
}
