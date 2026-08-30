<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Services;

use App\Domain\SuperAdmin\Models\Etablissement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PersonnalisationService
{
    private const DISQUE = 's3';

    public function uploaderLogo(Etablissement $etablissement, UploadedFile $logo): Etablissement
    {
        $chemin = "logos/{$etablissement->tenant_id}.{$logo->extension()}";

        Storage::disk(self::DISQUE)->put($chemin, file_get_contents($logo->getRealPath()));

        $etablissement->update([
            'logo_s3_key' => $chemin,
            'logo_url' => Storage::disk(self::DISQUE)->url($chemin),
        ]);

        return $etablissement->fresh();
    }

    public function mettreAJour(Etablissement $etablissement, array $data): Etablissement
    {
        $champs = array_filter(
            $data,
            fn (string $cle) => in_array($cle, ['couleur_primaire', 'couleur_secondaire', 'nom_court', 'slogan'], true),
            ARRAY_FILTER_USE_KEY,
        );

        $etablissement->update($champs);

        return $etablissement->fresh();
    }
}
