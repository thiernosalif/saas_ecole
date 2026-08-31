<?php

declare(strict_types=1);

namespace App\Domain\Comptabilite\Livewire;

use App\Domain\Comptabilite\Models\Facture;
use App\Domain\Comptabilite\Models\Reglement;
use App\Domain\Comptabilite\Services\ReglementService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Règlements')]
class ReglementForm extends Component
{
    public ?Facture $facture = null;

    public string $montant = '';

    public string $moyen_paiement = Reglement::MOYEN_ESPECES;

    public ?string $reference = null;

    public ?string $paid_at = null;

    public ?string $notes = null;

    public function mount(Facture $facture): void
    {
        $this->authorize('view', $facture);
        $this->facture = $facture;
    }

    /**
     * Chemin manuel uniquement (espèces/chèque/virement) — même service que
     * ReglementController::store (§Session 11), pour ne jamais dupliquer/faire
     * diverger la logique d'application au solde entre l'API mobile et le
     * portail école.
     */
    public function save(ReglementService $reglements): void
    {
        $this->authorize('create', Reglement::class);

        $data = $this->validate([
            'montant' => ['required', 'numeric', 'min:0.01'],
            'moyen_paiement' => ['required', 'string', 'in:'.implode(',', Reglement::MOYENS_MANUELS)],
            'reference' => ['nullable', 'string', 'max:100'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $reglements->enregistrerPaiementManuel($this->facture, $data);

        $this->facture->refresh();
        $this->resetForm();
        session()->flash('success', 'Règlement enregistré.');
    }

    public function render(): View
    {
        return view('livewire.comptabilite.reglement-form', [
            'facture' => $this->facture->load('reglements'),
            'moyensManuels' => Reglement::MOYENS_MANUELS,
        ]);
    }

    private function resetForm(): void
    {
        $this->montant = '';
        $this->moyen_paiement = Reglement::MOYEN_ESPECES;
        $this->reference = null;
        $this->paid_at = null;
        $this->notes = null;
        $this->resetValidation();
    }
}
