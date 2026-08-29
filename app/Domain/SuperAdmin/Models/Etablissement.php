<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Stub minimal pour Session 2 : uniquement les colonnes dont ResolveTenant
 * a besoin (tenant_id, nom, sous_domaine, statut). La migration complète
 * (§5.1, §15.2) et les colonnes/casts additionnels arrivent en Session 3.
 * Pas de BelongsToTenant : table globale, hors scope tenant.
 */
class Etablissement extends Model
{
    use HasUuids;

    protected $table = 'etablissement';

    /**
     * Le schéma cible (§5.1) n'a qu'une colonne created_at, pas updated_at.
     */
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'nom',
        'sous_domaine',
        'statut',
    ];
}
