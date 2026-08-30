<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Livewire;

use App\Domain\Scolarite\Models\Personne;
use App\Domain\SuperAdmin\Models\Etablissement;
use App\Domain\SuperAdmin\Models\PlanTarifaire;
use App\Domain\SuperAdmin\Services\AbonnementService;
use App\Domain\SuperAdmin\Services\AccesService;
use App\Domain\SuperAdmin\Services\EcoleService;
use App\Domain\SuperAdmin\Services\PersonnalisationService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
#[Title('École')]
class EcoleDetail extends Component
{
    use WithFileUploads;

    private const ONGLETS = ['infos', 'personnalisation', 'abonnement', 'stats', 'acces'];

    /**
     * Modules disponibles à la carte (§15.3) — "all" (plan Réseau) n'est pas une
     * case à cocher, il vaut juste "tous les modules ci-dessous par défaut".
     */
    public const MODULES_DISPONIBLES = [
        'scolarite', 'notes', 'absences', 'planning', 'comptabilite', 'documents', 'notifications',
    ];

    public Etablissement $etablissement;

    public string $activeTab = 'infos';

    // Onglet infos
    public string $nom = '';

    public ?string $adresse = null;

    public ?string $telephone = null;

    public ?string $telephone_ecole = null;

    public ?string $email = null;

    public ?string $contact_directeur = null;

    public ?string $ville = null;

    public ?string $pays = null;

    // Onglet personnalisation
    public ?string $couleur_primaire = null;

    public ?string $couleur_secondaire = null;

    public ?string $nom_court = null;

    public ?string $slogan = null;

    public $logo = null;

    // Onglet abonnement
    public ?string $plan_id = null;

    public int $nb_eleves_max = 300;

    public int $stockage_max_go = 5;

    /** @var array<int, string> */
    public array $modulesActifs = [];

    // Onglet accès
    public bool $confirmingSuspend = false;

    public bool $confirmingArchive = false;

    public string $motifSuspension = '';

    public function mount(Etablissement $etablissement): void
    {
        $this->etablissement = $etablissement->load('plan');
        $this->syncFromEtablissement();
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = in_array($tab, self::ONGLETS, true) ? $tab : 'infos';
    }

