<?php

declare(strict_types=1);

namespace App\Domain\Notes\Livewire;

use App\Domain\Notes\Models\AffectationProf;
use App\Domain\Notes\Models\Matiere;
use App\Domain\Notes\Models\Note;
use App\Domain\Notes\Services\NoteService;
use App\Domain\Scolarite\Models\AnneeScolaire;
use App\Domain\Scolarite\Models\Classe;
use App\Domain\Scolarite\Models\Inscription;
use App\Domain\Scolarite\Models\Trimestre;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Saisie des notes')]
class NotesSaisie extends Component
{
    public ?string $classe_id = null;

    public ?string $matiere_id = null;

    public ?string $trimestre_id = null;

    public string $type = Note::TYPE_DEVOIR;

    /** @var array<string, array{note_id: ?string, valeur: ?string, appreciation: ?string}> */
    public array $lignes = [];

    public function mount(): void
    {
        $this->authorize('create', Note::class);
    }

    public function updatedClasseId(): void
    {
        $this->matiere_id = null;
        $this->chargerGrille();
    }

    public function updatedMatiereId(): void
    {
        $this->chargerGrille();
    }

    public function updatedTrimestreId(): void
    {
        $this->chargerGrille();
    }

    public function updatedType(): void
    {
        $this->chargerGrille();
    }

    public function save(NoteService $notes): void
    {
        $this->authorize('create', Note::class);

        $this->validate([
            'classe_id' => ['required', 'uuid', 'exists:classe,id'],
            'matiere_id' => ['required', 'uuid', 'exists:matiere,id'],
            'trimestre_id' => ['required', 'uuid', 'exists:trimestre,id'],
            'type' => ['required', 'string', 'in:DEVOIR,COMPOSITION'],
            'lignes.*.valeur' => ['nullable', 'numeric', 'min:0', 'max:20'],
        ]);

        // Defense-in-depth : les <select> du formulaire ne proposent déjà que les
        // couples (classe, matiere) affectés au PROF, mais wire:model peut être
        // manipulé côté client — on revérifie donc toujours côté serveur avant
        // de persister quoi que ce soit.
        abort_unless($this->affectationAutorisee(), 403, 'Vous n’êtes pas affecté à cette classe pour cette matière.');

        foreach ($this->lignes as $eleveId => $ligne) {
            if ($ligne['valeur'] === null || $ligne['valeur'] === '') {
                continue;
            }

            $data = [
                'eleve_id' => $eleveId,
                'matiere_id' => $this->matiere_id,
                'trimestre_id' => $this->trimestre_id,
                'type' => $this->type,
                'valeur' => $ligne['valeur'],
                'appreciation' => $ligne['appreciation'] ?: null,
            ];

            if ($ligne['note_id']) {
                $note = Note::findOrFail($ligne['note_id']);
                $this->authorize('update', $note);
                $notes->update($note, $data);
            } else {
                $notes->create($data, auth()->user()->personne_id);
            }
        }

        session()->flash('success', 'Notes enregistrées.');
        $this->chargerGrille();
    }

    public function render(): View
    {
        $annee = $this->anneeActive();

        return view('livewire.notes.notes-saisie', [
            'annee' => $annee,
            'classes' => $annee ? $this->classesDisponibles($annee) : collect(),
            'matieres' => $annee ? $this->matieresDisponibles($annee) : collect(),
            'trimestres' => $annee
                ? Trimestre::where('annee_id', $annee->id)->orderBy('numero')->get()
                : collect(),
            'eleves' => $this->elevesAutorises(),
        ]);
    }

    private function chargerGrille(): void
    {
        $this->lignes = [];

        if (! $this->classe_id || ! $this->matiere_id || ! $this->trimestre_id) {
            return;
        }

        if (! $this->affectationAutorisee()) {
            return;
        }

        $eleves = $this->elevesAutorises();

        $notesExistantes = Note::where('matiere_id', $this->matiere_id)
            ->where('trimestre_id', $this->trimestre_id)
            ->where('type', $this->type)
            ->whereIn('eleve_id', $eleves->keys())
            ->orderByDesc('created_at')
            ->get()
            ->unique('eleve_id')
            ->keyBy('eleve_id');

        foreach ($eleves as $eleveId => $eleve) {
            $note = $notesExistantes->get($eleveId);

            $this->lignes[$eleveId] = [
                'note_id' => $note?->id,
                'valeur' => $note?->valeur,
                'appreciation' => $note?->appreciation,
            ];
        }
    }

    /**
     * @return Collection<string, \App\Domain\Scolarite\Models\Personne>
     */
    private function elevesAutorises(): Collection
    {
        if (! $this->classe_id || ! $this->affectationAutorisee()) {
            return collect();
        }

        return Inscription::where('classe_id', $this->classe_id)
            ->where('statut', Inscription::STATUT_ACTIVE)
            ->with('eleve')
            ->get()
            ->pluck('eleve', 'eleve_id');
    }

    /**
     * Un PROF ne voit/saisit que pour les couples (classe, matière) où il a une
     * affectation sur l'année scolaire en cours. ECOLE_ADMIN et SCOLARITE voient
     * tout, sans ce filtre.
     */
    private function affectationAutorisee(): bool
    {
        if (! $this->classe_id || ! $this->matiere_id) {
            return false;
        }

        $user = auth()->user();

        if (! $user->hasRole('PROF')) {
            return true;
        }

        $annee = $this->anneeActive();

        return $annee !== null && AffectationProf::where('prof_id', $user->personne_id)
            ->where('classe_id', $this->classe_id)
            ->where('matiere_id', $this->matiere_id)
            ->where('annee_id', $annee->id)
            ->exists();
    }

    /**
     * @return Collection<int, Classe>
     */
    private function classesDisponibles(AnneeScolaire $annee): Collection
    {
        $user = auth()->user();

        if ($user->hasRole('PROF')) {
            $classeIds = AffectationProf::where('prof_id', $user->personne_id)
                ->where('annee_id', $annee->id)
                ->pluck('classe_id')
                ->unique();

            return Classe::whereIn('id', $classeIds)->orderBy('libelle')->get();
        }

        return Classe::where('annee_id', $annee->id)->orderBy('libelle')->get();
    }

    /**
     * @return Collection<int, Matiere>
     */
    private function matieresDisponibles(AnneeScolaire $annee): Collection
    {
        if (! $this->classe_id) {
            return collect();
        }

        $user = auth()->user();

        if ($user->hasRole('PROF')) {
            $matiereIds = AffectationProf::where('prof_id', $user->personne_id)
                ->where('classe_id', $this->classe_id)
                ->where('annee_id', $annee->id)
                ->pluck('matiere_id')
                ->unique();

            return Matiere::whereIn('id', $matiereIds)->orderBy('libelle')->get();
        }

        $classe = Classe::find($this->classe_id);

        return $classe ? Matiere::where('niveau_id', $classe->niveau_id)->orderBy('libelle')->get() : collect();
    }

    private function anneeActive(): ?AnneeScolaire
    {
        return AnneeScolaire::where('active', true)->first();
    }
}
