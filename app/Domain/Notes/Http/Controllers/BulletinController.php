<?php

declare(strict_types=1);

namespace App\Domain\Notes\Http\Controllers;

use App\Domain\Notes\Http\Requests\GenererBulletinRequest;
use App\Domain\Notes\Http\Resources\BulletinResource;
use App\Domain\Notes\Models\Bulletin;
use App\Domain\Notes\Services\BulletinService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class BulletinController extends Controller
{
    public function __construct(private readonly BulletinService $bulletins) {}

    public function generer(GenererBulletinRequest $request)
    {
        $data = $request->validated();

        if ($data['classe_id'] ?? null) {
            $bulletins = $this->bulletins->genererPourClasse($data['classe_id'], $data['trimestre_id']);

            return BulletinResource::collection($bulletins);
        }

        $bulletin = $this->bulletins->genererPourEleve($data['eleve_id'], $data['trimestre_id']);

        return BulletinResource::make($bulletin);
    }

    public function pdf(Bulletin $bulletin)
    {
        $this->authorize('view', $bulletin);

        abort_unless($bulletin->url_pdf, 404, 'Bulletin non encore généré.');

        return Storage::disk('local')->download(
            $bulletin->url_pdf,
            "bulletin-{$bulletin->eleve_id}-{$bulletin->trimestre_id}.pdf",
        );
    }
}