    public function updateInfos(EcoleService $ecoles): void
    {
        $data = $this->validate([
            'nom' => ['required', 'string', 'max:200'],
            'adresse' => ['nullable', 'string'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'telephone_ecole' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'contact_directeur' => ['nullable', 'string', 'max:150'],
            'ville' => ['nullable', 'string', 'max:100'],
            'pays' => ['required', 'string', 'max:50'],
        ]);

        $this->etablissement = $ecoles->mettreAJour($this->etablissement, $data)->load('plan');

        session()->flash('success', 'Informations mises à jour.');
    }

    public function updatePersonnalisation(PersonnalisationService $personnalisation): void
    {
        $data = $this->validate([
            'couleur_primaire' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'couleur_secondaire' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'nom_court' => ['nullable', 'string', 'max:50'],
            'slogan' => ['nullable', 'string', 'max:200'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ]);

        $this->etablissement = $personnalisation->mettreAJour($this->etablissement, $data)->load('plan');

        if ($this->logo) {
            $this->etablissement = $personnalisation->uploaderLogo($this->etablissement, $this->logo)->load('plan');
            $this->logo = null;
        }

        session()->flash('success', 'Personnalisation mise à jour.');
    }

    public function updateAbonnement(EcoleService $ecoles): void
    {
        $validated = $this->validate([
            'plan_id' => ['nullable', 'uuid', 'exists:plan_tarifaire,id'],
            'nb_eleves_max' => ['required', 'integer', 'min:1'],
            'stockage_max_go' => ['required', 'integer', 'min:1'],
            'modulesActifs' => ['array'],
            'modulesActifs.*' => ['string', 'in:'.implode(',', self::MODULES_DISPONIBLES)],
        ]);

        $this->etablissement = $ecoles->mettreAJour($this->etablissement, [
            'plan_id' => $validated['plan_id'],
            'nb_eleves_max' => $validated['nb_eleves_max'],
            'stockage_max_go' => $validated['stockage_max_go'],
            'modules_actifs' => $validated['modulesActifs'],
        ])->load('plan');

        session()->flash('success', 'Abonnement mis à jour.');
    }

    /**
     * Paiement en ligne de l'échéance du mois courant (Session 13) — génère
     * la ligne reglement_saas si besoin puis redirige vers Wave/Orange Money.
     * La confirmation arrive plus tard via SuperAdmin\Http\Controllers\WebhookController,
     * pas ici.
     */
    public function payerEnLigne(string $moyen, AbonnementService $abonnements): void
    {
        $reglement = $abonnements->genererEcheanceCourante($this->etablissement->load('plan'));
        $resultat = $abonnements->initierPaiement($reglement, $moyen);

        $this->redirect($resultat['url_paiement']);
    }

    public function confirmSuspend(): void
    {
        $this->motifSuspension = '';
        $this->confirmingSuspend = true;
    }

    public function suspendre(AccesService $acces): void
    {
        $this->validate(['motifSuspension' => ['required', 'string', 'max:500']]);

        $this->etablissement = $acces->suspendre($this->etablissement, $this->motifSuspension)->load('plan');
        $this->confirmingSuspend = false;

        session()->flash('success', 'École suspendue.');
    }

    public function reactiver(AccesService $acces): void
    {
        $this->etablissement = $acces->reactiver($this->etablissement)->load('plan');

        session()->flash('success', 'École réactivée.');
    }

    public function confirmArchive(): void
    {
        $this->confirmingArchive = true;
    }

    public function archiver(AccesService $acces): void
    {
        $this->etablissement = $acces->archiver($this->etablissement)->load('plan');
        $this->confirmingArchive = false;

        session()->flash('success', 'École archivée.');
    }

    private function syncFromEtablissement(): void
    {
        $this->nom = $this->etablissement->nom;
        $this->adresse = $this->etablissement->adresse;
        $this->telephone = $this->etablissement->telephone;
        $this->telephone_ecole = $this->etablissement->telephone_ecole;
        $this->email = $this->etablissement->email;
        $this->contact_directeur = $this->etablissement->contact_directeur;
        $this->ville = $this->etablissement->ville;
        $this->pays = $this->etablissement->pays ?? 'Sénégal';

        $this->couleur_primaire = $this->etablissement->couleur_primaire;
        $this->couleur_secondaire = $this->etablissement->couleur_secondaire;
        $this->nom_court = $this->etablissement->nom_court;
        $this->slogan = $this->etablissement->slogan;

        $this->plan_id = $this->etablissement->plan_id;
        $this->nb_eleves_max = $this->etablissement->nb_eleves_max ?? 300;
        $this->stockage_max_go = $this->etablissement->stockage_max_go ?? 5;

        // modules_actifs est une surcharge par école (§15.1) : tant qu'elle n'a
        // jamais été définie, on part des modules inclus dans le plan actuel.
        $this->modulesActifs = $this->etablissement->modules_actifs ?? $this->modulesDuPlan();
    }

    /**
     * @return array<int, string>
     */
    private function modulesDuPlan(): array
    {
        $inclus = $this->etablissement->plan?->modules_inclus ?? [];

        return in_array('all', $inclus, true) ? self::MODULES_DISPONIBLES : $inclus;
    }

    public function render(): View
    {
        return view('livewire.admin.ecole-detail', [
            'plans' => PlanTarifaire::orderBy('prix_mensuel')->get(),
            'reglements' => $this->etablissement->reglementsSaas()->orderByDesc('mois')->limit(6)->get(),
            'echeanceCourante' => $this->etablissement->reglementsSaas()
                ->where('mois', now()->format('Y-m'))
                ->first(),
            'nbElevesActuel' => Personne::withoutTenant()->eleves()
                ->where('tenant_id', $this->etablissement->tenant_id)
                ->count(),
            'modulesDuPlan' => $this->modulesDuPlan(),
        ]);
    }
}
