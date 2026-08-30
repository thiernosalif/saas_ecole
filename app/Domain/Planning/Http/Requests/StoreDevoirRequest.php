<?php

declare(strict_types=1);

namespace App\Domain\Planning\Http\Requests;

use App\Domain\Planning\Models\Devoir;
use Illuminate\Foundation\Http\FormRequest;

class StoreDevoirRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Devoir::class);
    }

    public function rules(): array
    {
        return [
            'classe_id' => ['required', 'uuid', 'exists:classe,id'],
            'matiere_id' => ['required', 'uuid', 'exists:matiere,id'],
            'prof_id' => ['nullable', 'uuid', 'exists:personne,id'],
            'titre' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'date_remise' => ['required', 'date'],
        ];
    }
}
