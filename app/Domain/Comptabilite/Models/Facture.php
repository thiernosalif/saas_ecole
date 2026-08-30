<?php

declare(strict_types=1);

namespace App\Domain\Comptabilite\Models;

use App\Domain\Scolarite\Models\AnneeScolaire;
use App\Domain\Scolarite\Models\Personne;
use App\Models\Concerns\HasTenantAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Facture extends Model
{
    use HasTenantAudit;

    public const STATUT_EN_ATTENTE = 'EN_ATTENTE';

    public const STATUT_PARTIELLE = 'PARTIELLE';

    public const STATUT_PAYEE = 'PAYEE';

    protected $table = 'facture';

    /**
     * Le schéma (§7) n'a qu'une colonne created_at, pas updated_at.
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'eleve_id',
        'annee_id',
        'numero',
        'montant_total',
        'montant_paye',
        'statut',
        'due_at',
    ];

    protected function casts(): array
    {
        return [
            'montant_total' => 'decimal:2',
            'montant_paye' => 'decimal:2',
            'due_at' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Personne::class, 'eleve_id');
    }

    public function annee(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_id');
    }

    public function reglements(): HasMany
    {
        return $this->hasMany(Reglement::class, 'facture_id');
    }

    public function soldeRestant(): string
    {
        return bcsub((string) $this->montant_total, (string) $this->montant_paye, 2);
    }
}
