<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Http\Controllers;

use App\Domain\SuperAdmin\Http\Requests\PersonnaliserEtablissementRequest;
use App\Domain\SuperAdmin\Http\Requests\UploaderLogoRequest;
use App\Domain\SuperAdmin\Http\Resources\EtablissementResource;
use App\Domain\SuperAdmin\Models\Etablissement;
use App\Domain\SuperAdmin\Services\PersonnalisationService;
use App\Http\Controllers\Controller;

class PersonnalisationController extends Controller
{
    public function __construct(private readonly PersonnalisationService $personnalisation) {}

    public function update(PersonnaliserEtablissementRequest $request, Etablissement $etablissement)
    {
        $etablissement = $this->personnalisation->mettreAJour($etablissement, $request->validated());

        return EtablissementResource::make($etablissement);
    }

    public function uploaderLogo(UploaderLogoRequest $request, Etablissement $etablissement)
    {
        $etablissement = $this->personnalisation->uploaderLogo($etablissement, $request->file('logo'));

        return EtablissementResource::make($etablissement);
    }
}
