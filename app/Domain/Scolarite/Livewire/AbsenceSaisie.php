<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Livewire;

use App\Domain\Scolarite\Models\Absence;
use App\Domain\Scolarite\Models\Classe;
use App\Domain\Scolarite\Models\Inscription;
use App\Domain\Scolarite\Services\AbsenceService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Saisie des absences')]
class AbsenceSaisie extends Component
{
    public ?string $classe_id = null;

    public string $date;

    /** @var array<string, array{marque: bool, type: string, justifiee: bool}> */
    public array $lignes = [];

    public function mount(): void
    {
        $this->authorize('create', Absence::class);
        $this->date = now()->format('Y-m-d');
    }

    public function updatedClasseId(): void
    {
        $this->chargerEleves();
    }

    public function updatedDate(): void
    {
        $this->chargerEleves();
    }

    private function chargerEleves(): void
    {
        $this->lignes = [];

        if (! $this->classe_id) {
            return;
        }

        Inscription::where('classe_id', $this->classe_id)
            ->where('statut', Inscription::STATUT_ACTIVE)
            ->with('eleve')
            ->get()
            ->each(function (Inscription $inscription): void {
                $this->lignes[$inscription->eleve_id] = [
                    'marque' => false,
                    'type' => Absence::TYPE_ABSENCE,
                    'justifiee' => false,
                ];
            });
    }

    public function save(AbsenceService $absences): void
    {
        $this->authorize('create', Absence::class);

        $this->validate([
            'classe_id' => ['required', 'uuid', 'exists:classe,id'],
            'date' => ['required', 'date'],
        ]);

        foreach ($this->lignes as $eleveId => $ligne) {
            if (! $ligne['marque']) {
                continue;
            }

            $absences->create([
                'eleve_id' => $eleveId,
                'date' => $this->date,
                'type' => $ligne['type'],
                'justifiee' => $ligne['justifiee'],
            ], auth()->user()->personne_id);
        }

        session()->flash('success', 'Absences enregistrées.');
    }

    public function render(): View
    {
        return view('livewire.scolarite.absence-saisie', [
            'classes' => Classe::orderBy('libelle')->get(),
            'eleves' => $this->classe_id
                ? Inscription::where('classe_id', $this->classe_id)
                    ->where('statut', Inscription::STATUT_ACTIVE)
                    ->with('eleve')
                    ->get()
                    ->pluck('eleve', 'eleve_id')
                : collect(),
        ]);
    }
}
