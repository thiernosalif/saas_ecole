<?php

declare(strict_types=1);

namespace App\Domain\Planning\Livewire;

use App\Domain\Notes\Models\Matiere;
use App\Domain\Planning\Models\Salle;
use App\Domain\Planning\Models\Seance;
use App\Domain\Planning\Services\SeanceService;
use App\Domain\Scolarite\Models\AnneeScolaire;
use App\Domain\Scolarite\Models\Classe;
use App\Domain\Scolarite\Models\Personne;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Emploi du temps')]
class EmploiDuTemps extends Component
{
    /**
     * Convention de ce module : jour_semaine 0 = Lundi ... 5 = Samedi (6 = Dimanche,
     * accepté par le schéma mais non affiché en colonne, les écoles ne travaillant pas
     * ce jour-là dans ce contexte).
     */
    public const JOURS = [
        0 => 'Lundi',
        1 => 'Mardi',
        2 => 'Mercredi',
        3 => 'Jeudi',
        4 => 'Vendredi',
        5 => 'Samedi',
    ];

    public ?string $classe_id = null;

    public bool $showForm = false;

    public ?string $editingId = null;

    public ?string $matiere_id = null;

    public ?string $prof_id = null;

    public ?string $salle_id = null;

    public ?int $jour_semaine = null;

    public ?string $heure_debut = null;

    public ?string $heure_fin = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Seance::class);
    }

    public function updatedClasseId(): void
    {
        $this->matiere_id = null;
    }

    public function openCreate(): void
    {
        $this->authorize('create', Seance::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(string $seanceId): void
    {
        $seance = Seance::findOrFail($seanceId);
        $this->authorize('update', $seance);

        $this->editingId = $seance->id;
        $this->matiere_id = $seance->matiere_id;
        $this->prof_id = $seance->prof_id;
        $this->salle_id = $seance->salle_id;
        $this->jour_semaine = $seance->jour_semaine;
        $this->heure_debut = substr($seance->heure_debut, 0, 5);
        $this->heure_fin = substr($seance->heure_fin, 0, 5);
        $this->showForm = true;
    }

    /**
     * Création ou déplacement : la détection de chevauchement salle/prof est déjà
     * gérée par SeanceService (ValidationException), on ne la duplique pas ici — elle
     * remonte telle quelle dans le sac d'erreurs du formulaire.
     */
    public function save(SeanceService $seances): void
    {
        $data = $this->validate([
            'classe_id' => ['required', 'uuid', 'exists:classe,id'],
            'matiere_id' => ['required', 'uuid', 'exists:matiere,id'],
            'prof_id' => ['nullable', 'uuid', 'exists:personne,id'],
            'salle_id' => ['nullable', 'uuid', 'exists:salle,id'],
            'jour_semaine' => ['required', 'integer', 'min:0', 'max:6'],
            'heure_debut' => ['required', 'date_format:H:i'],
            'heure_fin' => ['required', 'date_format:H:i', 'after:heure_debut'],
        ]);

        $data['annee_id'] = $this->anneeActive()?->id;

        if ($this->editingId) {
            $seance = Seance::findOrFail($this->editingId);
            $this->authorize('update', $seance);
            // classe_id/matiere_id ne sont volontairement pas modifiables au
            // déplacement (cf. UpdateSeanceRequest, qui ne les accepte pas non plus).
            unset($data['classe_id'], $data['matiere_id']);
            $seances->deplacer($seance, $data);
        } else {
            $this->authorize('create', Seance::class);
            $seances->create($data);
        }

        $this->showForm = false;
        $this->resetForm();
        session()->flash('success', 'Séance enregistrée.');
    }

    public function render(): View
    {
        $seancesParJour = $this->classe_id
            ? Seance::where('classe_id', $this->classe_id)
                ->with(['matiere', 'prof', 'salle'])
                ->orderBy('heure_debut')
                ->get()
                ->groupBy('jour_semaine')
            : collect();

        return view('livewire.planning.emploi-du-temps', [
            'classes' => Classe::orderBy('libelle')->get(),
            'matieres' => $this->classe_id ? $this->matieresDisponibles() : collect(),
            'profs' => Personne::profs()->orderBy('nom')->get(),
            'salles' => Salle::orderBy('nom')->get(),
            'seancesParJour' => $seancesParJour,
            'jours' => self::JOURS,
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

    private function anneeActive(): ?AnneeScolaire
    {
        return AnneeScolaire::where('active', true)->first();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->matiere_id = null;
        $this->prof_id = null;
        $this->salle_id = null;
        $this->jour_semaine = null;
        $this->heure_debut = null;
        $this->heure_fin = null;
        $this->resetValidation();
    }
}
