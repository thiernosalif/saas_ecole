<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Livewire;

use App\Domain\SuperAdmin\Models\CommunicationLog;
use App\Domain\SuperAdmin\Models\Etablissement;
use App\Domain\SuperAdmin\Notifications\AnnonceGlobale;
use App\Domain\SuperAdmin\Services\AccesService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Communication')]
class Communication extends Component
{
    public string $sujet = '';

    public string $contenu = '';

    public function envoyer(AccesService $acces): void
    {
        $this->validate([
            'sujet' => ['required', 'string', 'max:200'],
            'contenu' => ['required', 'string', 'max:5000'],
        ]);

        // "Toutes les écoles" exclut les écoles archivées (§15.4 : accès définitivement
        // coupé à ce stade, plus de directeur à notifier). Réutilise
        // AccesService::notifierDirecteurs, déjà responsable du scoping team_id spatie
        // par école pour cibler les ECOLE_ADMIN.
        $etablissements = Etablissement::whereIn('statut', [Etablissement::STATUT_ACTIF, Etablissement::STATUT_SUSPENDU])->get();

        foreach ($etablissements as $etablissement) {
            $acces->notifierDirecteurs($etablissement, new AnnonceGlobale($this->sujet, $this->contenu));
        }

        CommunicationLog::create([
            'type' => CommunicationLog::TYPE_ANNONCE,
            'destinataires' => "Toutes les écoles ({$etablissements->count()})",
            'sujet' => $this->sujet,
            'contenu' => $this->contenu,
            'envoye_par' => auth()->user()->name,
        ]);

        $this->reset(['sujet', 'contenu']);

        session()->flash('success', "Annonce envoyée à {$etablissements->count()} école(s).");
    }

    public function render(): View
    {
        return view('livewire.admin.communication', [
            'historique' => CommunicationLog::latest('envoye_at')->limit(10)->get(),
        ]);
    }
}
