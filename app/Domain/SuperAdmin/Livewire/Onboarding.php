<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Livewire;

use App\Domain\SuperAdmin\Models\PlanTarifaire;
use App\Domain\SuperAdmin\Services\OnboardingService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Flux en 5 étapes (§15.7). Étapes 3 (création tenant/etablissement) et 4
 * (compte directeur) sont "automatiques (backend)" par design — pas d'écran
 * dédié, elles s'exécutent en une fois dans terminer() ; le stepper visuel
 * les affiche tout de même comme repères de progression.
 */
#[Layout('layouts.admin')]
#[Title('Onboarding')]
class Onboarding extends Component
{
    use WithFileUploads;

    public const ETAPES = [
        1 => 'Infos de base',
        2 => 'Personnalisation',
        3 => 'Création automatique',
        4 => 'Compte directeur',
        5 => 'Confirmation',
    ];

    public int $step = 1;

    // Étape 1
    public string $nom = '';

    public ?string $adresse = null;

    public ?string $ville = null;

    public ?string $telephone_ecole = null;

    public ?string $contact_directeur = null;

    public string $email_directeur = '';

    public ?string $plan_id = null;

    public bool $essai_gratuit = true;

    // Étape 2
    public $logo = null;

    public ?string $couleur_primaire = null;

    public ?string $couleur_secondaire = null;

    public ?array $recapitulatif = null;

    public function etapeSuivante(): void
    {
        $this->validate($this->reglesEtape1());

        $this->step = 2;
    }

    public function etapePrecedente(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function terminer(OnboardingService $onboarding): void
    {
        $donnees = array_merge(
            $this->validate($this->reglesEtape1()),
            $this->validate($this->reglesEtape2()),
        );

        $resultat = $onboarding->onboarder($donnees, $this->logo);

        $this->recapitulatif = $resultat['recapitulatif'];
        $this->step = 5;
    }

    public function recommencer(): void
    {
        $this->reset();
    }

    private function reglesEtape1(): array
    {
        return [
            'nom' => ['required', 'string', 'max:200'],
            'adresse' => ['nullable', 'string'],
            'ville' => ['nullable', 'string', 'max:100'],
            'telephone_ecole' => ['nullable', 'string', 'max:20'],
            'contact_directeur' => ['nullable', 'string', 'max:150'],
            'email_directeur' => ['required', 'email', 'max:150', 'unique:users,email'],
            'plan_id' => ['nullable', 'uuid', 'exists:plan_tarifaire,id'],
            'essai_gratuit' => ['boolean'],
        ];
    }

    private function reglesEtape2(): array
    {
        return [
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'couleur_primaire' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'couleur_secondaire' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.onboarding', [
            'plans' => PlanTarifaire::where('actif', true)->orderBy('prix_mensuel')->get(),
        ]);
    }
}
