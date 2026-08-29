<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Models;

use App\Models\Concerns\HasTenantAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inscription extends Model
{
    use HasTenantAudit;

    public const STATUT_ACTIVE = 'ACTIVE';

    public const STATUT_TRANSFERT = 'TRANSFERT';

    public const STATUT_RADIATION = 'RADIATION';

    public const STATUT_ABANDON = 'ABANDON';

    protected $table = 'inscription';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'eleve_id',
        'classe_id',
        'annee_id',
        'statut',
        'date_inscription',
        'frais_inscription',
    ];

    protected function casts(): array
    {
        return [
            'date_inscription' => 'date',
            'frais_inscription' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Personne::class, 'eleve_id');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class, 'classe_id');
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_id');
    }
}
