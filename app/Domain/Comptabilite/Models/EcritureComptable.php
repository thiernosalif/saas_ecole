<?php

declare(strict_types=1);

namespace App\Domain\Comptabilite\Models;

use App\Models\Concerns\HasTenantAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcritureComptable extends Model
{
    use HasTenantAudit;

    protected $table = 'ecriture_comptable';

    const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'reglement_id',
        'facture_id',
        'montant',
        'moyen_paiement',
        'libelle',
        'date_mouvement',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'date_mouvement' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function reglement(): BelongsTo
    {
        return $this->belongsTo(Reglement::class, 'reglement_id');
    }

    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class, 'facture_id');
    }
}
