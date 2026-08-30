<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Services;

use App\Domain\SuperAdmin\Models\Etablissement;
use Illuminate\Http\UploadedFile;

/**
 * Orchestre le workflow d'onboarding en 5 étapes (§15.7) : les étapes 1-2
 * sont validées côté Request/Livewire (session 9) et passées ici en un seul
 * appel ; EcoleService gère la création (3-4), PersonnalisationService la
 * personnalisation (2), et onboarder() construit le récapitulatif (5).
 */
class OnboardingService
{
    public function __construct(
        private readonly EcoleService $ecoles,
        private readonly PersonnalisationService $personnalisation,
    ) {}

    public function onboarder(array $donnees, ?UploadedFile $logo = null): array
    {
        $etablissement = $this->ecoles->creerEcole($donnees);

        if ($logo !== null) {
            $etablissement = $this->personnalisation->uploaderLogo($etablissement, $logo);
        }

        if (! empty($donnees['couleur_primaire']) || ! empty($donnees['couleur_secondaire'])) {
            $etablissement = $this->personnalisation->mettreAJour($etablissement, $donnees);
        }

        return [
            'etablissement' => $etablissement,
            'recapitulatif' => $this->recapitulatif($etablissement),
        ];
    }

    private function recapitulatif(Etablissement $etablissement): array
    {
        return [
            'ecole' => $etablissement->nom,
            'sous_domaine' => $etablissement->sous_domaine,
            'url' => sprintf(
                '%s://%s.%s',
                parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https',
                $etablissement->sous_domaine,
                config('app.tenant_central_domain'),
            ),
            'plan' => $etablissement->plan?->nom,
            'statut' => $etablissement->statut,
            'essai_jusquau' => $etablissement->date_fin_essai?->toDateString(),
        ];
    }
}
