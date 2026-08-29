<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Models;

use App\Models\Concerns\HasTenantAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnneeScolaire extends Model
{
    use HasTenantAudit;

    protected $table = 'annee_scolaire';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'libelle',
        'date_debut',
        'date_fin',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'active' => 'boolean',
        ];
    }

    public function trimestres(): HasMany
    {
        return $this->hasMany(Trimestre::class, 'annee_id');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(Classe::class, 'annee_id');
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class, 'annee_id');
    }
}
