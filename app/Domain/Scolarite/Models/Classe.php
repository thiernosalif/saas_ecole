<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Models;

use App\Models\Concerns\HasTenantAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classe extends Model
{
    use HasTenantAudit;

    protected $table = 'classe';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'niveau_id',
        'annee_id',
        'libelle',
        'capacite_max',
        'prof_principal_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class, 'niveau_id');
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_id');
    }

    public function profPrincipal(): BelongsTo
    {
        return $this->belongsTo(Personne::class, 'prof_principal_id');
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class, 'classe_id');
    }
}
