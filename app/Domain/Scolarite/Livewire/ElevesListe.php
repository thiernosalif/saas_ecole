<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Livewire;

use App\Domain\Scolarite\Models\Personne;
use App\Domain\Scolarite\Services\EleveService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Élèves')]
class ElevesListe extends Component
{
    use WithPagination;

    public string $search = '';

    public ?string $confirmingDeleteId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Personne::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(string $eleveId): void
    {
        $this->confirmingDeleteId = $eleveId;
    }

    public function delete(EleveService $eleves): void
    {
        $eleve = Personne::eleves()->findOrFail($this->confirmingDeleteId);

        $this->authorize('delete', $eleve);

        $eleves->delete($eleve);

        $this->confirmingDeleteId = null;
        session()->flash('success', 'Élève supprimé.');
    }

    public function render(): View
    {
        $eleves = Personne::eleves()
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query->where('nom', 'ilike', "%{$this->search}%")
                        ->orWhere('prenom', 'ilike', "%{$this->search}%")
                        ->orWhere('matricule', 'ilike', "%{$this->search}%");
                });
            })
            ->orderBy('nom')
            ->paginate(15);

        return view('livewire.scolarite.eleves-liste', [
            'eleves' => $eleves,
        ]);
    }
}
