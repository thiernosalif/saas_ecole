<?php

declare(strict_types=1);

namespace App\Domain\Scolarite\Models;

use App\Models\Concerns\HasTenantAudit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Personne extends Model
{
    use HasTenantAudit;

    public const TYPE_ELEVE = 'ELEVE';

    public const TYPE_PARENT = 'PARENT';

    public const TYPE_PROF = 'PROF';

    public const TYPE_STAFF = 'STAFF';

    protected $table = 'personne';

    protected $fillable = [
        'tenant_id',
        'type',
        'nom',
        'prenom',
        'date_naissance',
        'lieu_naissance',
        'genre',
        'matricule',
        'photo_url',
        'telephone',
        'email',
        'adresse',
        'nationalite',
        'num_acte_naissance',
        'groupe_sanguin',
        'allergies',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'date_naissance' => 'date',
            'actif' => 'boolean',
        ];
    }

    public function scopeEleves(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_ELEVE);
    }

    public function scopeProfs(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_PROF);
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class, 'eleve_id');
    }

    public function liensParente(): HasMany
    {
        return $this->hasMany(LienParente::class, 'eleve_id');
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(Personne::class, 'lien_parente', 'eleve_id', 'parent_id');
    }

    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class, 'eleve_id');
    }

    public function inscriptionActive(): ?Inscription
    {
        return $this->inscriptions()
            ->where('statut', Inscription::STATUT_ACTIVE)
            ->latest('date_inscription')
            ->first();
    }
}
