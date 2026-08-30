<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Table globale, hors scope tenant (§15.5) : pas de BelongsToTenant, un
 * Super Admin doit pouvoir lister/modifier toutes les écoles de la plateforme.
 */
class Etablissement extends Model
{
    use HasUuids;

    public const STATUT_ACTIF = 'ACTIF';

    public const STATUT_SUSPENDU = 'SUSPENDU';

    public const STATUT_ARCHIVE = 'ARCHIVE';

    /**
     * Team spatie "hors tenant" utilisé pour porter le rôle SUPER_ADMIN :
     * model_has_roles.team_id est UUID NOT NULL (§3.3 / migration
     * convert_permission_team_id_to_uuid), donc null n'est pas assignable —
     * on réserve ce nil-UUID comme team de la plateforme elle-même.
     */
    public const PLATFORM_TEAM_ID = '00000000-0000-0000-0000-000000000000';

    protected $table = 'etablissement';

    /**
     * Le schéma (§5.1) n'a qu'une colonne created_at, pas updated_at.
     */
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'nom',
        'sous_domaine',
        'logo_url',
        'adresse',
        'telephone',
        'email',
        'plan_id',
        'statut',
        'couleur_primaire',
        'couleur_secondaire',
        'logo_s3_key',
        'nom_court',
        'slogan',
        'date_debut_essai',
        'date_fin_essai',
        'date_suspension',
        'motif_suspension',
        'date_resiliation',
        'nb_eleves_max',
        'stockage_max_go',
        'modules_actifs',
        'contact_directeur',
        'telephone_ecole',
        'ville',
        'pays',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'date_debut_essai' => 'date',
            'date_fin_essai' => 'date',
            'date_suspension' => 'datetime',
            'date_resiliation' => 'datetime',
            'modules_actifs' => 'array',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanTarifaire::class, 'plan_id');
    }

    public function reglementsSaas(): HasMany
    {
        return $this->hasMany(ReglementSaas::class, 'etablissement_id');
    }
}
