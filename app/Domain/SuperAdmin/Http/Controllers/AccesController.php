<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Http\Controllers;

use App\Domain\SuperAdmin\Http\Requests\SuspendreEtablissementRequest;
use App\Domain\SuperAdmin\Http\Resources\EtablissementResource;
use App\Domain\SuperAdmin\Models\Etablissement;
use App\Domain\SuperAdmin\Services\AccesService;
use App\Http\Controllers\Controller;

class AccesController extends Controller
{
    public function __construct(private readonly AccesService $acces) {}

    public function suspendre(SuspendreEtablissementRequest $request, Etablissement $etablissement)
    {
        $etablissement = $this->acces->suspendre($etablissement, $request->validated()['motif']);

        return EtablissementResource::make($etablissement);
    }

    public function reactiver(Etablissement $etablissement)
    {
        $etablissement = $this->acces->reactiver($etablissement);

        return EtablissementResource::make($etablissement);
    }

    public function archiver(Etablissement $etablissement)
    {
        $etablissement = $this->acces->archiver($etablissement);

        return EtablissementResource::make($etablissement);
    }
}
