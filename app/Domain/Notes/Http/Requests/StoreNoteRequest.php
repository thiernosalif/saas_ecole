<?php

declare(strict_types=1);

namespace App\Domain\Notes\Http\Requests;

use App\Domain\Notes\Models\Note;
use Illuminate\Foundation\Http\FormRequest;

class StoreNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Note::class);
    }

    public function rules(): array
    {
        return [
            'eleve_id' => ['required', 'uuid', 'exists:personne,id'],
            'matiere_id' => ['required', 'uuid', 'exists:matiere,id'],
            'trimestre_id' => ['required', 'uuid', 'exists:trimestre,id'],
            'valeur' => ['required', 'numeric', 'min:0', 'max:20'],
            'coefficient' => ['sometimes', 'numeric', 'min:0.1'],
            'type' => ['nullable', 'string', 'in:DEVOIR,COMPOSITION'],
            'appreciation' => ['nullable', 'string'],
        ];
    }
}
