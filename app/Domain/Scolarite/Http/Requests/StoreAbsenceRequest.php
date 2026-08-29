<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Http\Requests;

use App\Domain\Scolarite\Models\Absence;
use Illuminate\Foundation\Http\FormRequest;

class StoreAbsenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Absence::class);
    }

    public function rules(): array
    {
        return [
            'eleve_id' => ['required', 'uuid', 'exists:personne,id'],
            'matiere_id' => ['nullable', 'uuid', 'exists:matiere,id'],
            'date' => ['required', 'date'],
            'heure_debut' => ['nullable', 'date_format:H:i'],
            'heure_fin' => ['nullable', 'date_format:H:i'],
            'type' => ['required', 'string', 'in:ABSENCE,RETARD'],
            'justifiee' => ['sometimes', 'boolean'],
            'justificatif' => ['nullable', 'string'],
        ];
    }
}
