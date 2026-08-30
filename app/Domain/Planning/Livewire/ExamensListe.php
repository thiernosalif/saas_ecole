<?php

declare(strict_types=1);

namespace App\Domain\Planning\Livewire;

use App\Domain\Notes\Models\Matiere;
use App\Domain\Planning\Models\Examen;
use App\Domain\Planning\Models\Salle;
use App\Domain\Planning\Services\ExamenService;
use App\Domain\Scolarite\Models\AnneeScolaire;
use App\Domain\Scolarite\Models\Classe;
use App\Domain\Scolarite\Models\Trimestre;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Examens')]
class ExamensListe extends Component
{
    use WithPagination;

    public ?string $classeIdFiltre = null;

    public bool $showForm = false;

    public ?string $editingId = null;

    public ?string $classe_id = null;

    public ?string $matiere_id = null;

    public ?string $trimestre_id = null;

    public ?string $salle_id = null;

    public ?string $date_examen = null;

    public ?string $heure_debut = null;

    public ?int $duree_minutes = null;

    public ?float $bareme = null;

    public ?string $libelle = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Examen::class);
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
        $this->authorize('create', Examen::class);
        $this->resetForm();
        $this->classe_id = $this->classeIdFiltre;
        $this->showForm = true;
    }

    public function openEdit(string $examenId): void
    {
        $examen = Examen::findOrFail($examenId);
        $this->authorize('update', $examen);

        $this->editingId = $examen->id;
        $this->classe_id = $examen->classe_id;
        $this->matiere_id = $examen->matiere_id;
        $this->trimestre_id = $examen->trimestre_id;
        $this->salle_id = $examen->salle_id;
        $this->date_examen = $examen->date_examen->format('Y-m-d');
        $this->heure_debut = $examen->heure_debut ? substr($examen->heure_debut, 0, 5) : null;
        $this->duree_minutes = $examen->duree_minutes;
        $this->bareme = (float) $examen->bareme;
        $this->libelle = $examen->libelle;
        $this->showForm = true;
    }

    public function save(ExamenService $examens): void
    {
        $data = $this->validate([
            'classe_id' => ['required', 'uuid', 'exists:classe,id'],
            'matiere_id' => ['required', 'uuid', 'exists:matiere,id'],
            'trimestre_id' => ['nullable', 'uuid', 'exists:trimestre,id'],
            'salle_id' => ['nullable', 'uuid', 'exists:salle,id'],
            'date_examen' => ['required', 'date'],
            'heure_debut' => ['nullable', 'date_format:H:i'],
            'duree_minutes' => ['nullable', 'integer', 'min:1'],
            'bareme' => ['nullable', 'numeric', 'min:1'],
            'libelle' => ['nullable', 'string', 'max:200'],
        ]);

        // bareme a un DEFAULT 20.0 en base mais n'est pas nullable (migration Session 10) :
        // un create()/update() avec la clé explicitement à null écraserait ce default et
        // violerait la contrainte NOT NULL (même règle que capacite_max sur Classe).
        if ($data['bareme'] === null) {
            unset($data['bareme']);
        }

        if ($this->editingId) {
            $examen = Examen::findOrFail($this->editingId);
            $this->authorize('update', $examen);
            // classe_id/matiere_id ne sont volontairement pas modifiables à l'édition
            // (cf. UpdateExamenRequest, qui ne les accepte pas non plus).
            unset($data['classe_id'], $data['matiere_id']);
            $examens->update($examen, $data);
        } else {
            $this->authorize('create', Examen::class);
            $examens->create($data);
        }

        $this->showForm = false;
        $this->resetForm();
        session()->flash('success', 'Examen enregistré.');
    }

    public function render(): View
    {
        $annee = AnneeScolaire::where('active', true)->first();

        return view('livewire.planning.examens-liste', [
            'examens' => Examen::query()
                ->when($this->classeIdFiltre, fn ($q) => $q->where('classe_id', $this->classeIdFiltre))
                ->with(['classe', 'matiere', 'trimestre', 'salle'])
                ->orderByDesc('date_examen')
                ->paginate(15),
            'classes' => Classe::orderBy('libelle')->get(),
            'matieres' => $this->classe_id ? $this->matieresDisponibles() : collect(),
            'trimestres' => $annee
                ? Trimestre::where('annee_id', $annee->id)->orderBy('numero')->get()
                : collect(),
            'salles' => Salle::orderBy('nom')->get(),
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
        $this->trimestre_id = null;
        $this->salle_id = null;
        $this->date_examen = null;
        $this->heure_debut = null;
        $this->duree_minutes = null;
        $this->bareme = null;
        $this->libelle = null;
        $this->resetValidation();
    }
}
