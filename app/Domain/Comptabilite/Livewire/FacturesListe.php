<?php

declare(strict_types=1);

namespace App\Domain\Comptabilite\Livewire;

use App\Domain\Comptabilite\Models\Facture;
use App\Domain\Comptabilite\Services\FactureService;
use App\Domain\Scolarite\Models\AnneeScolaire;
use App\Domain\Scolarite\Models\Personne;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Factures')]
class FacturesListe extends Component
{
    public ?Personne $eleve = null;

    public bool $showForm = false;

    public ?string $annee_id = null;

    public ?string $numero = null;

    public string $montant_total = '';

    public ?string $due_at = null;

    public function mount(Personne $eleve): void
    {
        // Pas de "toutes les factures" : on ne révèle les factures qu'après
        // avoir vérifié l'accès à CET élève précis (cf. FactureController::index,
        // même garde via ElevePolicy plutôt que FacturePolicy).
        $this->authorize('view', $eleve);
        $this->eleve = $eleve;
    }

    public function openCreate(): void
    {
        $this->authorize('create', Facture::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function save(FactureService $factures): void
    {
        $this->authorize('create', Facture::class);

        $data = $this->validate([
            'annee_id' => ['nullable', 'uuid', 'exists:annee_scolaire,id'],
            'numero' => ['nullable', 'string', 'max:30'],
            'montant_total' => ['required', 'numeric', 'min:0.01'],
            'due_at' => ['nullable', 'date'],
        ]);

        $factures->create([...$data, 'eleve_id' => $this->eleve->id]);

        $this->showForm = false;
        $this->resetForm();
        session()->flash('success', 'Facture créée.');
    }

    public function render(FactureService $factures): View
    {
        return view('livewire.comptabilite.factures-liste', [
            'factures' => $factures->pourEleve($this->eleve->id),
            'annees' => AnneeScolaire::orderByDesc('created_at')->get(),
        ]);
    }

    private function resetForm(): void
    {
        $this->annee_id = null;
        $this->numero = null;
        $this->montant_total = '';
        $this->due_at = null;
        $this->resetValidation();
    }
}
