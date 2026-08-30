<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Services;

use App\Domain\SuperAdmin\Models\Etablissement;
use App\Domain\SuperAdmin\Notifications\EtablissementSuspendu;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

/**
 * Suspension/réactivation/archivage d'une école (§15.4). La suspension ne
 * supprime jamais de données ; seul l'archivage (J+60) exporte puis coupe
 * l'accès définitivement.
 */
class AccesService
{
    public function suspendre(Etablissement $etablissement, string $motif): Etablissement
    {
        return DB::transaction(function () use ($etablissement, $motif) {
            $etablissement->update([
                'statut' => Etablissement::STATUT_SUSPENDU,
                'date_suspension' => now(),
                'motif_suspension' => $motif,
            ]);

            $this->revoquerSessionsActives($etablissement);
            $this->notifierDirecteurs($etablissement, new EtablissementSuspendu($etablissement->nom, $motif));

            return $etablissement->fresh();
        });
    }

    public function reactiver(Etablissement $etablissement): Etablissement
    {
        $etablissement->update([
            'statut' => Etablissement::STATUT_ACTIF,
            'date_suspension' => null,
            'motif_suspension' => null,
        ]);

        return $etablissement->fresh();
    }

    public function archiver(Etablissement $etablissement): Etablissement
    {
        return DB::transaction(function () use ($etablissement) {
            $this->exporterVersBucketFroid($etablissement);

            $etablissement->update([
                'statut' => Etablissement::STATUT_ARCHIVE,
                'date_resiliation' => now(),
            ]);

            return $etablissement->fresh();
        });
    }

    private function revoquerSessionsActives(Etablissement $etablissement): void
    {
        User::where('tenant_id', $etablissement->tenant_id)
            ->get()
            ->each(fn (User $user) => $user->tokens()->delete());
    }

    public function notifierDirecteurs(Etablissement $etablissement, Notification $notification): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($etablissement->id);

        User::where('tenant_id', $etablissement->tenant_id)
            ->whereHas('roles', fn ($query) => $query->where('name', 'ECOLE_ADMIN'))
            ->get()
            ->each(fn (User $user) => $user->notify($notification));
    }

    private function exporterVersBucketFroid(Etablissement $etablissement): void
    {
        $export = [
            'etablissement' => $etablissement->toArray(),
            'archive_le' => now()->toIso8601String(),
        ];

        Storage::disk('s3')->put(
            "archives/{$etablissement->tenant_id}.json",
            json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        );
    }
}
