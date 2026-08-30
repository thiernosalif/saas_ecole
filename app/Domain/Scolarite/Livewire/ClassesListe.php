<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Livewire;

use App\Domain\Scolarite\Models\AnneeScolaire;
use App\Domain\Scolarite\Models\Classe;
use App\Domain\Scolarite\Models\Niveau;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Classes')]
class ClassesListe extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public ?string $editingId = null;

    public ?string $confirmingDeleteId = null;

    public ?string $niveau_id = null;

    public ?string $annee_id = null;

    public string $libelle = '';

    public ?int $capacite_max = null;

    public ?string $prof_principal_id = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Classe::class);
    }

    protected function rules(): array
    {
        return [
            'niveau_id' => ['nullable', 'uuid', 'exists:niveau,id'],
            'annee_id' => ['nullable', 'uuid', 'exists:annee_scolaire,id'],
            'libelle' => ['required', 'string', 'max:20'],
            'capacite_max' => ['nullable', 'integer', 'min:1'],
            'prof_principal_id' => ['nullable', 'uuid', 'exists:personne,id'],
        ];
    }

    public function openCreate(): void
    {
        $this->authorize('create', Classe::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(string $classeId): void
    {
        $classe = Classe::findOrFail($classeId);
        $this->authorize('update', $classe);

        $this->editingId = $classe->id;
        $this->niveau_id = $classe->niveau_id;
        $this->annee_id = $classe->annee_id;
        $this->libelle = $classe->libelle;
        $this->capacite_max = $classe->capacite_max;
        $this->prof_principal_id = $classe->prof_principal_id;
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        // capacite_max a un DEFAULT 45 en base mais n'est pas nullable (migration Session 3) :
        // un create()/update() avec la clé explicitement à null écrase le default et viole la
        // contrainte NOT NULL. On omet la clé pour laisser Postgres appliquer le default.
        if ($data['capacite_max'] === null) {
            unset($data['capacite_max']);
        }

        if ($this->editingId) {
            $classe = Classe::findOrFail($this->editingId);
            $this->authorize('update', $classe);
            $classe->update($data);
        } else {
            $this->authorize('create', Classe::class);
            Classe::create($data);
        }

        $this->showForm = false;
        $this->resetForm();
        session()->flash('success', 'Classe enregistrée.');
    }

    public function confirmDelete(string $classeId): void
    {
        $this->confirmingDeleteId = $classeId;
    }

    public function delete(): void
    {
        $classe = Classe::findOrFail($this->confirmingDeleteId);
        $this->authorize('delete', $classe);

        $classe->delete();

        $this->confirmingDeleteId = null;
        session()->flash('success', 'Classe supprimée.');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->niveau_id = null;
        $this->annee_id = null;
        $this->libelle = '';
        $this->capacite_max = null;
        $this->prof_principal_id = null;
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.scolarite.classes-liste', [
            'classes' => Classe::with(['niveau', 'anneeScolaire', 'profPrincipal'])
                ->orderBy('libelle')
                ->paginate(15),
            'niveaux' => Niveau::orderBy('ordre')->get(),
            'annees' => AnneeScolaire::orderByDesc('date_debut')->get(),
        ]);
    }
}
