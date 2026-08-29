<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Http\Resources;

use App\Domain\Scolarite\Models\Personne;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Personne */
class EleveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'date_naissance' => $this->date_naissance?->toDateString(),
            'lieu_naissance' => $this->lieu_naissance,
            'genre' => $this->genre,
            'matricule' => $this->matricule,
            'photo_url' => $this->photo_url,
            'telephone' => $this->telephone,
            'email' => $this->email,
            'adresse' => $this->adresse,
            'nationalite' => $this->nationalite,
            'num_acte_naissance' => $this->num_acte_naissance,
            'groupe_sanguin' => $this->groupe_sanguin,
            'allergies' => $this->allergies,
            'actif' => $this->actif,
            'classe' => $this->whenLoaded('inscriptions', function () {
                $classe = $this->inscriptions->first()?->classe;

                return $classe ? new ClasseResource($classe) : null;
            }),
        ];
    }
}
