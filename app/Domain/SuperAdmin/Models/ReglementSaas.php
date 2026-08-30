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
