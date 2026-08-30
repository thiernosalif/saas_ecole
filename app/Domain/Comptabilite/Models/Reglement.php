<?php

declare(strict_types=1);

namespace App\Domain\Comptabilite\Models;

use App\Models\Concerns\HasTenantAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reglement extends Model
{
    use HasTenantAudit;

    // Moyens de paiement manuels (staff, disponibles day one, aucune
    // dépendance externe — cf. Session 11).
    public const MOYEN_ESPECES = 'ESPECES';

    public const MOYEN_CHEQUE = 'CHEQUE';

    public const MOYEN_VIREMENT = 'VIREMENT';

    // Moyens de paiement en ligne (module optionnel `paiement_mobile_money`).
    public const MOYEN_WAVE = 'WAVE';

    public const MOYEN_ORANGE_MONEY = 'ORANGE_MONEY';

    public const MOYENS_MANUELS = [self::MOYEN_ESPECES, self::MOYEN_CHEQUE, self::MOYEN_VIREMENT];

    public const MOYENS_EN_LIGNE = [self::MOYEN_WAVE, self::MOYEN_ORANGE_MONEY];

    public const STATUT_CONFIRME = 'CONFIRME';

    public const STATUT_EN_ATTENTE = 'EN_ATTENTE';

    public const STATUT_ECHEC = 'ECHEC';

    protected $table = 'reglement';

    /**
     * Le schéma (§7) n'a aucune colonne de timestamp gérée par Eloquent
     * (paid_at est un champ métier saisi explicitement, pas un timestamp
     * d'audit).
     */
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'facture_id',
        'montant',
        'moyen_paiement',
        'reference',
        'paid_at',
        'statut',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class, 'facture_id');
    }
}
