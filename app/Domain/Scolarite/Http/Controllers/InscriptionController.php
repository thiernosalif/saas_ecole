<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Http\Controllers;

use App\Domain\Scolarite\Http\Requests\StoreInscriptionRequest;
use App\Domain\Scolarite\Http\Requests\StoreTransfertRequest;
use App\Domain\Scolarite\Http\Resources\InscriptionResource;
use App\Domain\Scolarite\Services\InscriptionService;
use App\Domain\Scolarite\Services\TransfertService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class InscriptionController extends Controller
{
    public function __construct(
        private readonly InscriptionService $inscriptions,
        private readonly TransfertService $transferts,
    ) {}

    public function store(StoreInscriptionRequest $request)
    {
        $inscription = $this->inscriptions->create($request->validated());

        return InscriptionResource::make($inscription)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function transferer(StoreTransfertRequest $request)
    {
        $inscription = $this->transferts->declencher($request->validated());

        return InscriptionResource::make($inscription);
    }
}
