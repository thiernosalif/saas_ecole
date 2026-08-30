<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Livewire;

use App\Domain\Scolarite\Models\Personne;
use App\Domain\Scolarite\Services\EleveService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Élève')]
class EleveForm extends Component
{
    public ?Personne $eleve = null;

    public string $nom = '';

    public string $prenom = '';

    public ?string $date_naissance = null;

    public ?string $lieu_naissance = null;

    public ?string $genre = null;

    public ?string $matricule = null;

    public ?string $telephone = null;

    public ?string $email = null;

    public ?string $adresse = null;

    public ?string $nationalite = null;

    public ?string $num_acte_naissance = null;

    public ?string $groupe_sanguin = null;

    public ?string $allergies = null;

    public bool $actif = true;

    public function mount(?Personne $eleve = null): void
    {
        $this->authorize($eleve ? 'update' : 'create', $eleve ?? Personne::class);

        if ($eleve) {
            $this->eleve = $eleve;
            $this->fill($eleve->only([
                'nom', 'prenom', 'lieu_naissance', 'genre', 'matricule',
                'telephone', 'email', 'adresse', 'nationalite', 'num_acte_naissance',
                'groupe_sanguin', 'allergies', 'actif',
            ]));
            $this->date_naissance = $eleve->date_naissance?->format('Y-m-d');
        }
    }

    protected function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'date_naissance' => ['required', 'date'],
            'lieu_naissance' => ['nullable', 'string', 'max:100'],
            'genre' => ['nullable', 'string', 'in:M,F'],
            'matricule' => ['nullable', 'string', 'max:50'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'adresse' => ['nullable', 'string'],
            'nationalite' => ['nullable', 'string', 'max:50'],
            'num_acte_naissance' => ['required', 'string', 'max:50'],
            'groupe_sanguin' => ['nullable', 'string', 'max:5'],
            'allergies' => ['nullable', 'string'],
            'actif' => ['boolean'],
        ];
    }

    public function save(EleveService $eleves): void
    {
        $data = $this->validate();

        if ($this->eleve) {
            $eleves->update($this->eleve, $data);
        } else {
            $eleves->create($data);
        }

        session()->flash('success', 'Élève enregistré.');

        $this->redirectRoute('scolarite.eleves.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.scolarite.eleve-form');
    }
}
