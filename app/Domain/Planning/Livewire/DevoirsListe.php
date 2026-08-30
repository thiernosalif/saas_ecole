<?php

declare(strict_types=1);

namespace App\Domain\Planning\Livewire;

use App\Domain\Notes\Models\Matiere;
use App\Domain\Planning\Models\Devoir;
use App\Domain\Planning\Services\DevoirService;
use App\Domain\Scolarite\Models\Classe;
use App\Domain\Scolarite\Models\Personne;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Devoirs')]
class DevoirsListe extends Component
{
    use WithPagination;

    public ?string $classeIdFiltre = null;

    public bool $showForm = false;

    public ?string $editingId = null;

    public ?string $classe_id = null;

    public ?string $matiere_id = null;

    public ?string $prof_id = null;

    public string $titre = '';

    public ?string $description = null;

    public ?string $date_remise = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Devoir::class);
    }

    public function updatedClasseIdFiltre(): void
    {
        $this->resetPage();
    }

    public function updatedClasseId(): void
    {
        $this->matiere_id = null;
    }

    public function openCreate(): void
    {
        $this->authorize('create', Devoir::class);
        $this->resetForm();
        $this->classe_id = $this->classeIdFiltre;
        $this->showForm = true;
    }

    public function openEdit(string $devoirId): void
    {
        $devoir = Devoir::findOrFail($devoirId);
        $this->authorize('update', $devoir);

        $this->editingId = $devoir->id;
        $this->classe_id = $devoir->classe_id;
        $this->matiere_id = $devoir->matiere_id;
        $this->prof_id = $devoir->prof_id;
        $this->titre = $devoir->titre;
        $this->description = $devoir->description;
        $this->date_remise = $devoir->date_remise->format('Y-m-d');
        $this->showForm = true;
    }

    public function save(DevoirService $devoirs): void
    {
        $data = $this->validate([
            'classe_id' => ['required', 'uuid', 'exists:classe,id'],
            'matiere_id' => ['required', 'uuid', 'exists:matiere,id'],
            'prof_id' => ['nullable', 'uuid', 'exists:personne,id'],
            'titre' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'date_remise' => ['required', 'date'],
        ]);

        if ($this->editingId) {
            $devoir = Devoir::findOrFail($this->editingId);
            $this->authorize('update', $devoir);
            // classe_id/matiere_id ne sont volontairement pas modifiables à l'édition
            // (cf. UpdateDevoirRequest, qui ne les accepte pas non plus).
            unset($data['classe_id'], $data['matiere_id']);
            $devoirs->update($devoir, $data);
        } else {
            $this->authorize('create', Devoir::class);
            $devoirs->create($data);
        }

        $this->showForm = false;
        $this->resetForm();
        session()->flash('success', 'Devoir enregistré.');
    }

    public function render(): View
    {
        return view('livewire.planning.devoirs-liste', [
            'devoirs' => Devoir::query()
                ->when($this->classeIdFiltre, fn ($q) => $q->where('classe_id', $this->classeIdFiltre))
                ->with(['classe', 'matiere', 'prof'])
                ->orderByDesc('date_remise')
                ->paginate(15),
            'classes' => Classe::orderBy('libelle')->get(),
            'matieres' => $this->classe_id ? $this->matieresDisponibles() : collect(),
            'profs' => Personne::profs()->orderBy('nom')->get(),
        ]);
    }

    /**
     * @return Collection<int, Matiere>
     */
    private function matieresDisponibles(): Collection
    {
        $classe = Classe::find($this->classe_id);

        return $classe ? Matiere::where('niveau_id', $classe->niveau_id)->orderBy('libelle')->get() : collect();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->classe_id = null;
        $this->matiere_id = null;
        $this->prof_id = null;
        $this->titre = '';
        $this->description = null;
        $this->date_remise = null;
        $this->resetValidation();
    }
}
