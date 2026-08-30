<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Http\Resources;

use App\Domain\SuperAdmin\Models\Etablissement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Etablissement */
class EtablissementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'nom' => $this->nom,
            'nom_court' => $this->nom_court,
            'sous_domaine' => $this->sous_domaine,
            'slogan' => $this->slogan,
            'logo_url' => $this->logo_url,
            'couleur_primaire' => $this->couleur_primaire,
            'couleur_secondaire' => $this->couleur_secondaire,
            'adresse' => $this->adresse,
            'ville' => $this->ville,
            'pays' => $this->pays,
            'telephone' => $this->telephone,
            'telephone_ecole' => $this->telephone_ecole,
            'email' => $this->email,
            'contact_directeur' => $this->contact_directeur,
            'statut' => $this->statut,
            'date_debut_essai' => $this->date_debut_essai?->toDateString(),
            'date_fin_essai' => $this->date_fin_essai?->toDateString(),
            'date_suspension' => $this->date_suspension?->toIso8601String(),
            'motif_suspension' => $this->motif_suspension,
            'date_resiliation' => $this->date_resiliation?->toIso8601String(),
            'nb_eleves_max' => $this->nb_eleves_max,
            'stockage_max_go' => $this->stockage_max_go,
            'modules_actifs' => $this->modules_actifs,
            'plan' => $this->whenLoaded('plan', fn () => [
                'id' => $this->plan->id,
                'nom' => $this->plan->nom,
                'prix_mensuel' => $this->plan->prix_mensuel,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
