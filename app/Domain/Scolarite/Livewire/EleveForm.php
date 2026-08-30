<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Livewire;

use App\Domain\Scolarite\Models\LienParente;
use App\Domain\Scolarite\Models\Personne;
use App\Domain\Scolarite\Services\AccesFamilleService;
use App\Domain\Scolarite\Services\CompteUtilisateurService;
use App\Domain\Scolarite\Services\EleveService;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Élève')]
class EleveForm extends Component
{
    public ?Personne $eleve = null;

    public string $nom = '';

    public string $prenom = '';

    public ?string $date_naissance = null;

    public ?string $lieu_naissance = null;

    public ?string $genre = null;

    public ?string $matricule = null;

    public ?string $telephone = null;

    public ?string $email = null;

    public ?string $adresse = null;

    public ?string $nationalite = null;

    public ?string $num_acte_naissance = null;

    public ?string $groupe_sanguin = null;

    public ?string $allergies = null;

    public bool $actif = true;

    /** Parents déjà liés (chargés en lecture) : nom/prénom/email/statut de compte, non éditables ici. */
    public array $parentsExistants = [];

    /** Nouveaux parents à lier lors de cet enregistrement. */
    public array $nouveauxParents = [];

    public bool $eleveADejaCompte = false;

    public function mount(?Personne $eleve = null): void
    {
        $this->authorize($eleve ? 'update' : 'create', $eleve ?? Personne::class);

        if ($eleve) {
            $this->eleve = $eleve;
            $this->fill($eleve->only([
                'nom', 'prenom', 'lieu_naissance', 'genre', 'matricule',
                'telephone', 'email', 'adresse', 'nationalite', 'num_acte_naissance',
                'groupe_sanguin', 'allergies',
            ]));
            $this->date_naissance = $eleve->date_naissance?->format('Y-m-d');
            // $eleve->actif peut être null en mémoire si la Personne a été créée
            // sans passer explicitement 'actif' (le défaut DB `true` n'est jamais
            // relu sans fresh()) — jamais reproduit avant cette session car aucun
            // test n'ouvrait encore le formulaire d'édition sur un tel élève.
            $this->actif = $eleve->actif ?? true;

            $this->eleveADejaCompte = User::where('personne_id', $eleve->id)->exists();
            $this->chargerParentsExistants();
        }
    }

    private function chargerParentsExistants(): void
    {
        $this->parentsExistants = LienParente::where('eleve_id', $this->eleve->id)
            ->with('parentPersonne')
            ->get()
            ->map(fn (LienParente $lien) => [
                'parent_id' => $lien->parent_id,
                'nom' => $lien->parentPersonne->nom,
                'prenom' => $lien->parentPersonne->prenom,
                'telephone' => $lien->parentPersonne->telephone,
                'email' => $lien->parentPersonne->email,
                'lien' => $lien->lien,
                'tuteur_principal' => $lien->tuteur_principal,
                'compte_existe' => User::where('personne_id', $lien->parent_id)->exists(),
            ])
            ->all();
    }

    public function ajouterParent(): void
    {
        $this->nouveauxParents[] = [
            'nom' => '',
            'prenom' => '',
            'telephone' => '',
            'email' => '',
            'lien' => '',
            'tuteur_principal' => false,
            'contact_urgence' => false,
            'creer_compte' => false,
        ];
    }

    public function retirerParent(int $index): void
    {
        unset($this->nouveauxParents[$index]);
        $this->nouveauxParents = array_values($this->nouveauxParents);
    }

    protected function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'date_naissance' => ['required', 'date'],
            'lieu_naissance' => ['nullable', 'string', 'max:100'],
            'genre' => ['nullable', 'string', 'in:M,F'],
            'matricule' => ['nullable', 'string', 'max:50'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'adresse' => ['nullable', 'string'],
            'nationalite' => ['nullable', 'string', 'max:50'],
            'num_acte_naissance' => ['required', 'string', 'max:50'],
            'groupe_sanguin' => ['nullable', 'string', 'max:5'],
            'allergies' => ['nullable', 'string'],
            'actif' => ['boolean'],
            'nouveauxParents.*.nom' => ['required', 'string', 'max:100'],
            'nouveauxParents.*.prenom' => ['required', 'string', 'max:100'],
            'nouveauxParents.*.telephone' => ['nullable', 'string', 'max:20'],
            'nouveauxParents.*.email' => ['nullable', 'email', 'max:150'],
            'nouveauxParents.*.lien' => ['nullable', 'string', 'max:30'],
            'nouveauxParents.*.tuteur_principal' => ['boolean'],
            'nouveauxParents.*.contact_urgence' => ['boolean'],
            'nouveauxParents.*.creer_compte' => ['boolean'],
        ];
    }

    public function save(EleveService $eleves, AccesFamilleService $acces): void
    {
        $data = $this->validate();

        $nouveauxParents = $data['nouveauxParents'] ?? [];
        unset($data['nouveauxParents']);

        foreach ($nouveauxParents as $index => $parent) {
            if (! empty($parent['creer_compte']) && empty($parent['email'])) {
                throw ValidationException::withMessages([
                    "nouveauxParents.{$index}.email" => 'Un email est requis pour créer un accès parent.',
                ]);
            }
        }

        DB::transaction(function () use ($eleves, $acces, $data, $nouveauxParents): void {
            if ($this->eleve) {
                $eleves->update($this->eleve, $data);
            } else {
                $this->eleve = $eleves->create($data);
            }

            if (filled($nouveauxParents)) {
                $acces->lierParents($this->eleve, $nouveauxParents);
            }
        });

        session()->flash('success', 'Élève enregistré.');

        $this->redirectRoute('scolarite.eleves.index', navigate: true);
    }

    public function creerAccesEleve(CompteUtilisateurService $comptes): void
    {
        $this->authorize('update', $this->eleve);

        $comptes->creerCompteEleve($this->eleve);

        $this->eleveADejaCompte = true;
        session()->flash('success', 'Accès élève créé, les identifiants ont été envoyés par email.');
    }

    public function creerAccesParent(string $parentId, CompteUtilisateurService $comptes): void
    {
        $this->authorize('update', $this->eleve);

        // Le lien élève↔parent fait office de garde-fou : on ne crée jamais
        // de compte pour un parent qui ne serait pas réellement lié à CET
        // élève (IDOR), même si son id est deviné.
        $lien = LienParente::where('eleve_id', $this->eleve->id)->where('parent_id', $parentId)->firstOrFail();

        $comptes->creerCompteParent($lien->parentPersonne);

        $this->chargerParentsExistants();
        session()->flash('success', 'Accès parent créé, les identifiants ont été envoyés par email.');
    }

    public function render(): View
    {
        return view('livewire.scolarite.eleve-form');
    }
}
