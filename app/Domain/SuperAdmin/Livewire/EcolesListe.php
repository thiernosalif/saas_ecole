<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Livewire;

use App\Domain\SuperAdmin\Models\Etablissement;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Écoles')]
class EcolesListe extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statutFiltre = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatutFiltre(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $etablissements = Etablissement::with('plan')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query->where('nom', 'ilike', "%{$this->search}%")
                        ->orWhere('sous_domaine', 'ilike', "%{$this->search}%")
                        ->orWhere('ville', 'ilike', "%{$this->search}%");
                });
            })
            ->when($this->statutFiltre !== '', fn ($query) => $query->where('statut', $this->statutFiltre))
            ->latest('created_at')
            ->paginate(15);

        return view('livewire.admin.ecoles-liste', [
            'etablissements' => $etablissements,
        ]);
    }
}
