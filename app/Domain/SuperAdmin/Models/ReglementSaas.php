<?php

declare(strict_types=1);

namespace App\Domain\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReglementSaas extends Model
{
    use HasUuids;

    public const STATUT_EN_ATTENTE = 'EN_ATTENTE';

    public const STATUT_PAYE = 'PAYE';

    // Moyens de paiement manuels (facturation §15.4 d'origine, staff plateforme).
    public const MOYEN_ESPECES = 'ESPECES';

    public const MOYEN_CHEQUE = 'CHEQUE';

    public const MOYEN_VIREMENT = 'VIREMENT';

    // Moyens de paiement en ligne (Session 13) — l'école règle elle-même son
    // abonnement plateforme, même intégration Wave/Orange Money que la
    // Session 11 (paiement des parents), cf. AbonnementService.
    public const MOYEN_WAVE = 'WAVE';

    public const MOYEN_ORANGE_MONEY = 'ORANGE_MONEY';

    public const MOYENS_MANUELS = [self::MOYEN_ESPECES, self::MOYEN_CHEQUE, self::MOYEN_VIREMENT];

    public const MOYENS_EN_LIGNE = [self::MOYEN_WAVE, self::MOYEN_ORANGE_MONEY];

    /**
     * Volontairement pas de STATUT_ECHEC : CheckAbonnementsCommand ne
     * surveille que les lignes EN_ATTENTE (§15.4). Un paiement Wave/OM
     * échoué doit rester EN_ATTENTE pour que le cycle J-7/J-3/J0 existant
     * continue de le piloter — cf. SuperAdmin\Http\Controllers\WebhookController.
     */
    protected $table = 'reglement_saas';

    public $timestamps = false;

    protected $fillable = [
        'etablissement_id',
        'mois',
        'montant',
        'moyen_paiement',
        'reference',
        'statut',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'paid_at' => 'datetime',
            'montant' => 'decimal:2',
        ];
    }

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class, 'etablissement_id');
    }
}
