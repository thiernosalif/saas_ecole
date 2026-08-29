<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

trait HasTenantAudit
{
    use BelongsToTenant, HasUuids;

    private static array $hasCreatedByColumn = [];

    protected static function bootHasTenantAudit(): void
    {
        static::creating(function (Model $model): void {
            // Toutes les tables Scolarite n'ont pas de colonne created_by (seule
            // `personne` l'a, cf. PROJET.md §5.2) : on vérifie le schéma avant
            // d'affecter l'attribut pour ne pas casser l'INSERT des autres tables.
            if (! array_key_exists(static::class, self::$hasCreatedByColumn)) {
                self::$hasCreatedByColumn[static::class] = Schema::hasColumn($model->getTable(), 'created_by');
            }

            if (self::$hasCreatedByColumn[static::class] && ! $model->created_by) {
                $model->created_by = auth()->id();
            }
        });
    }
}
