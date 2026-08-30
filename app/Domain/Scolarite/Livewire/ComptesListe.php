<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Livewire;

use App\Domain\Scolarite\Models\Personne;
use App\Domain\Scolarite\Services\CompteUtilisateurService;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Comptes staff')]
class ComptesListe extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $creating = false;

    public ?string $confirmingDisableId = null;

    public string $nom = '';

    public string $prenom = '';

    public string $email = '';

    public ?string $telephone = null;

    public string $role = 'PROF';

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'string', 'in:PROF,SCOLARITE'],
        ];
    }

    public function creer(CompteUtilisateurService $comptes): void
    {
        $this->authorize('create', User::class);

        $data = $this->validate();

        $comptes->creerCompteStaff($data, $data['role']);

        $this->reset(['nom', 'prenom', 'email', 'telephone', 'role', 'creating']);
        session()->flash('success', 'Compte créé, les identifiants ont été envoyés par email.');
    }

    public function confirmDisable(string $compteId): void
    {
        $this->confirmingDisableId = $compteId;
    }

    public function disable(CompteUtilisateurService $comptes): void
    {
        $compte = $comptes->trouver($this->confirmingDisableId);

        $this->authorize('update', $compte);

        $comptes->desactiver($compte);

        $this->confirmingDisableId = null;
        session()->flash('success', 'Compte désactivé.');
    }

    public function render(): View
    {
        $comptes = User::where('tenant_id', app('currentTenantId'))
            ->whereNotNull('personne_id')
            ->whereHas('personne', fn ($query) => $query->whereIn('type', [Personne::TYPE_PROF, Personne::TYPE_STAFF]))
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query->where('name', 'ilike', "%{$this->search}%")
                        ->orWhere('email', 'ilike', "%{$this->search}%");
                });
            })
            ->with('personne')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.scolarite.comptes-liste', [
            'comptes' => $comptes,
        ]);
    }
}
